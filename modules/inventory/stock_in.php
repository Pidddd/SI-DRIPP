<?php
// modules/inventory/stock_in.php

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/auth_guard.php';

// Pastikan hanya role berwenang yang dapat mengakses halaman ini
auth_guard(['Super Admin', 'Admin', 'Staff Gudang']);

// Ambil daftar produk aktif dari Supabase untuk pilihan dropdown
$query_products = "SELECT p.id_product, p.nama_barang, p.stok_aktual, c.nama_category 
                   FROM products p 
                   LEFT JOIN category c ON p.categoryid_category = c.id_category 
                   ORDER BY p.nama_barang ASC";
$res_products = pg_query($conn, $query_products);

$page_title = "Input Restock Barang Masuk";
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="content-wrapper" style="padding: 24px;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 8px 0; color: #1e293b;">Penerimaan Barang Masuk (Restock)</h2>
        <p style="margin: 0; color: #64748b;">Catat penambahan stok fisik dari vendor langsung ke database gudang.</p>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="padding: 12px 16px; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; margin-bottom: 20px; border-radius: 4px;">
            <?= $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; max-width: 600px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form action="<?= BASE_URL; ?>modules/inventory/process/stock_in.php" method="POST">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Pilih Produk Sirup / Bubuk</label>
                <select name="id_product" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    <option value="">-- Pilih Produk --</option>
                    <?php if ($res_products): ?>
                        <?php while ($prod = pg_fetch_assoc($res_products)): ?>
                            <option value="<?= htmlspecialchars($prod['id_product']); ?>">
                                <?= htmlspecialchars($prod['nama_barang']); ?> (Stok saat ini: <?= (int)$prod['stok_aktual']; ?>) - [<?= htmlspecialchars($prod['nama_category'] ?? 'Umum'); ?>]
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Nama Pabrik / Vendor Penyuplai</label>
                <input type="text" name="nama_vendor" placeholder="Contoh: PT. Denali Prima, Pabrik Cikarang" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Jumlah Masuk (Botol / Satuan)</label>
                <input type="number" name="kuantitas" min="1" placeholder="Masukkan jumlah kuantitas" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" style="background: #0f766e; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Simpan Data Restock
                </button>
                <a href="<?= BASE_URL; ?>modules/inventory/index.php" style="padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; text-decoration: none; font-weight: 600; display: inline-block;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>