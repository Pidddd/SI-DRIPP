<?php
/**
 * BACKEND MODUL PRODUK - PROSES TAMBAH DATA
 * File: modules/products/process/create.php
 */

// 1. Panggil mesin utama (termasuk koneksi database PostgreSQL)
require_once '../../../config/bootstrap.php';

// 2. Keamanan: Pastikan file ini hanya bisa diakses dari submit form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

// 3. Tangkap data dari form (Pastikan atribut 'name' di form HTML buatan Inas sama dengan ini)
$id_product   = trim($_POST['id_product']);
$nama_barang  = trim($_POST['nama_barang']);
$harga_beli   = $_POST['harga_beli'];
$harga_jual   = $_POST['harga_jual'];
$kategori_id  = $_POST['category_id_category'];

// Note: stok_aktual tidak ditangkap dari form karena defaultnya 0 saat barang baru ditambahkan.

// 4. Query INSERT menggunakan Parameterized Query untuk keamanan ekstra dari SQL Injection
$query = "INSERT INTO products (id_product, nama_barang, harga_beli, harga_jual, category_id_category) 
          VALUES ($1, $2, $3, $4, $5)";

// Eksekusi query dengan fungsi bawaan PostgreSQL
$result = pg_query_params($conn, $query, array($id_product, $nama_barang, $harga_beli, $harga_jual, $kategori_id));

// 5. Cek hasil eksekusi
if ($result) {
    // Jika sukses, kembalikan ke halaman daftar produk
    header("Location: ../index.php?pesan=sukses_tambah");
    exit();
} else {
    // Jika gagal (misal ID duplikat), kembalikan ke form create
    $error_msg = pg_last_error($conn);
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
?>