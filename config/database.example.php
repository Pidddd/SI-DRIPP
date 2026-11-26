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

$db_host = function_exists('env_get') ? (string)env_get('DB_HOST', '') : (getenv('DB_HOST') ?: '');
$db_port = function_exists('env_get') ? (string)env_get('DB_PORT', '5432') : (getenv('DB_PORT') ?: '5432');
$db_name = function_exists('env_get') ? (string)env_get('DB_NAME', '') : (getenv('DB_NAME') ?: '');
$db_user = function_exists('env_get') ? (string)env_get('DB_USER', '') : (getenv('DB_USER') ?: '');
$db_pass = function_exists('env_get') ? (string)env_get('DB_PASS', '') : (getenv('DB_PASS') ?: '');

$db_pass_escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $db_pass);
$conn_string = "host={$db_host} port={$db_port} dbname={$db_name} user={$db_user} password='{$db_pass_escaped}' sslmode=require";

$conn = @pg_connect($conn_string);

if (!$conn) {
    die("<h1>Error Kritis</h1><p>Koneksi database PostgreSQL gagal. Pastikan file <b>.env</b> telah dikonfigurasi dengan benar.</p>");
}

// Sinkronisasi zona waktu sesi PostgreSQL ke WIB (Asia/Jakarta)
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta';");
