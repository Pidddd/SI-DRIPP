<?php
/**
 * BACKEND MODUL AUTH - PROSES LUPA PASSWORD
 * File: modules/auth/process/forgot_password.php
 * PIC: Muhammad Toriq Januarsyah (WBS 3.2.3 - Step 1 Gelombang 1)
 * Paradigma: PHP Native Prosedural Modular (Tanpa OOP, Class, atau PDO)
 * Fungsi: Memvalidasi username, mencocokkan kode pemulihan akun,
 *         memvalidasi password baru & konfirmasi, mengenkripsi password dengan password_hash,
 *         dan memperbarui password user di database PostgreSQL (Supabase) via pg_query_params.
 */

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

// 2. Tangkap & sanitasi input dari form
$username            = isset($_POST['username']) ? trim($_POST['username']) : '';
$kode_pemulihan      = isset($_POST['kode_pemulihan']) ? trim($_POST['kode_pemulihan']) : '';
$password_baru       = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
$konfirmasi_password = isset($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';

// 3. Validasi kelengkapan data
if (empty($username) || empty($kode_pemulihan) || empty($password_baru) || empty($konfirmasi_password)) {
    header("Location: ../forgot_password.php?error=Semua_kolom_wajib_diisi");
    exit();
}

// 4. Validasi panjang password baru minimal 6 karakter
if (strlen($password_baru) < 6) {
    header("Location: ../forgot_password.php?error=Password_baru_minimal_6_karakter");
    exit();
}

// 5. Validasi kesesuaian password baru dan konfirmasi password
if ($password_baru !== $konfirmasi_password) {
    header("Location: ../forgot_password.php?error=Konfirmasi_password_tidak_cocok");
    exit();
}

// 6. Cek kecocokan user dan kode pemulihan di database PostgreSQL menggunakan Prepared Statement (pg_query_params)
$query_user = "SELECT id_user, username, kode_pemulihan FROM users WHERE username = $1 LIMIT 1";
$result_user = pg_query_params($conn, $query_user, array($username));

if (!$result_user || pg_num_rows($result_user) === 0) {
    header("Location: ../forgot_password.php?error=Username_tidak_ditemukan");
    exit();
}

$user = pg_fetch_assoc($result_user);
$kode_valid = !empty($user['kode_pemulihan']) ? $user['kode_pemulihan'] : 'MKP2026';

if ($kode_pemulihan !== $kode_valid) {
    header("Location: ../forgot_password.php?error=Kode_pemulihan_tidak_valid");
    exit();
}

// 7. Enkripsi (hash) password baru secara aman menggunakan algoritma default PHP (Bcrypt/Argon2)
$password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

// 8. Eksekusi UPDATE password di tabel users dengan pg_query_params
$query_update = "UPDATE users SET password = $1 WHERE id_user = $2";
$result_update = pg_query_params($conn, $query_update, array($password_hash, (int)$user['id_user']));

if (!$result_update) {
    header("Location: ../forgot_password.php?error=Gagal_memperbarui_password_database");
    exit();
}

// 9. Redirect sukses kembali ke login dengan pesan notifikasi
header("Location: ../login.php?pesan=Password_berhasil_diperbarui_silakan_login");
exit();
