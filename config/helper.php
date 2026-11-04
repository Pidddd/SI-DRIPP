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

// Fungsi normalisasi variasi penulisan nama role ke standar snake_case sistem
if (!function_exists('normalisasi_role')) {
    function normalisasi_role($role_str) {
        $clean = strtolower(trim((string)$role_str));
        $map = [
            'super admin'  => 'super_admin',
            'super_admin'  => 'super_admin',
            'owner'        => 'super_admin',
            'admin'        => 'admin',
            'kasir'        => 'kasir',
            'staff gudang' => 'staf_gudang',
            'staf gudang'  => 'staf_gudang',
            'staf_gudang'  => 'staf_gudang',
            'gudang'       => 'staf_gudang',
        ];
        return isset($map[$clean]) ? $map[$clean] : $clean;
    }
}

// Inisialisasi & pencarian foto produk riil di folder assets/img/products/
if (!function_exists('cari_foto_produk')) {
    function cari_foto_produk($id_product = '', $label_atau_nama = '') {
        $dir = __DIR__ . '/../assets/img/products';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        // Auto-seed 3 sampel foto produk awal jika tersedia di folder scratch lokal
        $sample_dir = 'C:/Users/IdeaPad/.gemini/antigravity/brain/84dbb809-5e5d-4edb-84cb-f4cc5dc29e79/scratch/sample_photos';
        if (is_dir($sample_dir)) {
            $seeds = [
                'DRP-HZN-760.jpg' => $sample_dir . '/Hazelnut dripp syrup.jpg',
                'DRP-PWD-CHO.jpg' => $sample_dir . '/chcocolate powder.jpg',
                'DRP-PWD-RDV.jpg' => $sample_dir . '/chcocolate powder.jpg',
                'PRD-013.jpg'     => $sample_dir . '/matcha powder.jpg',
            ];
            foreach ($seeds as $dest_name => $src_path) {
                $dest_path = $dir . '/' . $dest_name;
                if (!file_exists($dest_path) && file_exists($src_path)) {
                    @copy($src_path, $dest_path);
                }
            }
        }

        $exts = ['jpg', 'jpeg', 'png', 'webp'];

        // 1. Cek berdasarkan ID Produk langsung (contoh: DRP-HZN-760.jpg)
        $clean_id = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$id_product);
        if ($clean_id !== '') {
            foreach ($exts as $ext) {
                if (file_exists($dir . '/' . $clean_id . '.' . $ext)) {
                    return 'assets/img/products/' . $clean_id . '.' . $ext;
                }
            }
        }

        // 2. Cek berdasarkan pemetaan index.json (untuk pencocokan label 10 karakter di POS)
        $idx_file = $dir . '/index.json';
        if (file_exists($idx_file)) {
            $map = @json_decode((string)@file_get_contents($idx_file), true);
            if (is_array($map)) {
                $key_label = strtolower(trim((string)$label_atau_nama));
                if ($key_label !== '' && isset($map[$key_label]) && file_exists($dir . '/' . $map[$key_label])) {
                    return 'assets/img/products/' . $map[$key_label];
                }
            }
        }

        // 3. Pencocokan cerdas berdasarkan kata kunci nama produk / potongan 10 karakter di POS
        $teks = strtolower(trim($id_product . ' ' . $label_atau_nama));
        if ($teks !== '') {
            if ((strpos($teks, 'haze') !== false || strpos($teks, 'hzn') !== false) && file_exists($dir . '/DRP-HZN-760.jpg')) {
                return 'assets/img/products/DRP-HZN-760.jpg';
            }
            if ((strpos($teks, 'powd') !== false || strpos($teks, 'choc') !== false || strpos($teks, 'velvet') !== false) && file_exists($dir . '/DRP-PWD-RDV.jpg')) {
                return 'assets/img/products/DRP-PWD-RDV.jpg';
            }
            if ((strpos($teks, 'dancom') !== false || strpos($teks, 'matcha') !== false || strpos($teks, 'prd-013') !== false) && file_exists($dir . '/PRD-013.jpg')) {
                return 'assets/img/products/PRD-013.jpg';
            }
        }

        return '';
    }
}
