<?php
/**
 * Handler Hapus Data Mitra (Tabel public.customers)
 * File: modules/customers/process/delete.php
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin']);

$redirect_url = BASE_URL . 'modules/customers/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url);
    exit;
}

$actual_role = normalisasi_role($_SESSION['role'] ?? '');
if (!in_array($actual_role, ['super_admin', 'admin'], true)) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk menghapus data mitra.';
    header('Location: ' . $redirect_url);
    exit;
}

$id_customer = (int)($_POST['id_customer'] ?? 0);
if ($id_customer <= 0) {
    $_SESSION['error'] = 'ID mitra tidak valid untuk dihapus.';
    header('Location: ' . $redirect_url);
    exit;
}

try {
    $pdo = get_pdo_connection();

    $stmt_trx = $pdo->prepare('select count(*) as total from public.transactions where customerid_customer = :id_customer');
    $stmt_trx->execute([':id_customer' => $id_customer]);
    $trx_row = $stmt_trx->fetch();

    if ((int)($trx_row['total'] ?? 0) > 0) {
        $_SESSION['error'] = 'Mitra tidak dapat dihapus karena sudah terikat riwayat transaksi.';
        header('Location: ' . $redirect_url);
        exit;
    }

    $stmt_del = $pdo->prepare('delete from public.customers where id_customer = :id_customer');
    $stmt_del->execute([':id_customer' => $id_customer]);

    if ($stmt_del->rowCount() > 0) {
        $_SESSION['success'] = 'Data mitra berhasil dihapus.';
    } else {
        $_SESSION['error'] = 'Data mitra tidak ditemukan atau sudah terhapus.';
    }
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23503') {
        $_SESSION['error'] = 'Mitra tidak dapat dihapus karena sudah terikat riwayat transaksi.';
    } else {
        $_SESSION['error'] = 'Terjadi kesalahan saat menghapus data mitra. Silakan coba kembali.';
    }
}

header('Location: ' . $redirect_url);
exit;
