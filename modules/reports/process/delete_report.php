<?php
/**
 * BACKEND MODUL LAPORAN — HAPUS ARSIP TRANSAKSI LAPORAN
 * File: modules/reports/process/delete_report.php
 * Fungsi: Menghapus entri transaksi dari laporan (khusus Super Admin).
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin']);

$halaman_laporan = BASE_URL . 'modules/reports/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode akses penghapusan laporan tidak valid.';
    header("Location: {$halaman_laporan}");
    exit();
}

$id_transaksi = trim($_POST['id_transaksi'] ?? '');

if ($id_transaksi === '') {
    $_SESSION['error'] = 'ID Transaksi yang akan dihapus tidak ditemukan.';
    header("Location: {$halaman_laporan}");
    exit();
}

$res_del = pg_query_params($conn, "DELETE FROM transactions WHERE id_transaksi = $1", [$id_transaksi]);

if ($res_del && pg_affected_rows($res_del) > 0) {
    $_SESSION['success'] = "Arsip transaksi #{$id_transaksi} berhasil dihapus dari laporan.";
} else {
    $_SESSION['error'] = "Gagal menghapus arsip transaksi #{$id_transaksi}: " . pg_last_error($conn);
}

header("Location: {$halaman_laporan}");
exit();
