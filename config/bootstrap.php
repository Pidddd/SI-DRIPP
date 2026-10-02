<?php
/**
 * BOOTSTRAP UTAMA APLIKASI
 * File ini dipanggil (require) di BARIS PERTAMA setiap file UI maupun proses.
 * Tugasnya: Memulai session, mengatur path absolut, dan memanggil file konfigurasi lainnya.
 */

// 1. Mulai Session (jika belum dimulai)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Tentukan BASE_PATH (Path Absolut)
// Ini mencegah error "file not found" akibat penggunaan '../../' yang salah
define('BASE_PATH', dirname(__DIR__) . '/');

// 3. Panggil File Database Asli (File ini disembunyikan oleh .gitignore)
// Kita pakai '@' untuk menyembunyikan warning jika file tidak ditemukan
if (file_exists(BASE_PATH . 'config/database.php')) {
    require_once BASE_PATH . 'config/database.php';
} else {
    // Jika file database.php belum dibuat oleh developer (baru clone repo)
    die("<h1>Error Kritis</h1><p>File konfigurasi database tidak ditemukan! Silakan copy <b>config/database.example.php</b> menjadi <b>config/database.php</b> dan isi password lokal Anda.</p>");
}

// 4. Panggil File Helper (Fungsi bantuan prosedural)
if (file_exists(BASE_PATH . 'config/helper.php')) {
    require_once BASE_PATH . 'config/helper.php';
}

// 5. URL Dasar Aplikasi (Opsional, untuk mempermudah redirect)
// Sesuaikan 'SI-DRIPP-dev' dengan nama folder root di XAMPP/htdocs Anda
$base_url = 'http://localhost/SI-DRIPP-dev/'; 
define('BASE_URL', $base_url);
?>