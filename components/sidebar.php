<?php
/**
 * KOMPONEN SIDEBAR DINAMIS
 * File: components/sidebar.php
 * Fungsi: Menampilkan menu navigasi aplikasi di sebelah kiri.
 * Aturan: Menu yang dirender harus disesuaikan (di-filter) berdasarkan $_SESSION['Role'] pengguna 
 * (super_admin, admin, kasir, staf_gudang).
 */
$user_role = $_SESSION['Role'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <span class="sidebar-section-title">MENU UTAMA</span>
        </div>

        <! -- Dashboard --!>
        <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="sidebar-link">
            <span class="sidebar-icon">▣</span>
            <span>Dashboard</span>
        </a>

        <?php if ($user_role === 'super_admin'): ?>
            <div class="sidebar-section">
                <span class="sidebar-section-title">MANAJEMEN</span>
            </div>
            <a
                href="<?= BASE_URL ?>modules/users/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">👤</span>
                <span>Manajemen Pengguna</span>
            </a>
            <?php elseif ($user_role === 'admin'): ?>

            <div class="sidebar-section">
                <span class="sidebar-section-title">MASTER DATA</span>
            </div>

            <a
                href="<?= BASE_URL ?>modules/products/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">📦</span>
                <span>Produk</span>
            </a>

            <a
                href="<?= BASE_URL ?>modules/categories/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">🏷️</span>
                <span>Kategori</span>
            </a>
            <div class="sidebar-section">
                <span class="sidebar-section-title">MANAJEMEN</span>
            </div>

            <a
                href="<?= BASE_URL ?>modules/receivables/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">💰</span>
                <span>Piutang</span>
            </a>

            <a
                href="<?= BASE_URL ?>modules/reports/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">📊</span>
                <span>Laporan</span>
            </a>


        <?php elseif ($user_role === 'kasir'): ?>

            <div class="sidebar-section">
                <span class="sidebar-section-title">KASIR</span>
            </div>

            <a
                href="<?= BASE_URL ?>modules/transactions/index.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">🛒</span>
                <span>Transaksi</span>
            </a>


        <?php elseif ($user_role === 'staf_gudang'): ?>

            <div class="sidebar-section">
                <span class="sidebar-section-title">GUDANG</span>
            </div>

            <a
                href="<?= BASE_URL ?>modules/inventory/stock_in.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">📥</span>
                <span>Barang Masuk</span>
            </a>

            <a
                href="<?= BASE_URL ?>modules/inventory/defects.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">⚠️</span>
                <span>Barang Defect</span>
            </a>

            <a
                href="<?= BASE_URL ?>modules/inventory/stock_opname.php"
                class="sidebar-link"
            >
                <span class="sidebar-icon">📋</span>
                <span>Stock Opname</span>
            </a>

        <?php endif; ?>


        <div class="sidebar-section">
            <span class="sidebar-section-title">AKUN</span>
        </div>

        <a
            href="<?= BASE_URL ?>modules/auth/process/logout.php"
            class="sidebar-link sidebar-logout"
        >
            <span class="sidebar-icon">↪</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>

