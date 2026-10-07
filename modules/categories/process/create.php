<?php

/**
 * backend modul kategori - proses tambah data
 * file: modules/categories/process/create.php
 */

require_once '../../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

$nama_category = isset($_POST['nama_category']) ? trim($_POST['nama_category']) : '';

if (empty($nama_category)) {
    header("Location: ../create.php?error=kategori_kosong");
    exit();
}

$query = "insert into category (nama_category) values ($1)";

$result = pg_query_params($conn, $query, array($nama_category));

if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah");
    exit();
} else {
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
