<?php
/**
 * BACKEND MODUL INVENTORY — PROSES BULK STOCK OPNAME & SINKRONISASI BATCH EXPIRED
 * File: modules/inventory/process/stock_opname.php
 * Revisi Dosen Poin 2 & 3:
 *   1. Menjalankan sinkronisasi otomatis batch kedaluwarsa (SELECT proses_batch_kedaluwarsa()).
 *   2. Mendukung sistem Bulk Save All (array $_POST['stok_fisik'][$id_product]) maupun single-item fallback.
 *   3. Menggunakan kontrol transaksi ACID (BEGIN, COMMIT, ROLLBACK) & row-level locking (FOR UPDATE).
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

cek_hak_akses(['super_admin', 'admin', 'staf_gudang']);

$halaman_opname = BASE_URL . 'modules/inventory/opname.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_opname}");
    exit();
}

$id_user = (int)($_SESSION['id_user'] ?? 0);

// Normalisasi payload POST agar mendukung Bulk Array (Save All) maupun Single Item (kompatibilitas UI lama)
$daftar_opname = [];
$is_bulk_mode  = false;

if (isset($_POST['stok_fisik']) && is_array($_POST['stok_fisik'])) {
    $is_bulk_mode = true;
    $raw_fisik    = $_POST['stok_fisik'];
    $raw_catatan  = $_POST['status_validasi'] ?? [];
    $raw_ids      = isset($_POST['id_product']) && is_array($_POST['id_product']) ? $_POST['id_product'] : null;

    foreach ($raw_fisik as $key => $val_fisik) {
        // Dukung format associative array stok_fisik['PRD-001'] maupun indexed array id_product[0] + stok_fisik[0]
        $kode_prod = ($raw_ids !== null && isset($raw_ids[$key])) ? trim((string)$raw_ids[$key]) : trim((string)$key);
        if ($kode_prod === '' || $val_fisik === '' || $val_fisik === null) {
            continue;
        }

        $catatan = 'Audit Rutin Gudang (Bulk Opname)';
        if (is_array($raw_catatan) && isset($raw_catatan[$key]) && trim((string)$raw_catatan[$key]) !== '') {
            $catatan = trim((string)$raw_catatan[$key]);
        } elseif (is_array($raw_catatan) && isset($raw_catatan[$kode_prod]) && trim((string)$raw_catatan[$kode_prod]) !== '') {
            $catatan = trim((string)$raw_catatan[$kode_prod]);
        } elseif (!is_array($raw_catatan) && trim((string)$raw_catatan) !== '') {
            $catatan = trim((string)$raw_catatan);
        }

        $daftar_opname[$kode_prod] = [
            'stok_fisik'      => (int)$val_fisik,
            'status_validasi' => $catatan,
        ];
    }
} else {
    // Fallback jika UI masih mengirim 1 produk tunggal per submit
    $id_single      = trim((string)($_POST['id_product'] ?? ''));
    $fisik_single   = isset($_POST['stok_fisik']) && $_POST['stok_fisik'] !== '' ? (int)$_POST['stok_fisik'] : -1;
    $catatan_single = trim((string)($_POST['status_validasi'] ?? 'Penyesuaian Stock Opname'));

    if ($id_single !== '' && $fisik_single >= 0) {
        $daftar_opname[$id_single] = [
            'stok_fisik'      => $fisik_single,
            'status_validasi' => $catatan_single !== '' ? $catatan_single : 'Penyesuaian Stock Opname',
        ];
    }
}

if (count($daftar_opname) === 0) {
    $_SESSION['error'] = 'Data stock opname tidak lengkap atau tidak ada produk yang dikirim.';
    header("Location: {$halaman_opname}");
    exit();
}

// Mulai transaksi atomik PostgreSQL
pg_query($conn, "BEGIN");

try {
    // 1. Panggil fungsi SQL proses_batch_kedaluwarsa() agar batch lewat expired_date otomatis dipotong lebih dulu
    $botol_expired = 0;
    $res_exp = pg_query($conn, "SELECT proses_batch_kedaluwarsa()");
    if ($res_exp) {
        $botol_expired = (int)pg_fetch_result($res_exp, 0, 0);
    }

    $jumlah_disesuaikan = 0;
    $ringkasan_item     = [];

    // 2. Looping setiap produk dalam daftar opname dengan penguncian baris (FOR UPDATE)
    foreach ($daftar_opname as $id_product => $item) {
        $stok_fisik      = (int)$item['stok_fisik'];
        $status_validasi = $item['status_validasi'];

        if ($stok_fisik < 0) {
            throw new Exception("Stok fisik untuk produk {$id_product} tidak boleh bernilai negatif.");
        }

        // Kunci baris produk untuk mencegah race condition
        $q_cek   = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
        $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
        $produk  = $res_cek ? pg_fetch_assoc($res_cek) : false;

        if (!$produk) {
            throw new Exception("Produk dengan kode {$id_product} tidak ditemukan di database.");
        }

        $stok_sistem = (int)$produk['stok_aktual'];
        $selisih     = $stok_fisik - $stok_sistem;

        // Pada mode Bulk Save All, hanya catat & update produk yang mengalami perubahan (atau catat langsung jika mode single-row)
        if ($is_bulk_mode && $selisih === 0) {
            continue;
        }

        // Catat riwayat audit ke tabel stock_opname
        $q_insert = "INSERT INTO stock_opname (waktu_opname, stok_sistem, stok_fisik, selisih, status_validasi, userid_user, productsid_product)
                     VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4, $5, $6)";
        $res_ins  = pg_query_params($conn, $q_insert, [
            $stok_sistem,
            $stok_fisik,
            $selisih,
            $status_validasi,
            $id_user,
            $id_product
        ]);

        if (!$res_ins) {
            throw new Exception("Gagal menyimpan riwayat stock opname untuk {$produk['nama_barang']}: " . pg_last_error($conn));
        }

        // Sinkronkan stok_aktual pada tabel products sesuai stok fisik hasil opname
        $q_update = "UPDATE products SET stok_aktual = $1 WHERE id_product = $2";
        $res_upd  = pg_query_params($conn, $q_update, [$stok_fisik, $id_product]);

        if (!$res_upd) {
            throw new Exception("Gagal memperbarui stok aktual produk {$produk['nama_barang']}: " . pg_last_error($conn));
        }

        $jumlah_disesuaikan++;
        $tanda = $selisih > 0 ? "+{$selisih}" : (string)$selisih;
        $ringkasan_item[] = "{$produk['nama_barang']} ({$stok_sistem} -> {$stok_fisik}, selisih {$tanda})";
    }

    pg_query($conn, "COMMIT");

    $info_expired = $botol_expired > 0
        ? " Sistem juga otomatis menarik {$botol_expired} botol yang melewati tanggal kedaluwarsa."
        : "";

    if ($jumlah_disesuaikan > 0) {
        $detail_teks = count($ringkasan_item) <= 3
            ? implode('; ', $ringkasan_item)
            : "{$jumlah_disesuaikan} varian produk berhasil disesuaikan";
        $_SESSION['success'] = "Stock opname berhasil disimpan: {$detail_teks}.{$info_expired}";
    } else {
        $_SESSION['success'] = "Audit Stock Opname selesai: seluruh stok fisik sudah sesuai dengan stok sistem (tidak ada selisih).{$info_expired}";
    }
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: {$halaman_opname}");
exit();
