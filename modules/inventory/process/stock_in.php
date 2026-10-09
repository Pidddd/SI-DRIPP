<?php

// Bootstrap dipanggil paling awal (session, BASE_PATH, koneksi DB, BASE_URL). __DIR__ dipakai agar path tidak bergantung pada working directory
require_once __DIR__ . '/../../config/bootstrap.php';

// Halaman frontend tujuan redirect (sesuaikan dengan struktur folder UInya)
$halaman_form = BASE_URL . 'inventory/stock_in.php';

// Helper kecil: simpan pesan ke Session lalu redirect. exit wajib supaya script berhenti setelah header Location
function kembali_dengan_pesan(string $kunci, string $pesan, string $url): void
{
    $_SESSION[$kunci] = $pesan;
    header('Location: ' . $url);
    exit;
}

// Guard 1: file ini hanya boleh diakses lewat POST, bukan dibuka langsung dari URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali_dengan_pesan('flash_error', 'Akses tidak valid.', $halaman_form);
}

// Guard 2: harus sudah login. ID petugas diambil dari Session, BUKAN dari form, supaya tidak bisa dipalsukan
if (empty($_SESSION['id_user'])) {
    kembali_dengan_pesan('flash_error', 'Sesi berakhir, silakan login kembali.', $halaman_form);
}
$id_user = (int) $_SESSION['id_user'];

// Ambil input. Form mengirim 1 nama_vendor dan array produk[] + kuantitas[] (satu pengiriman, banyak SKU)
$nama_vendor = trim($_POST['nama_vendor'] ?? '');
$daftar_produk = $_POST['id_product'] ?? [];
$daftar_qty = $_POST['kuantitas'] ?? [];

// Validasi lapis 1 (UX): cepat menolak input jelas salah sebelum menyentuh database. Integritas sebenarnya tetap dijaga constraint DB
if ($nama_vendor === '' || mb_strlen($nama_vendor) > 100) {
    kembali_dengan_pesan('Nama vendor wajib diisi (maksimal 100 karakter).', $halaman_form);
}

// Pastikan keduanya array, tidak kosong, dan jumlah barisnya sama (produk ke-n pasangan qty ke-n)
if (!is_array($daftar_produk) || !is_array($daftar_qty)
    || count($daftar_produk) === 0
    || count($daftar_produk) !== count($daftar_qty)) {
    kembali_dengan_pesan('Daftar barang masuk tidak valid atau kosong.', $halaman_form);
}

// Normalisasi + validasi tiap baris ke array bersih; kalau ada satu saja salah, seluruh request ditolak (all-or-nothing)
$baris_bersih = [];
foreach ($daftar_produk as $i => $id_produk) {
    $id_produk = trim((string) $id_produk);

    // filter_var FILTER_VALIDATE_INT menolak "abc", "5.5", dan "1e3". Opsi min_range=1 menolak 0 dan negatif
    $qty = filter_var($daftar_qty[$i] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    // id_product adalah varchar(20), cek tidak kosong dan tidak melebihi panjang kolom
    if ($id_produk === '' || mb_strlen($id_produk) > 20 || $qty === false) {
        kembali_dengan_pesan('flash_error', 'Baris ke-' . ($i + 1) . ' tidak valid: produk wajib dipilih dan kuantitas harus bilangan bulat > 0.', $halaman_form);
    }

    $baris_bersih[] = ['id_product' => $id_produk, 'kuantitas' => $qty];
}

try {
    // Pastikan PDO melempar Exception saat error; tanpa ini, kegagalan trigger/constraint bisa lolos tanpa terdeteksi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // BEGIN: semua INSERT di bawah dianggap satu kesatuan. Satu gagal, semuanya batal
    $pdo->beginTransaction();

    // Prepared statement dibuat SEKALI dan dieksekusi berulang: aman dari SQL Injection dan lebih efisien.
    // waktu_masuk tidak diisi karena DEFAULT current_timestamp di database.
    // Kolom stok TIDAK disentuh, itu tugas trigger_tambah_stok
    $stmt = $pdo->prepare(
        'INSERT INTO restocks (nama_vendor, kuantitas, productsid_product, userid_user)
         VALUES (:nama_vendor, :kuantitas, :id_product, :id_user)'
    );

    foreach ($baris_bersih as $baris) {
        //  Tiap execute memicu trigger AFTER INSERT di DB yang menambah products.stok_aktual
        $stmt->execute([
            ':nama_vendor' => $nama_vendor,
            ':kuantitas'   => $baris['kuantitas'],
            ':id_product'  => $baris['id_product'],
            ':id_user'     => $id_user,
        ]);
    }

    // COMMIT: seluruh riwayat dan efek trigger stok disimpan permanen
    $pdo->commit();

    kembali_dengan_pesan('flash_success', count($baris_bersih) . ' item barang masuk dari ' . $nama_vendor . ' berhasil dicatat.', $halaman_form);

} catch (PDOException $e) {
    // ROLLBACK: batalkan semua INSERT yang sudah lolos. inTransaction() dicek agar tidak error jika beginTransaction sendiri yang gagal
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // SQLSTATE PostgreSQL dipakai untuk menerjemahkan error DB menjadi pesan ramah user
    switch ($e->getCode()) {
        case '23503': // foreign_key_violation: id_product tidak ada di tabel products atau id_user tidak valid
            $pesan = 'Gagal: ada produk yang tidak terdaftar di master produk.';
            break;
        case '23514': // check_violation: chk_restocks_kuantitas_positif atau CHECK stok_aktual >= 0 menolak data
            $pesan = 'Gagal: kuantitas ditolak oleh aturan database.';
            break;
        default:
            // Detail teknis HANYA ke log server, jangan bocor ke user (nama tabel/kolom bisa membantu penyerang)
            error_log('[stock_in] ' . $e->getMessage());
            $pesan = 'Terjadi kesalahan sistem saat menyimpan barang masuk. Data tidak diubah.';
    }

    kembali_dengan_pesan('flash_error', $pesan, $halaman_form);

} catch (Throwable $e) {
    // Jaring pengaman untuk error non-PDO agar transaksi tidak menggantung
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[stock_in] ' . $e->getMessage());
    kembali_dengan_pesan('flash_error', 'Terjadi kesalahan tak terduga. Data tidak diubah.', $halaman_form);
}