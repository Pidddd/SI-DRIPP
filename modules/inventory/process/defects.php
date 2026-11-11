<?php
/**
 * BACKEND MODUL INVENTORY - PROSES LAPORAN BARANG DEFECT
 * File: modules/inventory/process/defects.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Memproses pencatatan massal (Bulk Save All) maupun satuan barang defect ke tabel defects
 *         serta memotong stok fisik aktual di tabel products secara atomik (ACID).
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

// Hak akses untuk pencatatan barang rusak
cek_hak_akses(['super_admin', 'admin', 'staf_gudang']);

$halaman_form = BASE_URL . 'modules/inventory/defect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_form}");
    exit;
}

$id_user = (int)($_SESSION['id_user'] ?? 0);

// ==========================================
// KASUS 1: BULK INPUT DARI TABEL "SAVE ALL"
// ==========================================
if (isset($_POST['kuantitas']) && is_array($_POST['kuantitas'])) {
    $arr_kuantitas   = $_POST['kuantitas'];
    $arr_keterangan  = isset($_POST['keterangan_rusak']) && is_array($_POST['keterangan_rusak']) ? $_POST['keterangan_rusak'] : [];
    
    // Filter hanya produk yang kuantitas rusaknya > 0
    $items_to_process = [];
    foreach ($arr_kuantitas as $id_prod => $qty) {
        $qty_int = (int)$qty;
        if ($qty_int > 0) {
            $ket = isset($arr_keterangan[$id_prod]) ? trim($arr_keterangan[$id_prod]) : '';
            if (empty($ket)) {
                $ket = 'Barang rusak / cacat fisik di gudang';
            }
            $items_to_process[] = [
                'id_product' => trim($id_prod),
                'kuantitas'  => $qty_int,
                'keterangan' => $ket
            ];
        }
    }

    if (empty($items_to_process)) {
        $_SESSION['error'] = 'Tidak ada kuantitas barang rusak (> 0) yang dimasukkan. Silakan isi jumlah rusak sebelum menyimpan.';
        header("Location: {$halaman_form}");
        exit;
    }

    // Eksekusi atomik database PostgreSQL
    pg_query($conn, "BEGIN");

    try {
        $total_items = 0;
        foreach ($items_to_process as $item) {
            $id_prod = $item['id_product'];
            $qty     = $item['kuantitas'];
            $ket     = $item['keterangan'];

            // Kunci baris produk dengan SELECT FOR UPDATE
            $q_cek   = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
            $res_cek = pg_query_params($conn, $q_cek, [$id_prod]);
            $prod    = pg_fetch_assoc($res_cek);

            if (!$prod) {
                throw new Exception("Produk dengan kode {$id_prod} tidak ditemukan.");
            }

            if ((int)$prod['stok_aktual'] < $qty) {
                throw new Exception("Jumlah rusak untuk '{$prod['nama_barang']}' ({$qty}) melebihi stok gudang yang tersedia ({$prod['stok_aktual']}).");
            }

            // 1. Catat ke tabel defects
            $q_defect = "INSERT INTO defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user) 
                         VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
            $res_defect = pg_query_params($conn, $q_defect, [$qty, $ket, $id_prod, $id_user]);

            if (!$res_defect) {
                throw new Exception("Gagal mencatat data defect untuk {$prod['nama_barang']}: " . pg_last_error($conn));
            }

            // 2. Kurangi stok fisik di tabel products
            $q_update = "UPDATE products 
                         SET stok_aktual = stok_aktual - $1 
                         WHERE id_product = $2";
            $res_update = pg_query_params($conn, $q_update, [$qty, $id_prod]);

            if (!$res_update) {
                throw new Exception("Gagal memotong stok untuk {$prod['nama_barang']}: " . pg_last_error($conn));
            }

            $total_items++;
        }

        pg_query($conn, "COMMIT");
        $_SESSION['success'] = "Berhasil mencatat {$total_items} laporan barang defect dan memotong stok gudang secara otomatis.";
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: {$halaman_form}");
    exit;
}

// ==========================================
// KASUS 2: SINGLE INPUT FALLBACK
// ==========================================
$id_product        = trim($_POST['id_product'] ?? '');
$kuantitas         = (int)($_POST['kuantitas'] ?? 0);
$keterangan_rusak  = trim($_POST['keterangan_rusak'] ?? '');

if (empty($id_product) || $kuantitas <= 0 || empty($keterangan_rusak)) {
    $_SESSION['error'] = 'Pilih produk, masukkan jumlah kerusakan yang valid (>0), dan tuliskan alasannya.';
    header("Location: {$halaman_form}");
    exit;
}

pg_query($conn, "BEGIN");

try {
    $q_cek = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
    $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
    $prod = pg_fetch_assoc($res_cek);

    if (!$prod) {
        throw new Exception("Produk tidak ditemukan dalam basis data.");
    }

    if ((int)$prod['stok_aktual'] < $kuantitas) {
        throw new Exception("Jumlah barang rusak ({$kuantitas}) melebihi stok yang tersedia ({$prod['stok_aktual']}).");
    }

    $query_defect = "INSERT INTO defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user) 
                     VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
    $res_defect = pg_query_params($conn, $query_defect, [$kuantitas, $keterangan_rusak, $id_product, $id_user]);

    if (!$res_defect) {
        throw new Exception("Gagal mencatat data barang rusak: " . pg_last_error($conn));
    }

    $query_update = "UPDATE products 
                     SET stok_aktual = stok_aktual - $1 
                     WHERE id_product = $2";
    $res_update = pg_query_params($conn, $query_update, [$kuantitas, $id_product]);

    if (!$res_update) {
        throw new Exception("Gagal memperbarui stok barang: " . pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    $_SESSION['success'] = "Laporan barang cacat/defect berhasil disimpan dan stok otomatis disesuaikan.";
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: {$halaman_form}");
exit;
