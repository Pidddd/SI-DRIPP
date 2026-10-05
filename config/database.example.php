<?php
/**
 * TEMPLATE KONEKSI DATABASE POSTGRESQL
 * PENTING: 
 * 1. Copy file ini dan rename menjadi 'database.php'
 * 2. Jangan pernah menaruh password asli di file .example ini!
 */

$host = 'localhost';
$port = '5432';          // Port default PostgreSQL
$dbname = 'sidripp_db';  // Nama database yang kamu buat di pgAdmin/DBeaver
$user = 'postgres';      // Username default PostgreSQL (atau sesuaikan dengan milikmu)
$pass = '';              // Biarkan KOSONG agar aman saat di-push ke GitHub

// Merakit string koneksi khusus PostgreSQL
$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$pass";

// Membuka koneksi menggunakan pg_connect
$conn = pg_connect($connection_string);

if (!$conn) {
    // pg_last_error digunakan untuk melihat detail error koneksi di PostgreSQL
    die("<h1>Error Kritis</h1><p>Koneksi database PostgreSQL gagal: " . pg_last_error() . "</p>");
}
?>