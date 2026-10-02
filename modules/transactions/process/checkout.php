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