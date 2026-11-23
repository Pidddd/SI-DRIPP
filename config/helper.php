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

// Fungsi konversi nominal angka Rupiah ke teks Terbilang Bahasa Indonesia
if (!function_exists('_terbilang_satuan_id')) {
    function _terbilang_satuan_id($nilai) {
        $nilai = (int)$nilai;
        $huruf = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
        ];

        if ($nilai < 12) {
            return $huruf[$nilai];
        }
        if ($nilai < 20) {
            return _terbilang_satuan_id($nilai - 10) . ' Belas';
        }
        if ($nilai < 100) {
            $puluh = (int)floor($nilai / 10);
            $sisa  = $nilai % 10;
            return _terbilang_satuan_id($puluh) . ' Puluh' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 200) {
            $sisa = $nilai - 100;
            return 'Seratus' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 1000) {
            $ratus = (int)floor($nilai / 100);
            $sisa  = $nilai % 100;
            return _terbilang_satuan_id($ratus) . ' Ratus' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 2000) {
            $sisa = $nilai - 1000;
            return 'Seribu' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 1000000) {
            $ribu = (int)floor($nilai / 1000);
            $sisa = $nilai % 1000;
            return _terbilang_satuan_id($ribu) . ' Ribu' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 1000000000) {
            $juta = (int)floor($nilai / 1000000);
            $sisa = $nilai % 1000000;
            return _terbilang_satuan_id($juta) . ' Juta' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }
        if ($nilai < 1000000000000) {
            $miliar = (int)floor($nilai / 1000000000);
            $sisa   = $nilai % 1000000000;
            return _terbilang_satuan_id($miliar) . ' Miliar' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
        }

        $triliun = (int)floor($nilai / 1000000000000);
        $sisa    = $nilai % 1000000000000;
        return _terbilang_satuan_id($triliun) . ' Triliun' . ($sisa > 0 ? ' ' . _terbilang_satuan_id($sisa) : '');
    }
}

if (!function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($angka) {
        if (!is_numeric($angka)) {
            return 'Nol Rupiah';
        }
        $n = (int)round((float)$angka);
        if ($n === 0) {
            return 'Nol Rupiah';
        }
        $prefix = $n < 0 ? 'Minus ' : '';
        $teks   = trim(preg_replace('/\s+/', ' ', _terbilang_satuan_id(abs($n))));
        return $prefix . $teks . ' Rupiah';
    }
}

// Fungsi format timestamp PostgreSQL ke format rapi WIB tanpa mikrodetik (contoh: 20 Nov 2026 · 10:10 WIB)
if (!function_exists('format_waktu_wib')) {
    function format_waktu_wib($timestamp) {
        if (empty($timestamp)) {
            return '-';
        }
        // Bersihkan pecahan mikrodetik PostgreSQL (mis. .470465) sebelum parsing
        $clean = preg_replace('/\.\d+/', '', trim((string)$timestamp));
        $ts    = strtotime($clean);
        if ($ts === false) {
            return '-';
        }
        $bulan_singkat = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        $hari  = date('d', $ts);
        $bulan = $bulan_singkat[(int)date('n', $ts)];
        $tahun = date('Y', $ts);
        $jam   = date('H:i', $ts);
        return "{$hari} {$bulan} {$tahun} · {$jam} WIB";
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
