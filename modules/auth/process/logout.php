<?php

/**
 * BACKEND MODUL AUTH - PROSES LOGOUT
 * File: modules/auth/process/logout.php
 * Paradigma: PHP Native Prosedural Modular
 * Fungsi: Menghapus seluruh data session dan cookie sesi yang tersimpan,
 *         kemudian mengarahkan pengguna kembali ke form login.
 */

require_once '../../../config/bootstrap.php';

// 1. Pastikan session aktif sebelum dibersihkan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kosongkan seluruh variabel dalam array session
$_SESSION = array();

// 3. Hapus cookie session jika browser menggunakannya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Hancurkan sesi di server
session_destroy();

// 5. Alihkan pengguna kembali ke form login dengan pesan sukses
header("Location: ../login.php?pesan=Anda_berhasil_keluar_dari_sistem");
exit();
