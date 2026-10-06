<?php

/**
 * backend modul produk - proses tambah data
 * file: modules/products/process/create.php
 */

require_once '../../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

$id_product = trim($_POST['id_product']);
$nama_barang = trim($_POST['nama_barang']);
$harga_beli = $_POST['harga_beli'];
$harga_jual = $_POST['harga_jual'];
$categoryid_category = $_POST['categoryid_category']; // dipastikan sesuai name di frontend

$stok_awal = 0; // wajib dikirim 0 karena constraint database menolak nilai null

$query = "insert into products (id_product, nama_barang, harga_beli, harga_jual, stok_aktual, categoryid_category) values ($1, $2, $3, $4, $5, $6)";

$result = pg_query_params($conn, $query, array($id_product, $nama_barang, $harga_beli, $harga_jual, $stok_awal, $categoryid_category));

if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah");
    exit();
} else {
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
