<?php
// config/helper.php

// Fungsi format angka ke format Rupiah
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

// Fungsi sanitasi input form agar aman dari XSS
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Fungsi format tanggal Indonesia sederhana (contoh: 27 Oktober 2026)
if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal) {
        if (!$tanggal) return '-';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
        return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
    }
}