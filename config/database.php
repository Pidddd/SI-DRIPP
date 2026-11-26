<?php
/**
 * KONEKSI DATABASE POSTGRESQL SUPABASE (PHP NATIVE PROSEDURAL)
 * File: config/database.php
 * Fungsi: Membaca kredensial murni dari environment variable (.env / Render),
 *         membuka koneksi pg_connect dengan sslmode=require,
 *         dan menyinkronkan zona waktu sesi PostgreSQL ke WIB (Asia/Jakarta).
 */

// 1. Muat file .env lokal jika belum dimuat oleh bootstrap
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// 2. Baca parameter koneksi murni dari environment variable (Render $_ENV/$_SERVER/getenv atau .env lokal)
$db_host = function_exists('env_get') ? (string)env_get('DB_HOST', '') : (getenv('DB_HOST') ?: '');
$db_port = function_exists('env_get') ? (string)env_get('DB_PORT', '5432') : (getenv('DB_PORT') ?: '5432');
$db_name = function_exists('env_get') ? (string)env_get('DB_NAME', '') : (getenv('DB_NAME') ?: '');
$db_user = function_exists('env_get') ? (string)env_get('DB_USER', '') : (getenv('DB_USER') ?: '');
$db_pass = function_exists('env_get') ? env_get('DB_PASS', null) : getenv('DB_PASS');

// Pertahankan fallback config/db_pass.local.php bila DB_PASS belum diisi di .env lokal
if (($db_pass === null || $db_pass === false || $db_pass === '') && file_exists(__DIR__ . '/db_pass.local.php')) {
    $db_pass = require __DIR__ . '/db_pass.local.php';
}
$db_pass = (string)($db_pass ?? '');

if ($db_host === '' || $db_name === '' || $db_user === '') {
    die("<h3>Konfigurasi Database Belum Lengkap</h3><p>Variabel environment <code>DB_HOST</code>, <code>DB_NAME</code>, atau <code>DB_USER</code> belum diatur. Di lokal, salin <code>.env.example</code> menjadi <code>.env</code>; di Render, isi pada menu <b>Environment Variables</b>.</p>");
}

// 3. Koneksi ke host remote Supabase wajib menggunakan sslmode=require
$db_pass_escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $db_pass);
$conn_string = "host={$db_host} port={$db_port} dbname={$db_name} user={$db_user} password='{$db_pass_escaped}' sslmode=require";

// Eksekusi koneksi menggunakan fungsi native PostgreSQL PHP
$conn = @pg_connect($conn_string);

// 4. Pengecekan status koneksi
if (!$conn) {
    $err = error_get_last();
    $errMsg = isset($err['message']) ? $err['message'] : 'Gagal membuka koneksi ke host database Supabase.';
    die("<h3>Koneksi ke Database Supabase Gagal</h3><p>Detail: " . htmlspecialchars($errMsg) . "</p><p><b>Kemungkinan penyebab:</b><br>1. Koneksi internet di laptop terputus / tidak stabil.<br>2. DNS/Jaringan memblokir koneksi ke <code>" . htmlspecialchars($db_host) . "</code>.<br>3. Kredensial database pada file <code>.env</code> belum tepat.</p><p><i>Pastikan konfigurasi .env benar dan laptop terhubung ke internet, lalu refresh halaman ini.</i></p>");
}

// 5. Sinkronisasi zona waktu sesi PostgreSQL ke WIB (Asia/Jakarta)
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta';");
