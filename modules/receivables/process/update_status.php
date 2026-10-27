<?php
/**
 * BACKEND MODUL PIUTANG — UPDATE STATUS PEMBAYARAN LUNAS
 * File: modules/receivables/process/update_status.php
 * Fungsi: Mengubah status_pembayaran transaksi piutang (2 -> 1 / Lunas) di Supabase.
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin']);

$halaman_piutang = BASE_URL . 'modules/receivables/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Akses pengiriman data tidak valid.';
    header("Location: {$halaman_piutang}");
    exit();
}

$id_transaksi = trim($_POST['id_transaksi'] ?? '');

if ($id_transaksi === '') {
    $_SESSION['error'] = 'Nomor transaksi piutang tidak ditemukan.';
    header("Location: {$halaman_piutang}");
    exit();
}

$q_update = "UPDATE transactions SET status_pembayaran = 1 WHERE id_transaksi = $1";
$res_upd  = pg_query_params($conn, $q_update, [$id_transaksi]);

if ($res_upd && pg_affected_rows($res_upd) > 0) {
    $_SESSION['success'] = "Tagihan piutang #{$id_transaksi} telah berhasil divalidasi menjadi LUNAS.";
} else {
    $_SESSION['error'] = "Gagal memperbarui status piutang #{$id_transaksi}: " . pg_last_error($conn);
}

header("Location: {$halaman_piutang}");
exit();
