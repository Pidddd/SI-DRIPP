<?php
// modules/inventory/process/defects.php

// 1. Perbaikan Jalur Include (Mundur 3 Tingkat ke Root)
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

// Hak akses untuk pencatatan barang rusak
auth_guard(['Super Admin', 'Admin', 'Staff Gudang']);

// 2. Perbaikan Redirect URL
$halaman_form = BASE_URL . 'modules/inventory/defect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_form}");
    exit;
}

// 3. Ambil dan Sanitasi Data Input
$id_product        = trim($_POST['id_product'] ?? '');
$kuantitas         = (int)($_POST['kuantitas'] ?? 0);
$keterangan_rusak  = trim($_POST['keterangan_rusak'] ?? '');
$id_user           = (int)($_SESSION['id_user'] ?? 0);

// Validasi input form
if (empty($id_product) || $kuantitas <= 0 || empty($keterangan_rusak)) {
    $_SESSION['error'] = 'Pilih produk, masukkan jumlah kerusakan yang valid (>0), dan tuliskan alasannya.';
    header("Location: {$halaman_form}");
    exit;
}

// 4. Eksekusi Database Menggunakan Native pg_connect ($conn)
pg_query($conn, "BEGIN");

try {
    // Validasi ketersediaan stok fisik sebelum dikurangi
    $q_cek = "SELECT stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
    $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
    $prod = pg_fetch_assoc($res_cek);

    if (!$prod) {
        throw new Exception("Produk tidak ditemukan dalam basis data.");
    }

    if ((int)$prod['stok_aktual'] < $kuantitas) {
        throw new Exception("Jumlah barang rusak ({$kuantitas}) melebihi stok yang tersedia ({$prod['stok_aktual']}).");
    }

    // A. Catat ke tabel defects
    $query_defect = "INSERT INTO defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user) 
                     VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
    $params_defect = [$kuantitas, $keterangan_rusak, $id_product, $id_user];
    $res_defect = pg_query_params($conn, $query_defect, $params_defect);

    if (!$res_defect) {
        throw new Exception("Gagal mencatat data barang rusak: " . pg_last_error($conn));
    }

    // B. Kurangi stok fisik aktual di tabel products (karena rusak/expired tidak boleh dijual normal)
    $query_update = "UPDATE products 
                     SET stok_aktual = stok_aktual - $1 
                     WHERE id_product = $2";
    $params_update = [$kuantitas, $id_product];
    $res_update = pg_query_params($conn, $query_update, $params_update);

    if (!$res_update) {
        throw new Exception("Gagal memperbarui stok barang: " . pg_last_error($conn));
    }

    // Simpan transaksi permanen
    pg_query($conn, "COMMIT");
    $_SESSION['success'] = "Laporan barang cacat/defect berhasil disimpan dan stok otomatis disesuaikan.";
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . BASE_URL . "modules/inventory/defect.php");
exit;
