<?php
/**
 * AUTH GUARD (Penjaga Halaman)
 */

if (session_status() === PHP_SESSION_NONE) {
    die("Auth Guard membutuhkan session aktif. Panggil bootstrap.php terlebih dahulu.");
}

// Ubah menjadi huruf kecil: id_user
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header("Location: " . BASE_URL . "modules/auth/login.php");
    exit();
}

function cek_hak_akses($allowed_roles) {
    // Ubah menjadi huruf kecil: role
    if (!isset($_SESSION['role'])) {
        die("Error: Session Role tidak ditemukan. Silakan login ulang.");
    }
    
    $user_role = $_SESSION['role'];
    
    if (!in_array($user_role, $allowed_roles)) {
        header("Location: " . BASE_URL . "modules/dashboard/index.php?error=Akses_Ditolak");
        exit();
    }
}
?>