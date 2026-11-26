<?php
/**
 * Handler Tambah & Edit Data Mitra (Tabel public.customers)
 * File: modules/customers/process/save.php
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'kasir']);

$redirect_url = BASE_URL . 'modules/customers/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url);
    exit;
}

$id_customer = (int)($_POST['id_customer'] ?? 0);
$nama_pt     = trim((string)($_POST['nama_pt'] ?? ''));
$nama_pj     = trim((string)($_POST['nama_pj'] ?? ''));
$nik_pj_raw  = trim((string)($_POST['nik_pj'] ?? ''));
$nik_pj      = $nik_pj_raw !== '' ? $nik_pj_raw : null;
$no_telp     = trim((string)($_POST['no_telp'] ?? ''));
$alamat_toko = trim((string)($_POST['alamat_toko'] ?? ''));

if ($nama_pt === '' || $nama_pj === '' || $no_telp === '' || $alamat_toko === '') {
    $_SESSION['error'] = 'Nama PT, Penanggung Jawab, No. Telepon, dan Alamat Toko wajib diisi.';
    header('Location: ' . $redirect_url);
    exit;
}

if (strlen($nama_pt) > 100 || strlen($nama_pj) > 100 || strlen($no_telp) > 20 || ($nik_pj !== null && strlen($nik_pj) > 20)) {
    $_SESSION['error'] = 'Panjang teks melebihi batas maksimum kolom (Nama PT/PJ maks. 100 karakter, NIK/Telepon maks. 20 karakter).';
    header('Location: ' . $redirect_url);
    exit;
}

try {
    $pdo = get_pdo_connection();

    if ($id_customer > 0) {
        $sql = 'update public.customers
                set nama_pt = :nama_pt,
                    nama_pj = :nama_pj,
                    nik_pj = :nik_pj,
                    no_telp = :no_telp,
                    alamat_toko = :alamat_toko
                where id_customer = :id_customer';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama_pt'     => $nama_pt,
            ':nama_pj'     => $nama_pj,
            ':nik_pj'      => $nik_pj,
            ':no_telp'     => $no_telp,
            ':alamat_toko' => $alamat_toko,
            ':id_customer' => $id_customer,
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Data mitra "' . $nama_pt . '" berhasil diperbarui.';
        } else {
            $check = $pdo->prepare('select id_customer from public.customers where id_customer = :id_customer');
            $check->execute([':id_customer' => $id_customer]);
            if ($check->fetch()) {
                $_SESSION['success'] = 'Data mitra "' . $nama_pt . '" sudah tersimpan tanpa perubahan baru.';
            } else {
                $_SESSION['error'] = 'Data mitra yang ingin diperbarui tidak ditemukan.';
            }
        }
    } else {
        $sql = 'insert into public.customers (nama_pt, nama_pj, nik_pj, no_telp, alamat_toko)
                values (:nama_pt, :nama_pj, :nik_pj, :no_telp, :alamat_toko)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama_pt'     => $nama_pt,
            ':nama_pj'     => $nama_pj,
            ':nik_pj'      => $nik_pj,
            ':no_telp'     => $no_telp,
            ':alamat_toko' => $alamat_toko,
        ]);

        $_SESSION['success'] = 'Mitra baru "' . $nama_pt . '" berhasil ditambahkan.';
    }
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23505') {
        $_SESSION['error'] = 'Nama PT / Instansi "' . $nama_pt . '" sudah terdaftar. Gunakan nama lain atau edit data mitra yang sudah ada.';
    } else {
        $_SESSION['error'] = 'Terjadi kesalahan saat menyimpan data mitra. Silakan coba kembali.';
    }
}

header('Location: ' . $redirect_url);
exit;
