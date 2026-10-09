<?php
// Bootstrap dipanggil paling awal (session, BASE_PATH, koneksi DB, BASE_URL). __DIR__ membuat path tidak bergantung pada working directory
require_once __DIR__ . '/../../config/bootstrap.php';

// Halaman frontend tujuan redirect (sesuaikan dengan struktur folder UInya)
$halaman_form = BASE_URL . 'inventory/defects.php';

// Helper: simpan pesan ke Session lalu redirect. exit wajib agar script berhenti setelah header Location
function kembali_dengan_pesan(string $kunci, string $pesan, string $url): void
{
    $_SESSION[$kunci] = $pesan;
    header('Location: ' . $url);
    exit;
}

// Guard 1: hanya menerima POST, bukan akses langsung via URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali_dengan_pesan('flash_error', 'Akses tidak valid.', $halaman_form);
}

// Guard 2: wajib login. ID pengguna diambil dari Session, BUKAN dari form, agar tidak bisa dipalsukan
if (empty($_SESSION['id_user'])) {
    kembali_dengan_pesan('flash_error', 'Sesi berakhir, silakan login kembali.', $halaman_form);
}
$id_user = (int) $_SESSION['id_user'];

// Ambil input: tiga array paralel (produk, kuantitas, keterangan). Baris ke-n di tiap array = satu laporan rusak
$daftar_produk = $_POST['id_product'] ?? [];
$daftar_qty = $_POST['kuantitas'] ?? [];
$daftar_ket = $_POST['keterangan_rusak'] ?? [];

// Validasi lapis 1: ketiganya harus array, tidak kosong, dan jumlah barisnya sama persis
if (!is_array($daftar_produk) || !is_array($daftar_qty) || !is_array($daftar_ket)
    || count($daftar_produk) === 0
    || count($daftar_produk) !== count($daftar_qty)
    || count($daftar_produk) !== count($daftar_ket)) {
    kembali_dengan_pesan('flash_error', 'Daftar barang rusak tidak valid atau kosong.', $halaman_form);
}

// Normalisasi + validasi tiap baris. Satu baris salah = seluruh request ditolak (all-or-nothing)
$baris_bersih = [];
foreach ($daftar_produk as $i => $id_produk) {
    $no = $i + 1; // Nomor baris ramah-user untuk pesan error

    $id_produk = trim((string) $id_produk);
    $keterangan = trim((string) ($daftar_ket[$i] ?? ''));

    // FILTER_VALIDATE_INT menolak "abc", "5.5", "1e3"; min_range=1 menolak 0 dan negatif
    $qty = filter_var($daftar_qty[$i] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    // id_product varchar(20): tidak boleh kosong atau melebihi panjang kolom
    if ($id_produk === '' || mb_strlen($id_produk) > 20) {
        kembali_dengan_pesan('flash_error', "Baris ke-$no: produk wajib dipilih.", $halaman_form);
    }
    if ($qty === false) {
        kembali_dengan_pesan('flash_error', "Baris ke-$no: kuantitas harus bilangan bulat lebih dari 0.", $halaman_form);
    }
    // Keterangan wajib (bukti audit) dan dibatasi 255 karakter sesuai varchar(255), supaya tidak memicu error "value too long"
    if ($keterangan === '' || mb_strlen($keterangan) > 255) {
        kembali_dengan_pesan('flash_error', "Baris ke-$no: keterangan kerusakan wajib diisi (maksimal 255 karakter).", $halaman_form);
    }

    $baris_bersih[] = [
        'id_product' => $id_produk,
        'kuantitas'  => $qty,
        'keterangan' => $keterangan,
    ];
}

// Urutkan berdasarkan id_product agar semua transaksi mengunci baris produk dengan urutan SAMA, sehingga tidak terjadi deadlock antar petugas
usort($baris_bersih, fn($a, $b) => strcmp($a['id_product'], $b['id_product']));

try {
    // Wajib ERRMODE_EXCEPTION agar kegagalan constraint/trigger dilempar sebagai exception, bukan lolos diam-diam
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // BEGIN: semua INSERT di bawah satu kesatuan. Satu gagal (termasuk stok tidak cukup), semuanya batal
    $pdo->beginTransaction();

    // Prepared statement dibuat SEKALI, dieksekusi berulang: aman dari SQL Injection dan efisien.
    // waktu_lapor tidak diisi karena DEFAULT current_timestamp. Kolom stok TIDAK disentuh, itu tugas trigger_kurangi_stok_rusak
    $stmt = $pdo->prepare(
        'INSERT INTO defects (kuantitas, keterangan_rusak, productsid_product, userid_user)
         VALUES (:kuantitas, :keterangan, :id_product, :id_user)'
    );

    foreach ($baris_bersih as $baris) {
        // Tiap execute memicu trigger AFTER INSERT yang mengurangi products.stok_aktual; CHECK stok >= 0 menjadi penjaga terakhir
        $stmt->execute([
            ':kuantitas'  => $baris['kuantitas'],
            ':keterangan' => $baris['keterangan'],
            ':id_product' => $baris['id_product'],
            ':id_user'    => $id_user,
        ]);
    }

    // COMMIT: riwayat rusak dan pengurangan stok disimpan permanen bersamaan
    $pdo->commit();

    kembali_dengan_pesan('flash_success', count($baris_bersih) . ' laporan barang rusak berhasil dicatat dan stok telah disesuaikan.', $halaman_form);

} catch (PDOException $e) {
    // ROLLBACK: batalkan semua INSERT yang sudah lolos. inTransaction() dicek agar aman jika beginTransaction sendiri yang gagal
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Pesan asli driver (berisi nama constraint) dipakai HANYA untuk membedakan jenis error, tidak ditampilkan ke user
    $pesan_driver = $e->errorInfo[2] ?? $e->getMessage();

    switch ($e->getCode()) {
        case '23514': // check_violation: ada DUA kemungkinan penyebab, dibedakan lewat nama constraint
            if (strpos($pesan_driver, 'products_stok_aktual_check') !== false) {
                // CHECK stok >= 0 menolak: jumlah rusak melebihi stok sistem
                $pesan = 'Gagal: jumlah barang rusak melebihi stok yang tercatat di sistem. Periksa stok atau lakukan stock opname terlebih dahulu.';
            } elseif (strpos($pesan_driver, 'chk_defects_kuantitas_positif') !== false) {
                $pesan = 'Gagal: kuantitas harus lebih dari 0.';
            } elseif (strpos($pesan_driver, 'chk_defects_keterangan_wajib') !== false) {
                $pesan = 'Gagal: keterangan kerusakan wajib diisi.';
            } else {
                error_log('[defects] check_violation tak dikenal: ' . $pesan_driver);
                $pesan = 'Gagal: data ditolak oleh aturan database.';
            }
            break;
        case '23503': // foreign_key_violation: id_product tidak ada di master produk atau id_user tidak valid
            $pesan = 'Gagal: ada produk yang tidak terdaftar di master produk.';
            break;
        case '40P01': // deadlock_detected: sangat jarang karena baris sudah di-sort, tapi tetap ditangani; user cukup mengulang
            $pesan = 'Sistem sedang sibuk memproses data lain. Silakan coba kirim ulang.';
            break;
        default:
            // Detail teknis hanya ke log server, jangan bocor ke user
            error_log('[defects] ' . $pesan_driver);
            $pesan = 'Terjadi kesalahan sistem saat menyimpan laporan barang rusak. Data tidak diubah.';
    }

    kembali_dengan_pesan('flash_error', $pesan, $halaman_form);

} catch (Throwable $e) {
    // Jaring pengaman untuk error non-PDO agar transaksi tidak menggantung
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[defects] ' . $e->getMessage());
    kembali_dengan_pesan('flash_error', 'Terjadi kesalahan tak terduga. Data tidak diubah.', $halaman_form);
}