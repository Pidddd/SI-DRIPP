<?php
/**
 * BACKEND MODUL PRODUK - PROSES TAMBAH, EDIT, & HAPUS DATA
 * File: modules/products/process/create.php
 * Revisi Dosen Poin 1: Dilarang ada input/edit jumlah stok di Master Produk.
 *                      Stok awal produk baru otomatis 0, dan penambahan stok murni lewat modul Gudang.
 */

require_once '../../../config/bootstrap.php';
require_once '../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin']);

// 1. Tangani aksi Hapus produk via GET (?hapus_id=...)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hapus_id'])) {
    $hapus_id = trim($_GET['hapus_id']);
    if ($hapus_id !== '') {
        $res_del = pg_query_params($conn, "DELETE FROM products WHERE id_product = $1", array($hapus_id));
        if ($res_del) {
            header("Location: ../index.php?pesan=sukses_hapus_produk");
            exit();
        }
    }
    header("Location: ../index.php?error=gagal_menghapus_produk");
    exit();
}

// 2. Pastikan method POST untuk Tambah / Edit produk
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

$mode                = isset($_POST['mode']) ? trim($_POST['mode']) : 'create';
$id_product          = isset($_POST['id_product']) ? trim($_POST['id_product']) : '';
$nama_barang         = isset($_POST['nama_barang']) ? trim($_POST['nama_barang']) : '';
$harga_beli          = isset($_POST['harga_beli']) ? max(0, (int) $_POST['harga_beli']) : 0;
$harga_jual          = isset($_POST['harga_jual']) ? max(0, (int) $_POST['harga_jual']) : 0;
$categoryid_category = isset($_POST['categoryid_category']) ? (int) $_POST['categoryid_category'] : 0;

if ($id_product === '' || $nama_barang === '' || $categoryid_category <= 0) {
    $redir = ($mode === 'edit' && $id_product !== '')
        ? "../create.php?edit=" . urlencode($id_product) . "&error=data_produk_tidak_lengkap"
        : "../create.php?error=data_produk_tidak_lengkap";
    header("Location: " . $redir);
    exit();
}

// 3. Eksekusi Query sesuai Mode (Revisi Poin 1: Tanpa memanipulasi stok dari input form)
if ($mode === 'edit') {
    // Mode Edit: Hanya mengubah nama_barang, harga_beli, harga_jual, dan categoryid_category (stok_aktual TIDAK disentuh)
    $query  = "UPDATE products 
               SET nama_barang = $1, harga_beli = $2, harga_jual = $3, categoryid_category = $4 
               WHERE id_product = $5";
    $result = pg_query_params($conn, $query, array($nama_barang, $harga_beli, $harga_jual, $categoryid_category, $id_product));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_edit_produk");
        exit();
    } else {
        header("Location: ../create.php?edit=" . urlencode($id_product) . "&error=gagal_mengubah_produk");
        exit();
    }
} else {
    // Mode Tambah Baru: stok_aktual dikunci otomatis bernilai 0 (penambahan stok wajib lewat modul Gudang)
    $stok_default = 0;
    $query  = "INSERT INTO products (id_product, nama_barang, harga_beli, harga_jual, stok_aktual, categoryid_category) 
               VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $query, array($id_product, $nama_barang, $harga_beli, $harga_jual, $stok_default, $categoryid_category));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_tambah_produk_stok_awal_0");
        exit();
    } else {
        header("Location: ../create.php?error=gagal_menyimpan_produk");
        exit();
    }
}
