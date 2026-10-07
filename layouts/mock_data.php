<?php
/**
 * PROTOTYPE HELPERS & DUMMY DATA - MKP STORE
 * File: layouts/mock_data.php
 * Fungsi: Data dummy + fungsi bantuan prosedural untuk UI Mockup SI-DRIPP.
 * Identitas: CV. Mitra Kembar Pradipta (MKP Store) - Distributor Resmi HORECA Malang.
 */

/* ---------- Katalog Role ---------- */
function role_catalog() {
    return [
        'super_admin' => [
            'label' => 'Super Admin', 'short' => 'Owner',
            'nama'  => 'Budi Santoso', 'username' => 'budi.owner', 'id' => 1,
            'home'  => 'modules/dashboard/index.php',
            'desc'  => 'Akses penuh + data finansial',
        ],
        'admin' => [
            'label' => 'Admin', 'short' => 'Admin',
            'nama'  => 'Rina Wulandari', 'username' => 'rina.admin', 'id' => 2,
            'home'  => 'modules/dashboard/index.php',
            'desc'  => 'Operasional & stok tanpa omzet/profit',
        ],
        'kasir' => [
            'label' => 'Kasir', 'short' => 'Kasir',
            'nama'  => 'Dimas Prasetyo', 'username' => 'dimas.kasir', 'id' => 3,
            'home'  => 'modules/transactions/index.php',
            'desc'  => 'Transaksi penjualan & POS kasir',
        ],
        'staf_gudang' => [
            'label' => 'Staf Gudang', 'short' => 'Gudang',
            'nama'  => 'Agus Hermawan', 'username' => 'agus.gudang', 'id' => 5,
            'home'  => 'modules/inventory/index.php',
            'desc'  => 'Stok fisik gudang & logistik',
        ],
    ];
}

/* ---------- Format & Util ---------- */
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function rupiah_short($n) {
    if ($n >= 1000000) return 'Rp ' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . ' jt';
    if ($n >= 1000)    return 'Rp ' . number_format($n / 1000, 0, ',', '.') . ' rb';
    return 'Rp ' . $n;
}

function tgl_id($ts = null) {
    $ts = $ts ?: time();
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $hari[(int)date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function salam() {
    $h = (int)date('G');
    if ($h < 11) return 'Selamat pagi';
    if ($h < 15) return 'Selamat siang';
    if ($h < 18) return 'Selamat sore';
    return 'Selamat malam';
}

function role_badge($role) {
    $roles = role_catalog();
    $label = isset($roles[$role]) ? $roles[$role]['label'] : $role;
    return '<span class="badge role-' . e($role) . '"><i class="dot"></i>' . e($label) . '</span>';
}

function role_url($role) {
    $q = $_GET;
    $q['as'] = $role;
    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($q);
}

function initials($nama) {
    $parts = preg_split('/\s+/', trim($nama));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    return $i;
}

/* ---------- Ikon (inline SVG, gaya outline) ---------- */
function icon($name, $size = 20) {
    $p = [
        'home'     => '<path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'cart'     => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'box'      => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.3 7L12 12l8.7-5M12 22V12"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'report'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'wallet'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h3"/>',
        'alert'    => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'trash'    => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'search'   => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'trend'    => '<path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/>',
        'truck'    => '<rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'check'    => '<path d="M20 6L9 17l-5-5"/>',
        'x'        => '<path d="M18 6L6 18M6 6l12 12"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'key'      => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3L21 2M16 7l3 3"/>',
        'tag'      => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'clipboard'=> '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 14l2 2 4-4"/>',
        'coin'     => '<circle cx="12" cy="12" r="10"/><path d="M14.8 9a2.8 2.8 0 0 0-2.8-1.5c-1.7 0-3 .9-3 2.2s1.3 1.9 3 2.3 3 1 3 2.3-1.3 2.2-3 2.2A2.8 2.8 0 0 1 9.2 15M12 6v2M12 16v2"/>',
        'pie'      => '<path d="M21.2 15.9A10 10 0 1 1 8 2.8"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'chevron'  => '<path d="M6 9l6 6 6-6"/>',
        'flask'    => '<path d="M9 2h6M10 2v6L4.5 18.5A2 2 0 0 0 6.3 21h11.4a2 2 0 0 0 1.8-2.5L14 8V2"/><path d="M7 15h10"/>',
        'eye'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'power'    => '<path d="M18.4 6.6a9 9 0 1 1-12.8 0M12 2v10"/>',
        'whatsapp' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
    ];
    $d = isset($p[$name]) ? $p[$name] : '';
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/* ---------- Ilustrasi Produk (SVG mockup foto) ---------- */
function bottle_svg($c1, $c2, $label, $sub = '760 ml', $type = 'syrup', $brand = 'DRIPP') {
    static $n = 0;
    $n++;
    $id = 'bg' . $n;
    $brand = strtoupper(e($brand));
    $label = strtoupper(e($label));
    $sub   = e($sub);
    $o  = '<svg viewBox="0 0 120 220" class="bottle" role="img" aria-label="' . $brand . ' ' . $label . '">';
    $o .= '<defs>';
    $o .= '<linearGradient id="' . $id . '" x1="0" x2="1"><stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '"/></linearGradient>';
    $o .= '<linearGradient id="' . $id . 's" x1="0" x2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".25" stop-color="#fff" stop-opacity=".55"/><stop offset=".45" stop-color="#fff" stop-opacity="0"/></linearGradient>';
    $o .= '</defs>';
    $o .= '<ellipse cx="60" cy="213" rx="40" ry="6" fill="#062329" opacity=".12"/>';

    if ($type === 'powder') {
        // Pouch powder model
        $body = 'M26 36 h68 l4 14 v150 a8 8 0 0 1 -8 8 H30 a8 8 0 0 1 -8 -8 V50z';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . ')"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . 's)"/>';
        $o .= '<rect x="22" y="32" width="76" height="12" rx="3" fill="#0A3238" opacity=".9"/>';
        $o .= '<path d="M22 38h76" stroke="#fff" stroke-opacity=".4" stroke-dasharray="3 3"/>';
        $o .= '<rect x="32" y="92" width="56" height="76" rx="6" fill="#fff" opacity=".96"/>';
        $o .= '<text x="60" y="115" text-anchor="middle" font-size="11" font-weight="900" fill="#088395" font-family="Inter,Arial,sans-serif" letter-spacing="1">' . $brand . '</text>';
        $o .= '<text x="60" y="132" text-anchor="middle" font-size="7.5" font-weight="700" fill="' . $c2 . '" font-family="Inter,Arial,sans-serif">' . $label . '</text>';
        $o .= '<text x="60" y="146" text-anchor="middle" font-size="6" font-weight="700" fill="#64748B" font-family="Inter,Arial,sans-serif">POWDER</text>';
        $o .= '<text x="60" y="158" text-anchor="middle" font-size="6" fill="#088395" font-weight="600" font-family="Inter,Arial,sans-serif">' . $sub . '</text>';
    } elseif ($type === 'can') {
        // Can 330ml (Ramoe)
        $body = 'M34 24 h52 a6 6 0 0 1 6 6 v160 a6 6 0 0 1 -6 6 H34 a6 6 0 0 1 -6 -6 V30 a6 6 0 0 1 6 -6z';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . ')"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . 's)"/>';
        $o .= '<rect x="36" y="20" width="48" height="6" rx="2" fill="#E2E8F0"/>';
        $o .= '<rect x="36" y="196" width="48" height="5" rx="2" fill="#CBD5E1"/>';
        $o .= '<rect x="36" y="80" width="48" height="80" rx="4" fill="#fff" opacity=".95"/>';
        $o .= '<text x="60" y="112" text-anchor="middle" font-size="13" font-weight="900" fill="#088395" font-family="Inter,Arial,sans-serif" letter-spacing="1.5">' . $brand . '</text>';
        $o .= '<text x="60" y="128" text-anchor="middle" font-size="7.5" font-weight="700" fill="' . $c2 . '" font-family="Inter,Arial,sans-serif">' . $label . '</text>';
        $o .= '<text x="60" y="142" text-anchor="middle" font-size="6.5" font-weight="600" fill="#64748B" font-family="Inter,Arial,sans-serif">' . $sub . '</text>';
    } elseif ($type === 'pulp') {
        // Fruit Pulp Bottle with wider shoulder
        $body = 'M46 44 h28 v14 c0 8 26 12 26 32 v106 a8 8 0 0 1 -8 8 H28 a8 8 0 0 1 -8 -8 V90 c0 -20 26 -24 26 -32z';
        $o .= '<rect x="48" y="6" width="24" height="12" rx="3" fill="#0A3238"/>';
        $o .= '<rect x="54" y="18" width="12" height="18" rx="2" fill="#088395"/>';
        $o .= '<rect x="44" y="34" width="32" height="12" rx="3" fill="#062329"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . ')"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . 's)"/>';
        $o .= '<rect x="32" y="104" width="56" height="70" rx="6" fill="#fff" opacity=".96"/>';
        $o .= '<text x="60" y="126" text-anchor="middle" font-size="11" font-weight="900" fill="#088395" font-family="Inter,Arial,sans-serif" letter-spacing="1">' . $brand . '</text>';
        $o .= '<text x="60" y="142" text-anchor="middle" font-size="7.5" font-weight="700" fill="' . $c2 . '" font-family="Inter,Arial,sans-serif">' . $label . '</text>';
        $o .= '<text x="60" y="154" text-anchor="middle" font-size="6" font-weight="700" fill="#088395" font-family="Inter,Arial,sans-serif">FRUIT PULP</text>';
        $o .= '<text x="60" y="164" text-anchor="middle" font-size="6" fill="#64748B" font-family="Inter,Arial,sans-serif">' . $sub . '</text>';
    } else {
        // Syrup 760ml & Multibev 1L
        $body = 'M50 48 h26 v14 c0 10 22 14 22 34 v100 a10 10 0 0 1 -10 10 H38 a10 10 0 0 1 -10 -10 V96 c0 -20 22 -24 22 -34z';
        $o .= '<rect x="50" y="4" width="26" height="9" rx="3" fill="#062329"/>';
        $o .= '<rect x="57" y="12" width="12" height="22" rx="2" fill="#088395"/>';
        $o .= '<rect x="47" y="33" width="32" height="15" rx="3" fill="#041F24"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . ')"/>';
        $o .= '<path d="' . $body . '" fill="url(#' . $id . 's)"/>';
        $o .= '<rect x="35" y="106" width="50" height="66" rx="6" fill="#fff" opacity=".96"/>';
        $o .= '<text x="60" y="128" text-anchor="middle" font-size="11" font-weight="900" fill="#088395" font-family="Inter,Arial,sans-serif" letter-spacing="1">' . $brand . '</text>';
        $o .= '<text x="60" y="144" text-anchor="middle" font-size="7" font-weight="700" fill="' . $c2 . '" font-family="Inter,Arial,sans-serif">' . $label . '</text>';
        $o .= '<text x="60" y="157" text-anchor="middle" font-size="6" font-weight="700" fill="#06616E" font-family="Inter,Arial,sans-serif">SYRUP</text>';
        $o .= '<text x="60" y="166" text-anchor="middle" font-size="6" fill="#64748B" font-family="Inter,Arial,sans-serif">' . $sub . '</text>';
    }
    $o .= '</svg>';
    return $o;
}

/* ---------- Data Dummy Produk (Katalog Resmi MKP Store) ---------- */
/* 
 * 1 Karton = 12 Botol (Stok Kritis jika < 2 Karton / < 24 Botol)
 * Sesuai Pricelist Resmi:
 * 1. DRIPP Syrup 760ml (Rp 111.000): Caramel, Vanilla, Hazelnut, Apel Malang, Pandan, Lychee
 * 2. DRIPP Fruit Pulp (Rp 166.500): Blueberry, Harum Manis
 * 3. Multibev Syrup 1L (Rp 78.800): Butterscotch, Strawberry
 * 4. DRIPP Powder 760g (Rp 156.000): Red Velvet, Chocolate
 * 5. Ramoe (Rp 12.500)
 */
function mock_products() {
    return [
        // --- 1. DRIPP Syrup 760ml (Rp 111.000) ---
        ['id' => 'DRP-CRM-760', 'nama' => 'DRIPP Caramel',            'brand' => 'DRIPP',    'flavor' => 'Caramel',      'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 36, 'min' => 24, 'c1' => '#F0B35A', 'c2' => '#B8651B', 'rak' => 'A-01', 'upd' => 'Hari ini, 08:30'],
        ['id' => 'DRP-VNL-760', 'nama' => 'DRIPP Vanilla',            'brand' => 'DRIPP',    'flavor' => 'Vanilla',      'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 48, 'min' => 24, 'c1' => '#F9E8B6', 'c2' => '#D4A843', 'rak' => 'A-02', 'upd' => 'Hari ini, 08:30'],
        ['id' => 'DRP-HZN-760', 'nama' => 'DRIPP Hazelnut',           'brand' => 'DRIPP',    'flavor' => 'Hazelnut',     'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 16, 'min' => 24, 'c1' => '#B98458', 'c2' => '#7A4B2A', 'rak' => 'A-03', 'upd' => 'Kemarin, 14:15'],
        ['id' => 'DRP-APL-760', 'nama' => 'DRIPP Apel Malang',        'brand' => 'DRIPP',    'flavor' => 'Apel Malang',  'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 28, 'min' => 24, 'c1' => '#A4DE63', 'c2' => '#5B9B26', 'rak' => 'A-04', 'upd' => 'Hari ini, 09:10'],
        ['id' => 'DRP-PDN-760', 'nama' => 'DRIPP Pandan',             'brand' => 'DRIPP',    'flavor' => 'Pandan',       'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 12, 'min' => 24, 'c1' => '#84D49D', 'c2' => '#278C48', 'rak' => 'A-05', 'upd' => 'Kemarin, 16:00'],
        ['id' => 'DRP-LYC-760', 'nama' => 'DRIPP Lychee',             'brand' => 'DRIPP',    'flavor' => 'Lychee',       'cat' => 'DRIPP Syrup', 'satuan' => 'Botol 760ml', 'sub' => '760 ml', 'beli' => 88000,  'jual' => 111000, 'stok' => 30, 'min' => 24, 'c1' => '#FAD2DC', 'c2' => '#DC6F8C', 'rak' => 'A-06', 'upd' => 'Hari ini, 07:45'],

        // --- 2. DRIPP Fruit Pulp (Rp 166.500) ---
        ['id' => 'DRP-PLP-BLU', 'nama' => 'DRIPP Fruit Pulp Blueberry','brand' => 'DRIPP',    'flavor' => 'Blueberry',    'cat' => 'Fruit Pulp',  'satuan' => 'Botol 760ml', 'sub' => 'Pulp 760ml', 'beli' => 132000, 'jual' => 166500, 'stok' => 18, 'min' => 20, 'c1' => '#A5B4FC', 'c2' => '#4338CA', 'rak' => 'B-01', 'upd' => '5 Okt, 11:20'],
        ['id' => 'DRP-PLP-HNM', 'nama' => 'DRIPP Fruit Pulp Harum Manis','brand' => 'DRIPP',  'flavor' => 'Harum Manis',  'cat' => 'Fruit Pulp',  'satuan' => 'Botol 760ml', 'sub' => 'Pulp 760ml', 'beli' => 132000, 'jual' => 166500, 'stok' => 24, 'min' => 20, 'c1' => '#FDE047', 'c2' => '#D97706', 'rak' => 'B-02', 'upd' => 'Hari ini, 10:00'],

        // --- 3. Multibev Syrup 1L (Rp 78.800) ---
        ['id' => 'MBV-BTS-1000', 'nama' => 'Multibev Syrup Butterscotch 1L', 'brand' => 'Multibev', 'flavor' => 'Butterscotch', 'cat' => 'Multibev 1L', 'satuan' => 'Botol 1 Liter', 'sub' => '1000 ml', 'beli' => 63000, 'jual' => 78800, 'stok' => 40, 'min' => 24, 'c1' => '#FDE68A', 'c2' => '#CA8A04', 'rak' => 'C-01', 'upd' => 'Hari ini, 08:00'],
        ['id' => 'MBV-STR-1000', 'nama' => 'Multibev Syrup Strawberry 1L',   'brand' => 'Multibev', 'flavor' => 'Strawberry',   'cat' => 'Multibev 1L', 'satuan' => 'Botol 1 Liter', 'sub' => '1000 ml', 'beli' => 63000, 'jual' => 78800, 'stok' => 8,  'min' => 24, 'c1' => '#FCA5A5', 'c2' => '#DC2626', 'rak' => 'C-02', 'upd' => 'Kemarin, 17:30'],

        // --- 4. DRIPP Powder 760g (Rp 156.000) ---
        ['id' => 'DRP-PWD-RDV', 'nama' => 'DRIPP Powder Red Velvet 760g', 'brand' => 'DRIPP', 'flavor' => 'Red Velvet',  'cat' => 'Powder',      'satuan' => 'Pouch 760g',  'sub' => '760 gr', 'beli' => 125000, 'jual' => 156000, 'stok' => 22, 'min' => 20, 'c1' => '#F87171', 'c2' => '#991B1B', 'rak' => 'D-01', 'upd' => '6 Okt, 15:40'],
        ['id' => 'DRP-PWD-CHO', 'nama' => 'DRIPP Powder Chocolate 760g',  'brand' => 'DRIPP', 'flavor' => 'Chocolate',   'cat' => 'Powder',      'satuan' => 'Pouch 760g',  'sub' => '760 gr', 'beli' => 125000, 'jual' => 156000, 'stok' => 35, 'min' => 20, 'c1' => '#B45309', 'c2' => '#451A03', 'rak' => 'D-02', 'upd' => 'Hari ini, 08:30'],

        // --- 5. Ramoe (Rp 12.500) ---
        ['id' => 'RMO-CAN-330', 'nama' => 'Ramoe Original 330ml',          'brand' => 'Ramoe', 'flavor' => 'Ramoe',       'cat' => 'Ramoe',       'satuan' => 'Can 330ml',   'sub' => '330 ml', 'beli' => 9800,   'jual' => 12500,  'stok' => 72, 'min' => 24, 'c1' => '#FDE047', 'c2' => '#088395', 'rak' => 'E-01', 'upd' => 'Hari ini, 09:00'],
    ];
}

function stok_status($p) {
    if ($p['stok'] <= 0)          return ['Habis', 'danger'];
    if ($p['stok'] < $p['min'])   return ['Menipis (< 2 Karton)', 'danger'];
    if ($p['stok'] <= ($p['min'] + 6)) return ['Waspada', 'warn'];
    return ['Aman', 'success'];
}

function product_art($p) {
    $type = 'syrup';
    if (isset($p['cat'])) {
        if ($p['cat'] === 'Powder') $type = 'powder';
        elseif ($p['cat'] === 'Fruit Pulp') $type = 'pulp';
        elseif ($p['cat'] === 'Ramoe') $type = 'can';
    }
    $brand = isset($p['brand']) ? $p['brand'] : 'DRIPP';
    return bottle_svg($p['c1'], $p['c2'], $p['flavor'], $p['sub'], $type, $brand);
}
