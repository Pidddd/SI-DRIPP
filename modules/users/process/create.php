<?php

/**
 * backend modul akun - proses tambah pegawai baru
 * file: modules/users/process/create.php
 */

require_once '../../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

// tangkap data dari form frontend
$nama = trim($_POST['nama']);
$jenis_kelamin = trim($_POST['jenis_kelamin']); // misal: "laki-laki" atau "perempuan"
$username = trim($_POST['username']);
$password_mentah = $_POST['password'];
$role = trim($_POST['role']); // misal: "admin", "kasir", "gudang"

// validasi sederhana
if (empty($nama) || empty($username) || empty($password_mentah)) {
    header("Location: ../create.php?error=data_tidak_lengkap");
    exit();
}

// enkripsi password sebelum masuk ke database
$password_hashed = password_hash($password_mentah, PASSWORD_DEFAULT);

// query insert ke tabel user (menggunakan tanda kutip ganda pada "user" karena user adalah keyword bawaan postgresql)
$query = 'insert into "user" (nama, jenis_kelamin, username, password, role) values ($1, $2, $3, $4, $5)';

// eksekusi query dengan aman
$result = pg_query_params($conn, $query, array($nama, $jenis_kelamin, $username, $password_hashed, $role));

if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah_pegawai");
    exit();
} else {
    // jika gagal (misal username sudah dipakai orang lain)
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
