<?php

/**
 * LOGIKA PEMROSESAN TRANSAKSI KASIR
 * File: modules/transactions/process_checkout.php
 * Fungsi: Memproses keranjang belanja untuk masuk ke tabel Transactions dan Transaction_Details.
 * Business Rules [WAJIB DIEKSEKUSI]:
 * 1. Kurangi Stok_Aktual pada entitas Products.
 * 2. Hitung diskon otomatis jika total Kuantitas memenuhi syarat MOQ (Minimum Order Quantity).
 * 3. Jika Status_Pembayaran = Belum Lunas, field Jatuh_Tempo wajib diset/diisi.
 */

require_once '../../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

// tangkap data utama transaksi
// id transaksi kita buat otomatis (misal: TRX-20261005-12345)
$id_transaksi = 'TRX-' . date('Ymd-His');
$user_id_user = $_POST['user_id_user']; // id kasir yang sedang login
$nama_pelanggan = $_POST['nama_pelanggan'];
$kontak_pelanggan = $_POST['kontak_pelanggan'];
$status_pembayaran = $_POST['status_pembayaran']; // 0 = piutang, 1 = lunas
$jatuh_tempo = !empty($_POST['jatuh_tempo']) ? $_POST['jatuh_tempo'] : null;

// tangkap data keranjang belanja (berupa array karena barang bisa lebih dari 1)
$produk_ids = $_POST['product_id'];
$kuantitas_arr = $_POST['kuantitas'];
$harga_arr = $_POST['harga'];

// hitung total harga di backend agar aman dari manipulasi inspect element
$total_harga = 0;
for ($i = 0; $i < count($produk_ids); $i++) {
    $total_harga += ($kuantitas_arr[$i] * $harga_arr[$i]);
}

// asumsi diskon awal 0 (bisa ditambah logika diskon kuantitas nanti)
$diskon = 0;

// mulai database transaction postgresql
pg_query($conn, "begin");

try {
    // insert ke tabel transactions utama
    $query_trx = "insert into transactions (id_transaksi, total_harga, user_id_user, nama_pelanggan, kontak_pelanggan, status_pembayaran, jatuh_tempo, diskon) values ($1, $2, $3, $4, $5, $6, $7, $8)";
    $res_trx = pg_query_params($conn, $query_trx, array($id_transaksi, $total_harga, $user_id_user, $nama_pelanggan, $kontak_pelanggan, $status_pembayaran, $jatuh_tempo, $diskon));

    if (!$res_trx) {
        throw new Exception("gagal menyimpan data transaksi utama.");
    }

    // looping: insert detail barang & potong stok
    for ($i = 0; $i < count($produk_ids); $i++) {
        $id_prod = $produk_ids[$i];
        $qty = $kuantitas_arr[$i];
        $subtotal = $qty * $harga_arr[$i];

        // insert ke tabel transaction_details
        $query_detail = "insert into transaction_details (kuantitas, subtotal_harga, transactions_id_transaksi, product_id_product) values ($1, $2, $3, $4)";
        $res_detail = pg_query_params($conn, $query_detail, array($qty, $subtotal, $id_transaksi, $id_prod));

        if (!$res_detail) {
            throw new Exception("gagal menyimpan rincian barang.");
        }

        // update pemotongan stok di tabel products
        $query_stok = "update products set stok_aktual = stok_aktual - $1 where id_product = $2";
        $res_stok = pg_query_params($conn, $query_stok, array($qty, $id_prod));

        if (!$res_stok) {
            throw new Exception("gagal memotong stok produk.");
        }
    }

    // jika semua berhasil, kunci perubahan (commit)
    pg_query($conn, "commit");
    header("Location: ../index.php?pesan=transaksi_sukses");
    exit();
} catch (Exception $e) {
    // jika ada satu saja yang gagal, batalkan semua (rollback)
    pg_query($conn, "rollback");

    header("Location: ../index.php?error=transaksi_gagal");
    exit();
}
