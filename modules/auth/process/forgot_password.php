<?php
/**
 * BACKEND MODUL AUTH - PROSES LUPA KATA SANDI (UI SEMENTARA)
 * File: modules/auth/process/forgot_password.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Memvalidasi email terdaftar di tabel users dan menampilkan notifikasi UI sementara.
 */

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php");
    exit();
}

// 2. Tangkap & validasi input email terdaftar
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../forgot_password.php?error=Format_email_tidak_valid");
    exit();
}

// 3. Cek apakah email terdaftar pada tabel users
$query_user = "SELECT id_user, nama, email FROM users WHERE LOWER(email) = LOWER($1) LIMIT 1";
$res_user   = pg_query_params($conn, $query_user, array($email));

if (!$res_user || pg_num_rows($res_user) === 0) {
    header("Location: ../forgot_password.php?error=Email_tidak_terdaftar_di_sistem");
    exit();
}

// TODO: Integrasi SMTP / Email API pada tahap deploy

// 4. Notifikasi UI sementara sebelum integrasi pengiriman OTP/email aktif
header("Location: ../forgot_password.php?pesan=Permintaan_pemulihan_diterima._Pengiriman_OTP_ke_email_akan_aktif_setelah_integrasi_SMTP_pada_tahap_deploy._Sementara_hubungi_Super_Admin_untuk_reset_manual.");
exit();
