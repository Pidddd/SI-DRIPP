<?php
/**
 * BACKEND MODUL LAPORAN — HAPUS ARSIP TRANSAKSI DENGAN OTORISASI PASSWORD AKUN
 * File: modules/reports/process/delete_report.php
 * Fungsi: Melindungi histori transaksi dari penghapusan tanpa otorisasi dengan
 *         memverifikasi hash password akun Super Admin yang sedang login menggunakan
 *         pg_query_params dan password_verify().
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

$return_to = (isset($_POST['return_to']) && $_POST['return_to'] === 'history')
    ? BASE_URL . 'modules/transactions/history.php'
    : BASE_URL . 'modules/reports/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode akses penghapusan transaksi tidak valid.';
    header("Location: {$return_to}");
    exit();
}

$id_transaksi       = trim($_POST['id_transaksi'] ?? '');
$password_otorisasi = $_POST['password_otorisasi'] ?? ($_POST['pin_otorisasi'] ?? '');

if ($id_transaksi === '') {
    $_SESSION['error'] = 'ID Transaksi yang akan dihapus tidak ditemukan.';
    header("Location: {$return_to}");
    exit();
}

if (trim($password_otorisasi) === '') {
    $_SESSION['error'] = "Penghapusan ditolak: Kata sandi akun Super Admin wajib diisi untuk menghapus transaksi #{$id_transaksi}.";
    header("Location: {$return_to}");
    exit();
}

// 1. Ambil hash password akun Super Admin yang sedang login dari tabel users
$active_uid = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0);

$user_row = null;
if ($active_uid > 0) {
    $q_user   = "SELECT id_user, nama, password, role FROM users WHERE id_user = $1 LIMIT 1";
    $res_user = pg_query_params($conn, $q_user, [$active_uid]);
    if ($res_user && pg_num_rows($res_user) > 0) {
        $user_row = pg_fetch_assoc($res_user);
    }
}

// Fallback jika sesi menggunakan akun prototipe Super Admin awal
if (!$user_row) {
    $q_sa   = "SELECT id_user, nama, password, role
               FROM users
               WHERE LOWER(role) IN ('super_admin', 'super admin', 'owner')
               ORDER BY id_user ASC
               LIMIT 1";
    $res_sa = pg_query($conn, $q_sa);
    if ($res_sa && pg_num_rows($res_sa) > 0) {
        $user_row = pg_fetch_assoc($res_sa);
    }
}

if (!$user_row || normalisasi_role($user_row['role']) !== 'super_admin') {
    $_SESSION['error'] = 'Otorisasi gagal: Akun Super Admin tidak ditemukan pada database.';
    header("Location: {$return_to}");
    exit();
}

// 2. Verifikasi kata sandi menggunakan password_verify() (dengan fallback teks polos jika data dummy lama belum di-hash)
$db_hash        = (string)$user_row['password'];
$password_valid = password_verify($password_otorisasi, $db_hash) || ($password_otorisasi === $db_hash);

if (!$password_valid) {
    $_SESSION['error'] = "Kata sandi Super Admin salah! Arsip transaksi #{$id_transaksi} dilindungi dan tidak jadi dihapus.";
    header("Location: {$return_to}");
    exit();
}

// 3. Eksekusi penghapusan transaksi setelah kata sandi Super Admin terverifikasi
$res_del = pg_query_params($conn, "DELETE FROM transactions WHERE id_transaksi = $1", [$id_transaksi]);

if ($res_del && pg_affected_rows($res_del) > 0) {
    $_SESSION['success'] = "Otorisasi kata sandi diterima: Arsip transaksi #{$id_transaksi} berhasil dihapus dari database.";
} else {
    $_SESSION['error'] = "Gagal menghapus arsip transaksi #{$id_transaksi}: Data tidak ditemukan atau terkendala relasi database.";
}

header("Location: {$return_to}");
exit();
