<?php
/**
 * AUTH GUARD (Penjaga Halaman & Hak Akses RBAC)
 * File: config/auth_guard.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    $login_url = defined('BASE_URL') ? BASE_URL . "modules/auth/login.php" : "../../modules/auth/login.php";
    header("Location: " . $login_url);
    exit();
}

/**
 * Menormalisasi variasi penulisan nama role ke standar snake_case sistem
 */
function normalisasi_role($role_str) {
    $clean = strtolower(trim((string)$role_str));
    $map = [
        'super admin'  => 'super_admin',
        'super_admin'  => 'super_admin',
        'owner'        => 'super_admin',
        'admin'        => 'admin',
        'kasir'        => 'kasir',
        'staff gudang' => 'staf_gudang',
        'staf gudang'  => 'staf_gudang',
        'staf_gudang'  => 'staf_gudang',
        'gudang'       => 'staf_gudang',
    ];
    return isset($map[$clean]) ? $map[$clean] : $clean;
}

function cek_hak_akses($allowed_roles) {
    if (!isset($_SESSION['role'])) {
        die("Error: Session Role tidak ditemukan. Silakan login ulang.");
    }

    $user_role = normalisasi_role($_SESSION['role']);
    $normalized_allowed = array_map('normalisasi_role', (array)$allowed_roles);

    if (!in_array($user_role, $normalized_allowed, true)) {
        $dash_url = defined('BASE_URL') ? BASE_URL . "modules/dashboard/index.php?error=Akses_Ditolak" : "../../modules/dashboard/index.php?error=Akses_Ditolak";
        header("Location: " . $dash_url);
        exit();
    }
}

/**
 * Fungsi alias kompatibilitas untuk modul yang memanggil auth_guard()
 */
if (!function_exists('auth_guard')) {
    function auth_guard($allowed_roles) {
        cek_hak_akses($allowed_roles);
    }
}
?>