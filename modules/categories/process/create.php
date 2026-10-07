<?php
/**
 * BACKEND MODUL KATEGORI - PROSES TAMBAH, EDIT, & HAPUS DATA
 * File: modules/categories/process/create.php
 */

require_once '../../../config/bootstrap.php';

// 1. Tangani aksi Hapus via GET (?hapus_id=...)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hapus_id'])) {
    $hapus_id = (int) $_GET['hapus_id'];
    if ($hapus_id > 0) {
        $res_del = pg_query_params($conn, "DELETE FROM category WHERE id_category = $1", array($hapus_id));
        if ($res_del) {
            header("Location: ../index.php?pesan=sukses_hapus_kategori");
            exit();
        }
    }
    header("Location: ../index.php?error=gagal_menghapus_kategori");
    exit();
}

// 2. Pastikan method POST untuk Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

$nama_category = isset($_POST['nama_category']) ? trim($_POST['nama_category']) : '';
$id_category   = isset($_POST['id_category']) ? (int) $_POST['id_category'] : 0;

if (empty($nama_category)) {
    $redirect = $id_category > 0 ? "../create.php?edit=$id_category&error=kategori_kosong" : "../create.php?error=kategori_kosong";
    header("Location: $redirect");
    exit();
}

// 3. Jika id_category ada -> UPDATE (Edit), jika tidak -> INSERT (Tambah Baru)
if ($id_category > 0) {
    $query  = "UPDATE category SET nama_category = $1 WHERE id_category = $2";
    $result = pg_query_params($conn, $query, array($nama_category, $id_category));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_edit_kategori");
        exit();
    } else {
        header("Location: ../create.php?edit=$id_category&error=gagal_mengubah");
        exit();
    }
} else {
    $query  = "INSERT INTO category (nama_category) VALUES ($1)";
    $result = pg_query_params($conn, $query, array($nama_category));

    if ($result) {
        header("Location: ../index.php?pesan=sukses_tambah");
        exit();
    } else {
        header("Location: ../create.php?error=gagal_menyimpan");
        exit();
    }
}