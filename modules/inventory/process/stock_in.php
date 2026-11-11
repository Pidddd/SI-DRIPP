<?php
// modules/inventory/process/stock_in.php
/**
 * BACKEND MODUL INVENTORY — PROSES RESTOCK BATCH BARU & BULK MASS UPDATE STOK
 * File: modules/inventory/process/stock_in.php
 * PIC Backend: Toriq (WBS 3.4.1 & 3.4.4), UI: Timothy
 * Mendukung:
 *   1. Mode 'bulk_update' (array delta_stok[id_product]) dari modules/inventory/index.php
 *   2. Mode Restock Batch Baru (kode_batch, expired_date, nama_vendor, kuantitas) dari modules/inventory/stock_in.php
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

// Pastikan hanya role yang berwenang (Admin, Super Admin, Gudang)
auth_guard(['Super Admin', 'Admin', 'Staff Gudang', 'super_admin', 'admin', 'staf_gudang']);

$halaman_form = BASE_URL . 'modules/inventory/stock_in.php';
$halaman_stok = BASE_URL . 'modules/inventory/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_stok}");
    exit();
}

$id_user = (int)($_SESSION['id_user'] ?? 0);
$mode    = trim($_POST['mode'] ?? '');

// ============================================================================
// MODE 1: BULK MASS UPDATE DARI TABEL INVENTARIS STOK (index.php)
// Mendukung penyesuaian granular per batch (delta_batch) & per produk (delta_stok)
// ============================================================================
if ($mode === 'bulk_update') {
    $delta_stok  = $_POST['delta_stok'] ?? [];
    $delta_batch = $_POST['delta_batch'] ?? [];

    $ada_perubahan = false;
    if (is_array($delta_stok)) {
        foreach ($delta_stok as $ds) {
            if ((int)$ds !== 0) { $ada_perubahan = true; break; }
        }
    }
    if (!$ada_perubahan && is_array($delta_batch)) {
        foreach ($delta_batch as $db) {
            if ((int)$db !== 0) { $ada_perubahan = true; break; }
        }
    }

    if (!$ada_perubahan) {
        $_SESSION['success'] = 'Tidak ada perubahan stok yang dilakukan (seluruh nilai penyesuaian 0).';
        header("Location: {$halaman_stok}");
        exit();
    }

    pg_query($conn, "BEGIN");

    try {
        $jumlah_diubah = 0;
        $ringkasan     = [];

        // 1. Proses penyesuaian granular per batch (Revisi Poin 3 - Timothy Accordion FEFO)
        if (is_array($delta_batch) && !empty($delta_batch)) {
            foreach ($delta_batch as $id_restock => $val_delta) {
                $id_restock = (int)$id_restock;
                $delta      = (int)$val_delta;

                if ($id_restock <= 0 || $delta === 0) {
                    continue;
                }

                // Kunci baris restock dan ambil data produk terkait
                $q_b = "SELECT r.id_restock, r.kode_batch, r.sisa_stok_batch, r.productsid_product, p.nama_barang, p.stok_aktual 
                        FROM restocks r 
                        JOIN products p ON r.productsid_product = p.id_product 
                        WHERE r.id_restock = $1 FOR UPDATE";
                $res_b = pg_query_params($conn, $q_b, [$id_restock]);
                $b_row = $res_b ? pg_fetch_assoc($res_b) : false;

                if (!$b_row) {
                    throw new Exception("Batch restock ID #{$id_restock} tidak ditemukan.");
                }

                $sisa_batch_lama = (int)$b_row['sisa_stok_batch'];
                $sisa_batch_baru = $sisa_batch_lama + $delta;

                if ($sisa_batch_baru < 0) {
                    throw new Exception("Pengurangan pada batch '{$b_row['kode_batch']}' ({$b_row['nama_barang']}) melebihi sisa batch (sisa {$sisa_batch_lama}, penyesuaian {$delta}).");
                }

                $stok_prod_lama = (int)$b_row['stok_aktual'];
                $stok_prod_baru = $stok_prod_lama + $delta;

                if ($stok_prod_baru < 0) {
                    throw new Exception("Stok total produk '{$b_row['nama_barang']}' tidak boleh kurang dari 0.");
                }

                $status_b = $sisa_batch_baru <= 0 ? 'habis' : 'aktif';

                // Update sisa_stok_batch dan status_batch
                $q_upd_b = "UPDATE restocks SET sisa_stok_batch = $1, status_batch = $2 WHERE id_restock = $3";
                $res_upd_b = pg_query_params($conn, $q_upd_b, [$sisa_batch_baru, $status_b, $id_restock]);
                if (!$res_upd_b) {
                    throw new Exception("Gagal memperbarui sisa stok batch #{$id_restock}: " . pg_last_error($conn));
                }

                // Update total stok_aktual produk terkait
                $q_upd_p = "UPDATE products SET stok_aktual = $1 WHERE id_product = $2";
                $res_upd_p = pg_query_params($conn, $q_upd_p, [$stok_prod_baru, $b_row['productsid_product']]);
                if (!$res_upd_p) {
                    throw new Exception("Gagal memperbarui stok total produk: " . pg_last_error($conn));
                }

                // Audit jika pengurangan stok batch
                if ($delta < 0) {
                    $q_audit = "INSERT INTO stock_opname (waktu_opname, stok_sistem, stok_fisik, selisih, status_validasi, userid_user, productsid_product)
                                VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4, $5, $6)";
                    $ket_audit = "Penyesuaian Batch " . ($b_row['kode_batch'] ?? 'BATCH');
                    pg_query_params($conn, $q_audit, [$stok_prod_lama, $stok_prod_baru, $delta, $ket_audit, $id_user, $b_row['productsid_product']]);
                }

                $tanda = $delta > 0 ? "+{$delta}" : (string)$delta;
                $ringkasan[] = "{$b_row['nama_barang']} [{$b_row['kode_batch']}] ({$tanda})";
                $jumlah_diubah++;
            }
        }

        // 2. Proses penyesuaian cepat per produk (untuk produk tanpa batch)
        if (is_array($delta_stok) && !empty($delta_stok)) {
            foreach ($delta_stok as $id_product => $val_delta) {
                $id_product = trim((string)$id_product);
                $delta      = (int)$val_delta;

                // Abaikan produk yang tidak mengalami perubahan (delta == 0)
                if ($id_product === '' || $delta === 0) {
                    continue;
                }

                // Kunci baris produk untuk konsistensi data
                $q_cek = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
                $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
                $prod = $res_cek ? pg_fetch_assoc($res_cek) : false;

                if (!$prod) {
                    throw new Exception("Produk dengan SKU {$id_product} tidak ditemukan di sistem.");
                }

                $stok_sekarang = (int)$prod['stok_aktual'];
                $stok_baru     = $stok_sekarang + $delta;

                if ($stok_baru < 0) {
                    throw new Exception("Pengurangan stok pada '{$prod['nama_barang']}' melebihi stok yang tersedia (sisa {$stok_sekarang}, dikurangi {$delta}).");
                }

                // Update stok_aktual pada tabel products
                $q_upd = "UPDATE products SET stok_aktual = $1 WHERE id_product = $2";
                $res_upd = pg_query_params($conn, $q_upd, [$stok_baru, $id_product]);

                if (!$res_upd) {
                    throw new Exception("Gagal memperbarui stok produk {$prod['nama_barang']}: " . pg_last_error($conn));
                }

                // Jika delta positif, catat riwayat restock cepat
                if ($delta > 0) {
                    $q_res = "INSERT INTO restocks (waktu_masuk, nama_vendor, kuantitas, sisa_stok_batch, status_batch, productsid_product, userid_user) 
                              VALUES (CURRENT_TIMESTAMP, 'Penyesuaian Massal Gudang', $1, $1, 'aktif', $2, $3)";
                    pg_query_params($conn, $q_res, [$delta, $id_product, $id_user]);
                } else {
                    // Jika delta negatif, catat riwayat audit penyesuaian di stock_opname
                    $selisih = $delta;
                    $q_audit = "INSERT INTO stock_opname (waktu_opname, stok_sistem, stok_fisik, selisih, status_validasi, userid_user, productsid_product)
                                VALUES (CURRENT_TIMESTAMP, $1, $2, $3, 'Penyesuaian Massal Pengurangan Stok', $4, $5)";
                    pg_query_params($conn, $q_audit, [$stok_sekarang, $stok_baru, $selisih, $id_user, $id_product]);
                }

                $tanda = $delta > 0 ? "+{$delta}" : (string)$delta;
                $ringkasan[] = "{$prod['nama_barang']} ({$tanda} botol)";
                $jumlah_diubah++;
            }
        }

        pg_query($conn, "COMMIT");

        if ($jumlah_diubah > 0) {
            $teks_info = count($ringkasan) <= 3 ? implode(', ', $ringkasan) : "{$jumlah_diubah} item berhasil disesuaikan";
            $_SESSION['success'] = "Perubahan stok berhasil disimpan: {$teks_info}.";
        } else {
            $_SESSION['success'] = "Tidak ada produk yang diubah (semua penyesuaian bernilai 0).";
        }

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: {$halaman_stok}");
    exit();
}

// ============================================================================
// MODE 2: INPUT RESTOCK BATCH BARU DARI FORM stock_in.php
// ============================================================================
$id_product   = trim($_POST['id_product'] ?? '');
$kuantitas    = (int)($_POST['kuantitas'] ?? 0);
$nama_vendor  = trim($_POST['nama_vendor'] ?? '');
$kode_batch   = trim($_POST['kode_batch'] ?? '');
$expired_date = trim($_POST['expired_date'] ?? '');

// Validasi input
if (empty($id_product) || $kuantitas <= 0 || empty($nama_vendor)) {
    $_SESSION['error'] = 'Seluruh kolom wajib diisi dengan benar dan jumlah harus lebih dari 0.';
    header("Location: {$halaman_form}");
    exit();
}

pg_query($conn, "BEGIN");

try {
    // 1. Catat ke tabel restocks lengkap dengan kode_batch, expired_date, dan sisa_stok_batch
    $query_restock = "INSERT INTO restocks (waktu_masuk, nama_vendor, kuantitas, kode_batch, expired_date, sisa_stok_batch, status_batch, productsid_product, userid_user) 
                      VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4, $5, 'aktif', $6, $7)";
    $exp_val = !empty($expired_date) ? $expired_date : null;
    $batch_val = !empty($kode_batch) ? $kode_batch : ('BATCH-' . date('Ym') . '-01');
    $params_restock = [$nama_vendor, $kuantitas, $batch_val, $exp_val, $kuantitas, $id_product, $id_user];
    $res_restock = pg_query_params($conn, $query_restock, $params_restock);

    if (!$res_restock) {
        throw new Exception("Gagal mencatat transaksi restock batch: " . pg_last_error($conn));
    }

    // 2. Tambahkan stok aktual di tabel products
    $query_update_stok = "UPDATE products 
                          SET stok_aktual = stok_aktual + $1 
                          WHERE id_product = $2";
    $params_update = [$kuantitas, $id_product];
    $res_update = pg_query_params($conn, $query_update_stok, $params_update);

    if (!$res_update) {
        throw new Exception("Gagal memperbarui stok barang di tabel products: " . pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    $_SESSION['success'] = "Restock barang batch [{$batch_val}] berhasil dicatat! Stok produk bertambah {$kuantitas} botol.";

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: {$halaman_stok}");
exit();
