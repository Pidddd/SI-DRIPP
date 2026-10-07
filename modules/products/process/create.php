<?php
/**
 * BACKEND MODUL PRODUK - PROSES TAMBAH, EDIT, & HAPUS DATA
 * File: modules/products/process/create.php
 */

require_once '../../../config/bootstrap.php';

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

$mode                = isset($_POST['mode']) ? $_POST['mode'] : 'create';
$id_product          = isset($_POST['id_product']) ? trim($_POST['id_product']) : '';
$nama_barang         = isset($_POST['nama_barang']) ? trim($_POST['nama_barang']) : '';
$harga_beli          = isset($_POST['harga_beli']) ? (int) $_POST['harga_beli'] : 0;
$harga_jual          = isset($_POST['harga_jual']) ? (int) $_POST['harga_jual'] : 0;
$categoryid_category = isset($_POST['categoryid_category']) ? (int) $_POST['categoryid_category'] : 0;
$stok_awal           = isset($_POST['stok']) && $_POST['stok'] !== '' ? max(0, (int) $_POST['stok']) : 0;

if ($mode === 'edit') {
    $query  = "UPDATE products 
               SET nama_barang = $1, harga_beli = $2, harga_jual = $3, stok_aktual = $4, categoryid_category = $5 
               WHERE id_product = $6";
    $result = pg_query_params($conn, $query, array($nama_barang, $harga_beli, $harga_jual, $stok_awal, $categoryid_category, $id_product));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_edit_produk");
        exit();
    } else {
        header("Location: ../create.php?edit=" . urlencode($id_product) . "&error=gagal_mengubah_produk");
        exit();
    }
} else {
    $query  = "INSERT INTO products (id_product, nama_barang, harga_beli, harga_jual, stok_aktual, categoryid_category) 
               VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $query, array($id_product, $nama_barang, $harga_beli, $harga_jual, $stok_awal, $categoryid_category));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_tambah");
        exit();
    } else {
        header("Location: ../create.php?error=gagal_menyimpan");
        exit();
    }
}