<?php
// modules/inventory/process/stock_in.php

// 1. Perbaikan Jalur Include (Mundur 3 Tingkat ke Root)
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

// Pastikan hanya role yang berwenang (Admin, Super Admin, Gudang) yang bisa input restock
auth_guard(['Super Admin', 'Admin', 'Staff Gudang']);

// 2. Perbaikan Redirect URL
$halaman_form = BASE_URL . 'modules/inventory/stock_in.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_form}");
    exit;
}

// 3. Ambil dan Sanitasi Data Form
$id_product   = trim($_POST['id_product'] ?? '');
$kuantitas    = (int)($_POST['kuantitas'] ?? 0);
$nama_vendor  = trim($_POST['nama_vendor'] ?? '');
$id_user      = (int)($_SESSION['id_user'] ?? 0);

// Validasi input dasar
if (empty($id_product) || $kuantitas <= 0 || empty($nama_vendor)) {
    $_SESSION['error'] = 'Seluruh kolom wajib diisi dengan benar dan jumlah harus lebih dari 0.';
    header("Location: {$halaman_form}");
    exit;
}

// 4. Eksekusi Database Menggunakan Native pg_connect ($conn)
// Memulai transaksi kontrol atomik
pg_query($conn, "BEGIN");

try {
    // A. Simpan riwayat transaksi barang masuk ke tabel restocks
    $query_restock = "INSERT INTO restocks (waktu_masuk, nama_vendor, kuantitas, productsid_product, userid_user) 
                      VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
    $params_restock = [$nama_vendor, $kuantitas, $id_product, $id_user];
    $res_restock = pg_query_params($conn, $query_restock, $params_restock);

    if (!$res_restock) {
        throw new Exception("Gagal mencatat transaksi restock: " . pg_last_error($conn));
    }

    // B. Tambahkan stok fisik aktual di tabel products
    $query_update_stok = "UPDATE products 
                          SET stok_aktual = stok_aktual + $1 
                          WHERE id_product = $2";
    $params_update = [$kuantitas, $id_product];
    $res_update = pg_query_params($conn, $query_update_stok, $params_update);

    if (!$res_update) {
        throw new Exception("Gagal memperbarui stok barang: " . pg_last_error($conn));
    }

    // Commit jika kedua query berhasil tanpa cela
    pg_query($conn, "COMMIT");
    $_SESSION['success'] = "Restock barang berhasil dicatat! Stok produk bertambah {$kuantitas} botol.";

} catch (Exception $e) {
    // Rollback jika terjadi kegagalan agar data tidak inkonsisten
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

// Pengalihan kembali ke halaman riwayat atau form stok
header("Location: " . BASE_URL . "modules/inventory/index.php");
exit;