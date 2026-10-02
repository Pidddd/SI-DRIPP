<?php
/**
 * TEMPLATE KONEKSI DATABASE
 * PENTING: 
 * 1. Copy file ini dan rename menjadi 'database.php'
 * 2. Jangan pernah menaruh password asli di file .example ini!
 */

$host = 'localhost';
$user = 'root';
$pass = '';       // Biarkan KOSONG agar aman saat di-push ke GitHub
$dbname = 'sidripp'; // Pastikan nama DB sesuai ERD revisi dosen

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>