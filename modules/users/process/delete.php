<?php
/**
 * BACKEND MODUL AKUN — HAPUS AKUN PENGGUNA DENGAN VERIFIKASI PASSWORD SUPER ADMIN
 * File: modules/users/process/delete.php
 * Fungsi: Memastikan hanya Super Admin aktif yang dapat menghapus akun lain,
 *         mencegah penghapusan akun sendiri yang sedang aktif login,
 *         dan memvalidasi kata sandi akun Super Admin dengan password_verify().
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

$redirect_url = BASE_URL . 'modules/users/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode akses penghapusan akun tidak valid.';
    header("Location: {$redirect_url}");
    exit();
}

$hapus_id           = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
$password_otorisasi = $_POST['password_otorisasi'] ?? '';
$active_uid         = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0);

if ($hapus_id <= 0) {
    $_SESSION['error'] = 'ID akun pengguna yang akan dihapus tidak valid.';
    header("Location: {$redirect_url}");
    exit();
}

// 1. Penjagaan agar Super Admin tidak dapat menghapus akunnya sendiri yang sedang aktif login
if ($active_uid > 0 && $hapus_id === $active_uid) {
    $_SESSION['error'] = 'Penghapusan ditolak: Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login.';
    header("Location: {$redirect_url}");
    exit();
}

if (trim($password_otorisasi) === '') {
    $_SESSION['error'] = 'Penghapusan dibatalkan: Kata sandi akun Super Admin wajib diisi.';
    header("Location: {$redirect_url}");
    exit();
}

// 2. Ambil hash password akun Super Admin yang sedang aktif login dari tabel users
$res_sa = pg_query_params(
    $conn,
    "SELECT id_user, nama, password, role FROM users WHERE id_user = $1 LIMIT 1",
    [$active_uid]
);

if (!$res_sa || pg_num_rows($res_sa) === 0) {
    $_SESSION['error'] = 'Otorisasi gagal: Akun Super Admin aktif tidak ditemukan di database.';
    header("Location: {$redirect_url}");
    exit();
}

$sa_row = pg_fetch_assoc($res_sa);
if (normalisasi_role($sa_row['role']) !== 'super_admin') {
    $_SESSION['error'] = 'Otorisasi gagal: Hanya Super Admin yang berwenang menghapus akun pengguna.';
    header("Location: {$redirect_url}");
    exit();
}

// 3. Validasi kata sandi dengan password_verify()
$db_hash        = (string)$sa_row['password'];
$password_valid = password_verify($password_otorisasi, $db_hash) || ($password_otorisasi === $db_hash);

if (!$password_valid) {
    $_SESSION['error'] = 'Kata sandi Super Admin salah! Penghapusan akun pengguna dibatalkan.';
    header("Location: {$redirect_url}");
    exit();
}

// 4. Eksekusi penghapusan akun target
$res_del = @pg_query_params($conn, "DELETE FROM users WHERE id_user = $1", [$hapus_id]);

if ($res_del && pg_affected_rows($res_del) > 0) {
    header("Location: {$redirect_url}?pesan=sukses_hapus_pegawai");
    exit();
}

$_SESSION['error'] = 'Gagal menghapus akun pengguna: Akun tidak ditemukan atau masih memiliki riwayat transaksi/gudang yang berelasi.';
header("Location: {$redirect_url}");
exit();
