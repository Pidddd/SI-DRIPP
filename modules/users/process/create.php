<?php
/**
 * BACKEND MODUL AKUN — PROSES CRUD PEGAWAI (CREATE, UPDATE, DELETE)
 * File: modules/users/process/create.php
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

// 1. Proses Hapus Akun (via GET ?hapus_id=...)
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    if ($hapus_id > 0 && $hapus_id !== (int)($_SESSION['id_user'] ?? 0)) {
        $res_del = pg_query_params($conn, "DELETE FROM users WHERE id_user = $1", [$hapus_id]);
        if ($res_del) {
            header("Location: ../index.php?pesan=sukses_hapus_pegawai");
            exit();
        }
    }
    header("Location: ../index.php?error=Tidak_dapat_menghapus_akun_aktif_atau_berelasi");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../create.php");
    exit();
}

$id_user         = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
$nama            = trim($_POST['nama'] ?? '');
$jenis_kelamin   = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
$username        = trim($_POST['username'] ?? '');
$password_mentah = $_POST['password'] ?? '';
$role            = normalisasi_role($_POST['role'] ?? 'kasir');

if ($nama === '' || $username === '' || ($id_user === 0 && $password_mentah === '')) {
    $redir = $id_user > 0 ? "../create.php?edit={$id_user}&error=data_tidak_lengkap" : "../create.php?error=data_tidak_lengkap";
    header("Location: {$redir}");
    exit();
}

// 2. Validasi Subquery (Requirement PBL): Cek apakah username sudah dipakai oleh id_user lain
$q_cek_duplikat = "SELECT id_user FROM users WHERE username = (SELECT CAST($1 AS VARCHAR)) AND id_user <> $2";
$res_duplikat   = pg_query_params($conn, $q_cek_duplikat, [$username, $id_user]);
if ($res_duplikat && pg_num_rows($res_duplikat) > 0) {
    $redir = $id_user > 0 ? "../create.php?edit={$id_user}&error=username_terpakai" : "../create.php?error=username_terpakai";
    header("Location: {$redir}");
    exit();
}

// 3. Mode Update Akun Existing
if ($id_user > 0) {
    if ($password_mentah !== '') {
        $password_hashed = password_hash($password_mentah, PASSWORD_DEFAULT);
        $q_upd  = "UPDATE users SET nama = $1, jenis_kelamin = $2, username = $3, password = $4, role = $5 WHERE id_user = $6";
        $result = pg_query_params($conn, $q_upd, [$nama, $jenis_kelamin, $username, $password_hashed, $role, $id_user]);
    } else {
        $q_upd  = "UPDATE users SET nama = $1, jenis_kelamin = $2, username = $3, role = $4 WHERE id_user = $5";
        $result = pg_query_params($conn, $q_upd, [$nama, $jenis_kelamin, $username, $role, $id_user]);
    }

    if ($result) {
        header("Location: ../index.php?pesan=sukses_update_pegawai");
        exit();
    }
    header("Location: ../create.php?edit={$id_user}&error=gagal_menyimpan");
    exit();
}

// 4. Mode Insert Akun Baru
$password_hashed = password_hash($password_mentah, PASSWORD_DEFAULT);
$query  = "INSERT INTO users (nama, jenis_kelamin, username, password, role) VALUES ($1, $2, $3, $4, $5)";
$result = pg_query_params($conn, $query, [$nama, $jenis_kelamin, $username, $password_hashed, $role]);

if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah_pegawai");
    exit();
} else {
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
