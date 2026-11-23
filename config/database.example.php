<?php
/**
 * TEMPLATE KONEKSI DATABASE POSTGRESQL SUPABASE
 * File: config/database.example.php
 * PENTING:
 * 1. Kredensial asli disimpan di file `.env` di root proyek (mengacu pada `.env.example`),
 *    atau di tab Environment Variables pada server hosting Render.
 * 2. Jangan pernah menuliskan password asli di file ini!
 */

if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

$db_host = getenv('DB_HOST') ?: '';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: '';
$db_user = getenv('DB_USER') ?: '';
$db_pass = getenv('DB_PASS') ?: '';

$conn_string = "host={$db_host} port={$db_port} dbname={$db_name} user={$db_user} password='{$db_pass}' sslmode=require";

$conn = @pg_connect($conn_string);

if (!$conn) {
    die("<h1>Error Kritis</h1><p>Koneksi database PostgreSQL gagal. Pastikan file <b>.env</b> telah dikonfigurasi dengan benar.</p>");
}

// Sinkronisasi zona waktu sesi PostgreSQL ke WIB (Asia/Jakarta)
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta';");
