<?php
/**
 * BACKEND MODUL AKUN — PROSES CRUD PEGAWAI (CREATE, UPDATE, DELETE)
 * File: modules/users/process/create.php
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

// 1. Proses Hapus Akun (diarahkan wajib melalui POST delete.php dengan verifikasi password Super Admin)
if (isset($_GET['hapus_id'])) {
    $_SESSION['error'] = 'Penghapusan akun wajib dilakukan melalui konfirmasi kata sandi Super Admin.';
    header("Location: ../index.php");
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
$email           = trim($_POST['email'] ?? '');
$no_telp_raw     = trim($_POST['no_telp'] ?? '');
$no_telp         = $no_telp_raw !== '' ? $no_telp_raw : null;
$password_mentah = $_POST['password'] ?? '';
$role            = normalisasi_role($_POST['role'] ?? 'kasir');

if ($nama === '' || $username === '' || $email === '' || ($id_user === 0 && $password_mentah === '')) {
    $redir = $id_user > 0 ? "../create.php?edit={$id_user}&error=data_tidak_lengkap" : "../create.php?error=data_tidak_lengkap";
    header("Location: {$redir}");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $redir = $id_user > 0 ? "../create.php?edit={$id_user}&error=email_tidak_valid" : "../create.php?error=email_tidak_valid";
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

// Validasi duplikat email
$q_cek_email = "SELECT id_user FROM users WHERE LOWER(email) = LOWER($1) AND id_user <> $2";
$res_email   = pg_query_params($conn, $q_cek_email, [$email, $id_user]);
if ($res_email && pg_num_rows($res_email) > 0) {
    $redir = $id_user > 0 ? "../create.php?edit={$id_user}&error=email_terpakai" : "../create.php?error=email_terpakai";
    header("Location: {$redir}");
    exit();
}

// 3. Mode Update Akun Existing
if ($id_user > 0) {
    $admin_password = $_POST['admin_password'] ?? '';
    $active_uid     = isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0);

    if (trim($admin_password) === '') {
        header("Location: ../create.php?edit={$id_user}&error=admin_password_kosong");
        exit();
    }

    // Validasi kata sandi milik Super Admin yang sedang aktif login
    $res_sa = pg_query_params(
        $conn,
        "SELECT id_user, password, role FROM users WHERE id_user = $1 LIMIT 1",
        [$active_uid]
    );
    if (!$res_sa || pg_num_rows($res_sa) === 0) {
        header("Location: ../create.php?edit={$id_user}&error=admin_password_salah");
        exit();
    }

    $sa_row = pg_fetch_assoc($res_sa);
    if (normalisasi_role($sa_row['role']) !== 'super_admin') {
        header("Location: ../create.php?edit={$id_user}&error=admin_password_salah");
        exit();
    }

    $db_hash        = (string)$sa_row['password'];
    $password_valid = password_verify($admin_password, $db_hash) || ($admin_password === $db_hash);
    if (!$password_valid) {
        header("Location: ../create.php?edit={$id_user}&error=admin_password_salah");
        exit();
    }

    // Penjagaan agar Super Admin tidak dapat menurunkan peran (role) akunnya sendiri
    if ($active_uid > 0 && $id_user === $active_uid && $role !== 'super_admin') {
        header("Location: ../create.php?edit={$id_user}&error=turun_role_sendiri");
        exit();
    }

    if ($password_mentah !== '') {
        $password_hashed = password_hash($password_mentah, PASSWORD_DEFAULT);
        $q_upd  = "UPDATE users SET nama = $1, jenis_kelamin = $2, username = $3, email = $4, no_telp = $5, password = $6, role = $7 WHERE id_user = $8";
        $result = pg_query_params($conn, $q_upd, [$nama, $jenis_kelamin, $username, $email, $no_telp, $password_hashed, $role, $id_user]);
    } else {
        $q_upd  = "UPDATE users SET nama = $1, jenis_kelamin = $2, username = $3, email = $4, no_telp = $5, role = $6 WHERE id_user = $7";
        $result = pg_query_params($conn, $q_upd, [$nama, $jenis_kelamin, $username, $email, $no_telp, $role, $id_user]);
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
$query  = "INSERT INTO users (nama, jenis_kelamin, username, email, no_telp, password, role) VALUES ($1, $2, $3, $4, $5, $6, $7)";
$result = pg_query_params($conn, $query, [$nama, $jenis_kelamin, $username, $email, $no_telp, $password_hashed, $role]);

if ($result) {
    header("Location: ../index.php?pesan=sukses_tambah_pegawai");
    exit();
} else {
    header("Location: ../create.php?error=gagal_menyimpan");
    exit();
}
