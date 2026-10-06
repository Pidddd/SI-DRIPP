<?php

/**
 * backend modul kasir - proses checkout transaksi
 * file: modules/transactions/process/checkout.php
 */

require_once '../../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$id_transaksi = 'TRX-' . date('Ymd-His');
$userid_user = $_POST['userid_user']; // disesuaikan dengan kolom database baru
$nama_pelanggan = $_POST['nama_pelanggan'];
$kontak_pelanggan = $_POST['kontak_pelanggan'];
$status_pembayaran = $_POST['status_pembayaran'];
$jatuh_tempo = !empty($_POST['jatuh_tempo']) ? $_POST['jatuh_tempo'] : null;

$produk_ids = $_POST['product_id'];
$kuantitas_arr = $_POST['kuantitas'];
$harga_arr = $_POST['harga'];

$total_harga = 0;
for ($i = 0; $i < count($produk_ids); $i++) {
    $total_harga += ($kuantitas_arr[$i] * $harga_arr[$i]);
}

$diskon = 0;

// mulai database transaction
pg_query($conn, "begin");

try {
    // insert ke tabel transactions
    $query_trx = "insert into transactions (id_transaksi, total_harga, userid_user, nama_pelanggan, kontak_pelanggan, status_pembayaran, jatuh_tempo, diskon) values ($1, $2, $3, $4, $5, $6, $7, $8)";
    $res_trx = pg_query_params($conn, $query_trx, array($id_transaksi, $total_harga, $userid_user, $nama_pelanggan, $kontak_pelanggan, $status_pembayaran, $jatuh_tempo, $diskon));

    if (!$res_trx) {
        throw new Exception("gagal menyimpan data transaksi utama.");
    }

    // looping insert rincian barang (stok tidak perlu di-update di sini karena sudah diurus trigger database)
    for ($i = 0; $i < count($produk_ids); $i++) {
        $id_prod = $produk_ids[$i];
        $qty = $kuantitas_arr[$i];
        $subtotal = $qty * $harga_arr[$i];

        // insert ke transaction_details dengan nama kolom yang baru
        $query_detail = "insert into transaction_details (kuantitas, subtotal_harga, transactionsid_transaksi, productsid_product) values ($1, $2, $3, $4)";
        $res_detail = pg_query_params($conn, $query_detail, array($qty, $subtotal, $id_transaksi, $id_prod));

        if (!$res_detail) {
            throw new Exception("gagal menyimpan rincian barang atau stok tidak cukup.");
        }
    }

    pg_query($conn, "commit");
    header("Location: ../index.php?pesan=transaksi_sukses");
    exit();
} catch (Exception $e) {
    pg_query($conn, "rollback");
    header("Location: ../index.php?error=transaksi_gagal");
    exit();
}
