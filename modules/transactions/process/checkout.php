<?php
/**
 * BACKEND MODUL KASIR — PROSES CHECKOUT TRANSAKSI
 * File: modules/transactions/process/checkout.php
 * Fitur PBL: Kontrol Transaksi ACID (BEGIN/COMMIT/ROLLBACK), Validasi Subquery Stok, & Trigger Database.
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'kasir']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$id_transaksi      = 'TRX-' . date('Ymd-His');
$userid_user       = !empty($_POST['userid_user']) ? (int)$_POST['userid_user'] : (int)($_SESSION['id_user'] ?? 1);
$nama_pelanggan    = trim($_POST['nama_pelanggan'] ?? 'Pelanggan Walk-In');
$kontak_pelanggan  = trim($_POST['kontak_pelanggan'] ?? '-');
$status_pembayaran = isset($_POST['status_pembayaran']) ? (int)$_POST['status_pembayaran'] : 1;
$jatuh_tempo       = ($status_pembayaran === 2 && !empty($_POST['jatuh_tempo'])) ? $_POST['jatuh_tempo'] : null;
$diskon            = isset($_POST['diskon']) ? max(0, (int)$_POST['diskon']) : 0;

$produk_ids    = $_POST['product_id'] ?? [];
$kuantitas_arr = $_POST['kuantitas'] ?? [];
$harga_arr     = $_POST['harga'] ?? [];

if (!is_array($produk_ids) || count($produk_ids) === 0) {
    header("Location: ../index.php?error=Keranjang_belanja_kosong");
    exit();
}

$subtotal_kotor = 0;
for ($i = 0; $i < count($produk_ids); $i++) {
    $subtotal_kotor += ((int)$kuantitas_arr[$i] * (int)$harga_arr[$i]);
}
$total_harga = max(0, $subtotal_kotor - $diskon);

// Mulai transaksi atomik PostgreSQL
pg_query($conn, "BEGIN");

try {
    // 1. Insert header nota ke tabel transactions
    $query_trx = "INSERT INTO transactions (id_transaksi, total_harga, userid_user, nama_pelanggan, kontak_pelanggan, status_pembayaran, jatuh_tempo, diskon)
                  VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
    $res_trx = pg_query_params($conn, $query_trx, [
        $id_transaksi,
        $total_harga,
        $userid_user,
        $nama_pelanggan,
        $kontak_pelanggan,
        $status_pembayaran,
        $jatuh_tempo,
        $diskon
    ]);

    if (!$res_trx) {
        throw new Exception("Gagal menyimpan nota utama transaksi.");
    }

    // 2. Looping rincian item dengan Subquery pengecekan stok sebelum insert
    for ($i = 0; $i < count($produk_ids); $i++) {
        $id_prod  = trim((string)$produk_ids[$i]);
        $qty      = (int)$kuantitas_arr[$i];
        $subtotal = $qty * (int)$harga_arr[$i];

        // Validasi Subquery (Requirement PBL): Pastikan stok_aktual produk mencukupi
        $q_subquery_stok = "SELECT id_product, nama_barang
                            FROM products
                            WHERE id_product = $1
                              AND stok_aktual >= (SELECT CAST($2 AS INTEGER))";
        $res_cek_stok = pg_query_params($conn, $q_subquery_stok, [$id_prod, $qty]);

        if (!$res_cek_stok || pg_num_rows($res_cek_stok) === 0) {
            throw new Exception("Stok produk {$id_prod} tidak mencukupi untuk kuantitas {$qty}.");
        }

        // Insert ke transaction_details (Trigger trigger_kurangi_stok di DB otomatis memotong stok_aktual)
        $query_detail = "INSERT INTO transaction_details (kuantitas, subtotal_harga, transactionsid_transaksi, productsid_product)
                         VALUES ($1, $2, $3, $4)";
        $res_detail = pg_query_params($conn, $query_detail, [$qty, $subtotal, $id_transaksi, $id_prod]);

        if (!$res_detail) {
            throw new Exception("Gagal menyimpan detail item transaksi.");
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
