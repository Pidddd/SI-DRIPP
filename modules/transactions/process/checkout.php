<?php
/**
 * BACKEND MODUL KASIR — PROSES CHECKOUT TRANSAKSI
 * File: modules/transactions/process/checkout.php
 * PIC: Muhammad Toriq Januarsyah (Back-End Developer) — Revisi Mitra T2 (20 Nov 2026)
 * Paradigma: PHP Native Prosedural Modular (DILARANG OOP/Class/PDO), Zero HTML/CSS
 * Fitur:
 *   - Kontrol Transaksi ACID (BEGIN / COMMIT / ROLLBACK)
 *   - Validasi Whitelist sumber_transaksi ('Umum' atau 'Shopee')
 *   - Validasi Diskon 2 Mode ('auto' 2% jika total_qty >= 24, atau 'manual')
 *   - Penanganan Ongkir (Hanya masuk ke transactions, tidak ke transaction_details)
 *   - Validasi ulang subtotal & total_qty dari harga_jual tabel products
 *   - Validasi Subquery ketersediaan stok_aktual (PBL requirement)
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'kasir']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

// 1. Identitas Transaksi & Personel
$id_transaksi        = 'TRX-' . date('Ymd-His');
$userid_user         = !empty($_POST['userid_user']) ? (int)$_POST['userid_user'] : (int)($_SESSION['id_user'] ?? 1);
$customerid_customer = !empty($_POST['customerid_customer']) ? (int)$_POST['customerid_customer'] : null;
$nama_pelanggan      = trim($_POST['nama_pelanggan'] ?? 'Pelanggan Walk-In');
$kontak_pelanggan    = trim($_POST['kontak_pelanggan'] ?? '-');

// Jika customerid_customer ada, sinkronkan snapshot nama_pt dan no_telp dari tabel customers
if (!empty($customerid_customer)) {
    $q_cust = "SELECT nama_pt, no_telp FROM customers WHERE id_customer = $1 LIMIT 1";
    $res_cust = pg_query_params($conn, $q_cust, [$customerid_customer]);
    if ($res_cust && pg_num_rows($res_cust) > 0) {
        $c_data = pg_fetch_assoc($res_cust);
        $nama_pelanggan   = $c_data['nama_pt'];
        $kontak_pelanggan = $c_data['no_telp'];
    }
}

// 2. Status Pembayaran & Jatuh Tempo
$status_pembayaran = isset($_POST['status_pembayaran']) ? (int)$_POST['status_pembayaran'] : 1;
$jatuh_tempo       = ($status_pembayaran === 2 && !empty($_POST['jatuh_tempo'])) ? $_POST['jatuh_tempo'] : null;

// 3. Sumber Transaksi (Whitelist Ketat: 'Umum' atau 'Shopee', default 'Umum')
$raw_sumber       = trim($_POST['sumber_transaksi'] ?? 'Umum');
$sumber_transaksi = ($raw_sumber === 'Shopee') ? 'Shopee' : 'Umum';

// 4. Data Keranjang Item
$produk_ids    = $_POST['product_id'] ?? [];
$kuantitas_arr = $_POST['kuantitas'] ?? [];

if (!is_array($produk_ids) || count($produk_ids) === 0) {
    header("Location: ../index.php?error=Keranjang_belanja_kosong");
    exit();
}

// Mulai Transaksi ACID PostgreSQL
pg_query($conn, "BEGIN");

try {
    // 5. Validasi ulang harga_jual dari tabel products, hitung subtotal_kotor dan total_qty
    $subtotal_kotor = 0;
    $total_qty      = 0;
    $items_validated = [];

    for ($i = 0; $i < count($produk_ids); $i++) {
        $id_prod = trim((string)$produk_ids[$i]);
        $qty     = (int)($kuantitas_arr[$i] ?? 0);

        if ($id_prod === '' || $qty <= 0) {
            continue;
        }

        // Ambil data harga_jual resmi dari database untuk mencegah manipulasi form
        $q_prod = "SELECT id_product, nama_barang, harga_jual, stok_aktual 
                   FROM products 
                   WHERE id_product = $1 
                   LIMIT 1";
        $res_prod = pg_query_params($conn, $q_prod, [$id_prod]);

        if (!$res_prod || pg_num_rows($res_prod) === 0) {
            throw new Exception("Produk dengan SKU {$id_prod} tidak ditemukan di database.");
        }

        $prod_data = pg_fetch_assoc($res_prod);
        $harga_resmi = (int)$prod_data['harga_jual'];
        $subtotal_item = $qty * $harga_resmi;

        $subtotal_kotor += $subtotal_item;
        $total_qty      += $qty;

        $items_validated[] = [
            'id_product' => $id_prod,
            'qty'        => $qty,
            'harga'      => $harga_resmi,
            'subtotal'   => $subtotal_item
        ];
    }

    if (empty($items_validated)) {
        throw new Exception("Tidak ada item produk valid dalam keranjang belanja.");
    }

    // 6. Validasi Diskon 2 Mode (Ketentuan Final Mitra MKP Store)
    $diskon_mode = trim($_POST['diskon_mode'] ?? 'auto');
    $diskon      = 0;

    if ($diskon_mode === 'manual') {
        // Mode Manual: input kasir dibatasi 0 s.d. subtotal_kotor
        $raw_diskon = isset($_POST['diskon']) ? (int)$_POST['diskon'] : 0;
        $diskon     = min($subtotal_kotor, max(0, $raw_diskon));
    } else {
        // Mode Otomatis (Default): Jika total botol >= 24 (2 karton), diskon 2% resmi
        if ($total_qty >= 24) {
            $diskon = (int)round($subtotal_kotor * 0.02);
        } else {
            $diskon = 0;
        }
    }

    // 7. Validasi Ongkir (Hanya masuk ke transactions, TIDAK ke transaction_details)
    $pakai_ongkir = trim($_POST['pakai_ongkir'] ?? '0');
    $ongkir       = ($pakai_ongkir === '1') ? max(0, (int)($_POST['ongkir'] ?? 0)) : 0;

    // 8. Hitung Total Akhir Transaksi
    $total_harga = max(0, $subtotal_kotor - $diskon) + $ongkir;

    // 9. Simpan Header Transaksi ke tabel transactions
    $query_trx = "INSERT INTO transactions (
                      id_transaksi, 
                      total_harga, 
                      userid_user, 
                      customerid_customer, 
                      nama_pelanggan, 
                      kontak_pelanggan, 
                      status_pembayaran, 
                      jatuh_tempo, 
                      diskon, 
                      ongkir, 
                      sumber_transaksi
                  ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
    $res_trx = pg_query_params($conn, $query_trx, [
        $id_transaksi,
        $total_harga,
        $userid_user,
        $customerid_customer,
        $nama_pelanggan,
        $kontak_pelanggan,
        $status_pembayaran,
        $jatuh_tempo,
        $diskon,
        $ongkir,
        $sumber_transaksi
    ]);

    if (!$res_trx) {
        throw new Exception("Gagal menyimpan nota utama transaksi: " . pg_last_error($conn));
    }

    // 10. Looping Simpan Detail Item ke transaction_details dengan Validasi Subquery Stok
    foreach ($items_validated as $item) {
        $id_prod  = $item['id_product'];
        $qty      = $item['qty'];
        $subtotal = $item['subtotal'];

        // Validasi Subquery (PBL Requirement): Pastikan stok_aktual mencukupi
        $q_subquery_stok = "SELECT id_product, nama_barang 
                            FROM products 
                            WHERE id_product = $1 
                              AND stok_aktual >= (SELECT CAST($2 AS INTEGER))";
        $res_cek_stok = pg_query_params($conn, $q_subquery_stok, [$id_prod, $qty]);

        if (!$res_cek_stok || pg_num_rows($res_cek_stok) === 0) {
            throw new Exception("Stok produk {$id_prod} tidak mencukupi untuk kuantitas {$qty} unit.");
        }

        // Insert ke transaction_details (Trigger database otomatis memotong stok_aktual)
        $query_detail = "INSERT INTO transaction_details (kuantitas, subtotal_harga, transactionsid_transaksi, productsid_product)
                         VALUES ($1, $2, $3, $4)";
        $res_detail = pg_query_params($conn, $query_detail, [$qty, $subtotal, $id_transaksi, $id_prod]);

        if (!$res_detail) {
            throw new Exception("Gagal menyimpan rincian item transaksi: " . pg_last_error($conn));
        }
    }

    pg_query($conn, "COMMIT");
    header("Location: ../index.php?pesan=transaksi_sukses&id=" . urlencode($id_transaksi));
    exit();

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    header("Location: ../index.php?error=" . urlencode($e->getMessage()));
    exit();
}
