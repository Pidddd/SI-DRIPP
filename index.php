<?php

/**
 * GLOBAL ROUTER / REDIRECTOR
 * File: index.php
 * Fungsi: Memeriksa session aktif pengguna. 
 * - Jika belum login -> diarahkan ke modules/auth/login.php
 * - Jika sudah login -> diarahkan ke modules/dashboard/index.php
 * PENTING: Dilarang menulis struktur HTML aplikasi utama di file ini.
 */

require_once 'config/bootstrap.php';
if (!isset($_SESSION['id_user'])) {
    header("Location: " . BASE_URL . "modules/auth/login.php");
} else {
    header("Location: " . BASE_URL . "modules/dashboard/index.php");
}
exit();
