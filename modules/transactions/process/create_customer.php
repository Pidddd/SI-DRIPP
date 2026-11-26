<?php
/**
 * BACKEND TRANSAKSI — REGISTRASI MITRA / PELANGGAN PT BARU
 * File: modules/transactions/process/create_customer.php
 * Revisi Dosen Poin 6: Registrasi data Mitra PT HORECA via Pop-up Modal Kasir POS.
 * Wajib PHP Native Prosedural murni (pg_query_params).
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'kasir']);

$halaman_pos = BASE_URL . 'modules/transactions/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$halaman_pos}");
    exit();
}

$nama_pt     = trim((string)($_POST['nama_pt'] ?? ''));
$nama_pj     = trim((string)($_POST['nama_pj'] ?? ''));
$nik_pj_raw  = trim((string)($_POST['nik_pj'] ?? ''));
$nik_pj      = $nik_pj_raw !== '' ? $nik_pj_raw : null;
$no_telp     = trim((string)($_POST['no_telp'] ?? ''));
$alamat_toko = trim((string)($_POST['alamat_toko'] ?? ''));

if ($nama_pt === '' || $nama_pj === '' || $no_telp === '' || $alamat_toko === '') {
    header("Location: {$halaman_pos}?error=" . urlencode("Nama PT, Penanggung Jawab, No. Telepon, dan Alamat Toko wajib diisi."));
    exit();
}

// Cek duplikasi Nama PT
$q_cek = "select id_customer from public.customers where lower(nama_pt) = lower($1) limit 1";
$res_cek = pg_query_params($conn, $q_cek, [$nama_pt]);
if ($res_cek && pg_num_rows($res_cek) > 0) {
    header("Location: {$halaman_pos}?error=" . urlencode("Mitra PT dengan nama '{$nama_pt}' sudah terdaftar."));
    exit();
}

$query = "insert into public.customers (nama_pt, nama_pj, nik_pj, no_telp, alamat_toko, created_at)
          values ($1, $2, $3, $4, $5, current_timestamp)
          returning id_customer";
$res = pg_query_params($conn, $query, [$nama_pt, $nama_pj, $nik_pj, $no_telp, $alamat_toko]);

if ($res && pg_num_rows($res) > 0) {
    $row = pg_fetch_assoc($res);
    $new_id = (int)$row['id_customer'];
    header("Location: {$halaman_pos}?pesan=mitra_baru_ditambahkan&selected_customer={$new_id}");
    exit();
} else {
    $err = pg_last_error($conn);
    header("Location: {$halaman_pos}?error=" . urlencode("Gagal mendaftarkan mitra baru: {$err}"));
    exit();
}
