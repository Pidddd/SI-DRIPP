<?php
/**
 * BACKEND MODUL AUTH - PROSES LUPA PASSWORD
 * File: modules/auth/process/forgot_password.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Memvalidasi kode pemulihan dan memperbarui password pengguna di database PostgreSQL Supabase.
 */

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php");
    exit();
}

// 2. Tangkap input dari form pemulihan password
$username            = isset($_POST['username']) ? trim($_POST['username']) : '';
$kode_pemulihan      = isset($_POST['kode_pemulihan']) ? trim($_POST['kode_pemulihan']) : '';
$password_baru       = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
$konfirmasi_password = isset($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';

// 3. Validasi kelengkapan data
if (empty($username) || empty($kode_pemulihan) || empty($password_baru) || empty($konfirmasi_password)) {
    header("Location: ../forgot_password.php?error=Semua_kolom_wajib_diisi");
    exit();
}

// 4. Validasi kecocokan password baru dan konfirmasi password
if ($password_baru !== $konfirmasi_password) {
    header("Location: ../forgot_password.php?error=Konfirmasi_password_tidak_cocok");
    exit();
}

// 5. Validasi panjang minimal password
if (strlen($password_baru) < 6) {
    header("Location: ../forgot_password.php?error=Password_baru_minimal_6_karakter");
    exit();
}

// 6. Cek kecocokan username dan kode pemulihan di tabel users
$query_user = "SELECT id_user, nama, username, kode_pemulihan FROM users WHERE username = $1 LIMIT 1";
$res_user   = pg_query_params($conn, $query_user, array($username));

if (!$res_user || pg_num_rows($res_user) === 0) {
    header("Location: ../forgot_password.php?error=Username_tidak_ditemukan");
    exit();
}

$user = pg_fetch_assoc($res_user);

// Verifikasi kode pemulihan (default MKP2026 jika kolom di database null/kosong)
$db_kode = !empty($user['kode_pemulihan']) ? $user['kode_pemulihan'] : 'MKP2026';

if ($kode_pemulihan !== $db_kode) {
    header("Location: ../forgot_password.php?error=Kode_pemulihan_tidak_valid");
    exit();
}

// 7. Hash password baru dengan standar aman BCRYPT
$password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

// 8. Update password user di PostgreSQL
$query_update = "UPDATE users SET password = $1 WHERE id_user = $2";
$res_update   = pg_query_params($conn, $query_update, array($password_hash, (int)$user['id_user']));

if ($res_update) {
    header("Location: ../login.php?pesan=Password_berhasil_diperbarui_silakan_login");
    exit();
} else {
    header("Location: ../forgot_password.php?error=Gagal_memperbarui_password_database");
    exit();
}
