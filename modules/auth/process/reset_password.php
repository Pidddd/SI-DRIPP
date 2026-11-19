<?php
/**
 * BACKEND MODUL AUTH - TAHAP 2: VERIFIKASI OTP & SIMPAN KATA SANDI BARU
 * File: modules/auth/process/reset_password.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Memvalidasi session OTP, masa berlaku, kecocokan OTP, dan memperbarui
 *         password terenkripsi (password_hash) pada tabel users di PostgreSQL Supabase.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../reset_password.php");
    exit();
}

// 2. Pastikan seluruh data session OTP masih tersedia
if (!isset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires'])) {
    header("Location: ../forgot_password.php?error=" . urlencode("Sesi OTP tidak ditemukan atau sudah berakhir. Silakan minta kode OTP baru."));
    exit();
}

// 3. Cek apakah waktu sekarang telah melewati batas berlaku OTP (10 menit)
if (time() > (int)$_SESSION['reset_expires']) {
    unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
    header("Location: ../forgot_password.php?error=" . urlencode("Kode OTP sudah kedaluwarsa. Silakan minta kode OTP baru."));
    exit();
}

// 4. Tangkap input dari form verifikasi
$otp_input           = isset($_POST['otp']) ? trim($_POST['otp']) : '';
$new_password        = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
$konfirmasi_password = isset($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';

// 5. Validasi kelengkapan input
if ($otp_input === '' || $new_password === '' || $konfirmasi_password === '') {
    header("Location: ../reset_password.php?error=" . urlencode("Semua kolom wajib diisi."));
    exit();
}

// 6. Validasi kecocokan kode OTP 6 digit (timing-safe comparison)
if (!hash_equals((string)$_SESSION['reset_otp'], $otp_input)) {
    header("Location: ../reset_password.php?error=" . urlencode("Kode OTP tidak sesuai. Periksa kembali 6 digit kode dari email Anda."));
    exit();
}

// 7. Validasi panjang minimal kata sandi baru (minimal 6 karakter)
if (strlen($new_password) < 6) {
    header("Location: ../reset_password.php?error=" . urlencode("Kata sandi baru minimal terdiri dari 6 karakter."));
    exit();
}

// 8. Validasi kecocokan konfirmasi kata sandi baru
if ($new_password !== $konfirmasi_password) {
    header("Location: ../reset_password.php?error=" . urlencode("Konfirmasi kata sandi baru tidak cocok."));
    exit();
}

// 9. Enkripsi kata sandi baru dengan password_hash & simpan ke PostgreSQL
$password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
$reset_email     = (string)$_SESSION['reset_email'];

$query_update = "UPDATE users SET password = $1 WHERE email = $2";
$res_update   = pg_query_params($conn, $query_update, [$password_hashed, $reset_email]);

if (!$res_update || pg_affected_rows($res_update) === 0) {
    header("Location: ../reset_password.php?error=" . urlencode("Gagal memperbarui kata sandi pada database. Silakan coba lagi."));
    exit();
}

// 10. Hapus seluruh session reset_* agar OTP tidak dapat dipakai ulang
unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires']);

// 11. Arahkan kembali ke halaman login dengan pesan sukses
header("Location: ../login.php?pesan=" . urlencode("Kata sandi berhasil diperbarui. Silakan masuk menggunakan kata sandi baru Anda."));
exit();
