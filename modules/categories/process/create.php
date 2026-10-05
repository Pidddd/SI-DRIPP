<?php

/**
 * BACKEND MODUL KATEGORI - PROSES TAMBAH DATA
 * File: modules/categories/process/create.php
 */

// 1. Panggil mesin utama (koneksi database PostgreSQL)
require_once '../../../config/bootstrap.php';

// 2. Keamanan: Pastikan file ini hanya bisa dieksekusi lewat submit form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

// 3. Tangkap data dari form 
// Nanti Inas/Timothy wajib membuat <input type="text" name="nama_category"> di frontend
$nama_category = trim($_POST['nama_category']);

// Validasi sederhana jika input kosong
if (empty($nama_category)) {
    header("Location: ../create.php?error=kategori_kosong");
    exit();
}

// 4. Siapkan Query PostgreSQL
$query = "INSERT INTO category (nama_category) VALUES ($1)";

// 5. Eksekusi query dengan aman
$result = pg_query_params($conn, $query, array($nama_category));

// 6. Cek hasil dan kembalikan ke halaman tabel kategori
if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah");
    exit();
} else {
    // Jika gagal, catat errornya untuk debugging
    $error_msg = pg_last_error($conn);
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
