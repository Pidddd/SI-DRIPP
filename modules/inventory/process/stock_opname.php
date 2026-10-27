<?php
/**
 * BACKEND MODUL INVENTORY — PROSES STOCK OPNAME
 * File: modules/inventory/process/stock_opname.php
 * Fungsi: Mencatat hasil audit stok fisik ke tabel stock_opname dan menyelaraskan stok_aktual di tabel products.
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'staf_gudang']);

$halaman_opname = BASE_URL . 'modules/inventory/opname.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_opname}");
    exit();
}

$id_product      = trim($_POST['id_product'] ?? '');
$stok_fisik      = isset($_POST['stok_fisik']) ? (int)$_POST['stok_fisik'] : -1;
$status_validasi = trim($_POST['status_validasi'] ?? 'Penyesuaian Stock Opname');
$id_user         = (int)($_SESSION['id_user'] ?? 0);

if ($id_product === '' || $stok_fisik < 0 || $status_validasi === '') {
    $_SESSION['error'] = 'Data stock opname tidak lengkap atau stok fisik bernilai negatif.';
    header("Location: {$halaman_opname}");
    exit();
}

pg_query($conn, "BEGIN");

try {
    // 1. Kunci baris produk dan ambil stok_aktual tercatat di sistem
    $q_cek   = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
    $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
    $produk  = $res_cek ? pg_fetch_assoc($res_cek) : false;

    if (!$produk) {
        throw new Exception("Produk dengan kode {$id_product} tidak ditemukan.");
    }

    $stok_sistem = (int)$produk['stok_aktual'];
    $selisih     = $stok_fisik - $stok_sistem;

    // 2. Catat riwayat audit ke tabel stock_opname
    $q_insert = "INSERT INTO stock_opname (waktu_opname, stok_sistem, stok_fisik, selisih, status_validasi, userid_user, productsid_product)
                 VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4, $5, $6)";
    $res_ins  = pg_query_params($conn, $q_insert, [
        $stok_sistem,
        $stok_fisik,
        $selisih,
        $status_validasi,
        $id_user,
        $id_product
    ]);

    if (!$res_ins) {
        throw new Exception("Gagal menyimpan riwayat stock opname: " . pg_last_error($conn));
    }

    // 3. Sinkronkan stok_aktual pada tabel products sesuai stok fisik hasil opname
    $q_update = "UPDATE products SET stok_aktual = $1 WHERE id_product = $2";
    $res_upd  = pg_query_params($conn, $q_update, [$stok_fisik, $id_product]);

    if (!$res_upd) {
        throw new Exception("Gagal memperbarui stok aktual produk: " . pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    $tanda = $selisih > 0 ? "+{$selisih}" : (string)$selisih;
    $_SESSION['success'] = "Stock opname {$produk['nama_barang']} berhasil disimpan (Stok Sistem: {$stok_sistem} -> Fisik: {$stok_fisik}, Selisih: {$tanda}).";
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: {$halaman_opname}");
exit();
