<?php
/**
 * BACKEND MODUL INVENTORY - PROSES LAPORAN BARANG DEFECT PER BATCH
 * File: modules/inventory/process/defects.php
 * Paradigma: PHP Native Prosedural Modular (DILARANG OOP/Class/PDO)
 * Fungsi: Memproses pencatatan massal (Bulk Save All) barang defect per batch aktif ke tabel defects,
 *         memotong restocks.sisa_stok_batch dan products.stok_aktual secara otomatis dan atomik (ACID).
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/auth_guard.php';

// Hak akses untuk pencatatan barang rusak
cek_hak_akses(['super_admin', 'admin', 'staf_gudang']);

$halaman_form = BASE_URL . 'modules/inventory/defect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Metode pengiriman data tidak valid.';
    header("Location: {$halaman_form}");
    exit();
}

$id_user = (int)($_SESSION['id_user'] ?? 0);

// ============================================================================
// KASUS 1: BULK INPUT PER BATCH DARI ACCORDION (kuantitas_batch & keterangan_batch)
// ============================================================================
if (isset($_POST['kuantitas_batch']) && is_array($_POST['kuantitas_batch'])) {
    $arr_kuantitas   = $_POST['kuantitas_batch'];
    $arr_keterangan  = isset($_POST['keterangan_batch']) && is_array($_POST['keterangan_batch']) ? $_POST['keterangan_batch'] : [];

    // Filter hanya batch yang kuantitas rusaknya > 0
    $items_to_process = [];
    foreach ($arr_kuantitas as $id_restock => $qty) {
        $id_restock_int = (int)$id_restock;
        $qty_int        = (int)$qty;

        if ($id_restock_int > 0 && $qty_int > 0) {
            $ket = isset($arr_keterangan[$id_restock]) ? trim($arr_keterangan[$id_restock]) : '';
            if (empty($ket)) {
                $ket = 'Barang rusak / cacat fisik pada batch gudang';
            }
            $items_to_process[] = [
                'id_restock' => $id_restock_int,
                'kuantitas'  => $qty_int,
                'keterangan' => $ket
            ];
        }
    }

    if (empty($items_to_process)) {
        $_SESSION['error'] = 'Tidak ada kuantitas barang rusak (> 0) yang dimasukkan pada batch. Silakan isi jumlah rusak sebelum menyimpan.';
        header("Location: {$halaman_form}");
        exit();
    }

    // Eksekusi atomik database PostgreSQL
    pg_query($conn, "BEGIN");

    try {
        $total_items = 0;
        $ringkasan   = [];

        foreach ($items_to_process as $item) {
            $id_restock = $item['id_restock'];
            $qty        = $item['kuantitas'];
            $ket        = $item['keterangan'];

            // 1. Kunci baris batch di tabel restocks dan baris produk di tabel products (SELECT ... FOR UPDATE)
            $q_cek_batch = "SELECT r.id_restock, r.kode_batch, r.sisa_stok_batch, r.productsid_product,
                                   p.nama_barang, p.stok_aktual
                            FROM restocks r
                            JOIN products p ON r.productsid_product = p.id_product
                            WHERE r.id_restock = $1
                            FOR UPDATE OF r, p";
            $res_cek_batch = pg_query_params($conn, $q_cek_batch, [$id_restock]);
            $batch_row     = $res_cek_batch ? pg_fetch_assoc($res_cek_batch) : false;

            if (!$batch_row) {
                throw new Exception("Batch kedatangan dengan ID #{$id_restock} tidak ditemukan dalam sistem.");
            }

            $sisa_batch_lama = (int)$batch_row['sisa_stok_batch'];
            $id_product      = $batch_row['productsid_product'];
            $nama_barang     = $batch_row['nama_barang'];
            $kode_batch      = $batch_row['kode_batch'] ?? 'BATCH-UMUM';
            $stok_prod_lama  = (int)$batch_row['stok_aktual'];

            // 2. Validasi ketersediaan stok fisik batch dan produk
            if ($sisa_batch_lama < $qty) {
                throw new Exception("Jumlah rusak untuk batch '{$kode_batch}' produk '{$nama_barang}' ({$qty} botol) melebihi sisa stok batch yang tersedia ({$sisa_batch_lama} botol).");
            }

            if ($stok_prod_lama < $qty) {
                throw new Exception("Jumlah rusak untuk '{$nama_barang}' ({$qty} botol) melebihi total stok produk di gudang ({$stok_prod_lama} botol).");
            }

            $sisa_batch_baru = $sisa_batch_lama - $qty;
            $status_batch    = ($sisa_batch_baru <= 0) ? 'habis' : 'aktif';
            $stok_prod_baru  = $stok_prod_lama - $qty;

            // 3. Catat ke tabel defects (sertakan kode batch pada keterangan)
            $ket_final = "[Batch: {$kode_batch}] " . $ket;
            $q_defect = "INSERT INTO defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user)
                         VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
            $res_defect = pg_query_params($conn, $q_defect, [$qty, $ket_final, $id_product, $id_user]);

            if (!$res_defect) {
                throw new Exception("Gagal mencatat data defect untuk batch {$kode_batch}: " . pg_last_error($conn));
            }

            // 4. Potong sisa_stok_batch dan perbarui status_batch di tabel restocks
            $q_upd_batch = "UPDATE restocks 
                            SET sisa_stok_batch = $1, status_batch = $2 
                            WHERE id_restock = $3";
            $res_upd_batch = pg_query_params($conn, $q_upd_batch, [$sisa_batch_baru, $status_batch, $id_restock]);

            if (!$res_upd_batch) {
                throw new Exception("Gagal memotong stok batch {$kode_batch}: " . pg_last_error($conn));
            }

            // 5. Potong stok_aktual di tabel products
            $q_upd_prod = "UPDATE products 
                           SET stok_aktual = $1 
                           WHERE id_product = $2";
            $res_upd_prod = pg_query_params($conn, $q_upd_prod, [$stok_prod_baru, $id_product]);

            if (!$res_upd_prod) {
                throw new Exception("Gagal memotong total stok produk {$nama_barang}: " . pg_last_error($conn));
            }

            $total_items++;
            $ringkasan[] = "{$nama_barang} [{$kode_batch}] (-{$qty})";
        }

        pg_query($conn, "COMMIT");

        $info_text = (count($ringkasan) <= 3) ? implode(', ', $ringkasan) : "{$total_items} batch produk";
        $_SESSION['success'] = "Berhasil mencatat {$total_items} laporan barang defect: {$info_text}. Sisa stok batch dan stok produk otomatis dipotong.";

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: {$halaman_form}");
    exit();
}

// ============================================================================
// KASUS 2: FALLBACK SINGLE INPUT PRODUK (JIKA DIPANGGIL DARI FORM LEGACY)
// ============================================================================
$id_product        = trim($_POST['id_product'] ?? '');
$kuantitas         = (int)($_POST['kuantitas'] ?? 0);
$keterangan_rusak  = trim($_POST['keterangan_rusak'] ?? '');

if (empty($id_product) || $kuantitas <= 0 || empty($keterangan_rusak)) {
    $_SESSION['error'] = 'Pilih batch / produk dan masukkan jumlah kerusakan yang valid (>0).';
    header("Location: {$halaman_form}");
    exit();
}

pg_query($conn, "BEGIN");

try {
    $q_cek = "SELECT nama_barang, stok_aktual FROM products WHERE id_product = $1 FOR UPDATE";
    $res_cek = pg_query_params($conn, $q_cek, [$id_product]);
    $prod = $res_cek ? pg_fetch_assoc($res_cek) : false;

    if (!$prod) {
        throw new Exception("Produk tidak ditemukan dalam basis data.");
    }

    if ((int)$prod['stok_aktual'] < $kuantitas) {
        throw new Exception("Jumlah barang rusak ({$kuantitas}) melebihi stok yang tersedia ({$prod['stok_aktual']}).");
    }

    $query_defect = "INSERT INTO defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user) 
                     VALUES (CURRENT_TIMESTAMP, $1, $2, $3, $4)";
    $res_defect = pg_query_params($conn, $query_defect, [$kuantitas, $keterangan_rusak, $id_product, $id_user]);

    if (!$res_defect) {
        throw new Exception("Gagal mencatat data barang rusak: " . pg_last_error($conn));
    }

    $query_update = "UPDATE products 
                     SET stok_aktual = stok_aktual - $1 
                     WHERE id_product = $2";
    $res_update = pg_query_params($conn, $query_update, [$kuantitas, $id_product]);

    if (!$res_update) {
        throw new Exception("Gagal memperbarui stok barang: " . pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    $_SESSION['success'] = "Laporan barang defect berhasil disimpan dan stok otomatis disesuaikan.";
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $_SESSION['error'] = $e->getMessage();
}

header("Location: {$halaman_form}");
exit();
