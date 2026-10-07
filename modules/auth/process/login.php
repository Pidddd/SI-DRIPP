<?php
/**
 * BACKEND MODUL AUTH - PROSES LOGIN
 * File: modules/auth/process/login.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Memvalidasi kredensial pengguna, mengecek password ke database PostgreSQL,
 *         dan menginisialisasi session aktif pengguna.
 */

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

// 2. Tangkap & sanitasi input dari form login
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// 3. Validasi kelengkapan data
if (empty($username) || empty($password)) {
    header("Location: ../login.php?error=Username_dan_password_wajib_diisi");
    exit();
}

// 4. Query data user dengan Prepared Statement (pg_query_params) untuk mencegah SQL Injection
$query = "SELECT id_user, nama, jenis_kelamin, username, password, role FROM users WHERE username = $1 LIMIT 1";
$result = pg_query_params($conn, $query, array($username));

if (!$result || pg_num_rows($result) === 0) {
    header("Location: ../login.php?error=Username_atau_password_salah");
    exit();
}

$user = pg_fetch_assoc($result);

// 5. Verifikasi password (mendukung password_hash dan fallback teks polos jika ada data testing awal)
$password_valid = password_verify($password, $user['password']) || ($password === $user['password']);

if (!$password_valid) {
    header("Location: ../login.php?error=Username_atau_password_salah");
    exit();
}

// 6. Cegah session fixation attack dengan memperbarui ID session
session_regenerate_id(true);

// 7. Simpan data pengguna ke dalam session
$_SESSION['id_user']       = (int)$user['id_user'];
$_SESSION['nama']          = $user['nama'];
$_SESSION['username']      = $user['username'];
$_SESSION['role']          = $user['role'];
$_SESSION['jenis_kelamin'] = $user['jenis_kelamin'];

// 8. Redirect ke halaman awal berdasarkan role pengguna
switch ($user['role']) {
    case 'kasir':
        header("Location: ../../transactions/index.php");
        break;
    case 'staf_gudang':
    case 'gudang':
        header("Location: ../../inventory/index.php");
        break;
    default:
        header("Location: ../../dashboard/index.php");
        break;
}
exit();
