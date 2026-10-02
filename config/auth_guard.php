<?php
/**
 * AUTH GUARD (Penjaga Halaman)
 * File ini dipanggil SETELAH bootstrap.php pada halaman yang membutuhkan login.
 */

// Pastikan file dipanggil setelah session_start() dari bootstrap
if (session_status() === PHP_SESSION_NONE) {
    die("Auth Guard membutuhkan session aktif. Panggil bootstrap.php terlebih dahulu.");
}

// 1. Cek apakah user sudah login
if (!isset($_SESSION['ID_User']) || empty($_SESSION['ID_User'])) {
    // Jika belum login, tendang ke halaman login
    header("Location: " . BASE_URL . "modules/auth/login.php");
    exit();
}

/**
 * Fungsi Bantuan untuk Cek Role
 * Menggunakan pendekatan prosedural murni.
 * Role yang valid sesuai proposal: super_admin, admin, kasir, staf_gudang
 * 
 * @param array $allowed_roles Array berisi role yang diizinkan (contoh: ['super_admin', 'admin'])
 */
function cek_hak_akses($allowed_roles) {
    if (!isset($_SESSION['Role'])) {
        die("Error: Session Role tidak ditemukan. Silakan login ulang.");
    }
    
    $user_role = $_SESSION['Role'];
    
    // Cek apakah role user saat ini ada di dalam daftar role yang diizinkan
    if (!in_array($user_role, $allowed_roles)) {
        // Jika tidak berhak, tendang ke dashboard dengan pesan error
        // (Di dunia nyata, bisa diarahkan ke halaman 403 Forbidden)
        header("Location: " . BASE_URL . "modules/dashboard/index.php?error=Akses_Ditolak");
        exit();
    }
}
?>