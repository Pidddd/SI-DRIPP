<?php
/**
 * KOMPONEN HEADER
 * File: components/header.php
 * Fungsi:
 * - Membuka struktur HTML
 * - Memanggil CSS utama
 * - Menampilkan navbar atas
 *
 * File ini dipanggil setelah bootstrap.php
 */

$page_title = $page_title ?? 'SI-DRIPP';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SI-DRIPP</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="app">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle">
                    ☰
                </button>
                <div class="brand">
                    <span class="brand-name">SI-DRIPP</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <span class="user-name">
                        <?= htmlspecialchars($_SESSION['username'] ?? 'User')?>
                    </span>
                    <span class="user-role">
                        <?= htmlspecialchars($_SESSION['Role'] ?? '') ?>
                    </span>
                </div>
            </div>
        </header>
        <div class="app-body">