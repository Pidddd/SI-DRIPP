<?php
/**
 * UI MODUL MANAJEMEN PIUTANG & TOP (TERM OF PAYMENT)
 * File: modules/piutang/index.php
 * Fungsi: Monitoring piutang pelanggan kafe/resto HORECA, peringatan jatuh tempo,
 *         dan pengiriman draf pesan penagihan otomatis via WhatsApp (wa.me).
 * SOP: Urutan default piutang otomatis diprioritaskan dari yang paling dekat/lewat tempo.
 */

$root       = '../../';
$page_title = 'Manajemen Piutang';
$page_sub   = 'Pengawasan Term of Payment (TOP) & penagihan tagihan kafe/resto';
$active     = 'piutang';
$allowed    = ['super_admin', 'admin'];

require $root . 'layouts/header.php';

// Data dummy piutang pelanggan MKP Store
$piutang_raw = [
    [
        'id'            => 'PIU-202610-001',
        'trx_no'        => 'TRX-20260923-009',
        'pelanggan'     => 'Kopi Titik Koma Suhat',
        'kontak'        => 'Dimas Aditya (Owner)',
        'wa'            => '081234567890',
        'tgl_trx'       => '2026-09-23',
        'tempo_hari'    => 14,
        'jatuh_tempo'   => '2026-10-07', // Hari ini
        'total'         => 3429000,
        'terbayar'      => 0,
        'sisa'          => 3429000,
        'items'         => 'DRIPP Caramel 12 btl, DRIPP Hazelnut 12 btl, Multibev 12 btl',
        'status'        => 'tempo_today',
        'status_label'  => 'Jatuh Tempo HARI INI',
    ],
    [
        'id'            => 'PIU-202609-004',
        'trx_no'        => 'TRX-20260920-003',
        'pelanggan'     => 'Kopi Janji Jiwa Soekarno Hatta',
        'kontak'        => 'Rendi Pratama',
        'wa'            => '085799881122',
        'tgl_trx'       => '2026-09-20',
        'tempo_hari'    => 14,
        'jatuh_tempo'   => '2026-10-04', // Lewat 3 hari
        'total'         => 2664000,
        'terbayar'      => 0,
        'sisa'          => 2664000,
        'items'         => 'DRIPP Vanilla 24 btl (Diskon Grosir 2 Karton)',
        'status'        => 'overdue',
        'status_label'  => 'Lewat Tempo (3 Hari)',
    ],
    [
        'id'            => 'PIU-202610-002',
        'trx_no'        => 'TRX-20260930-011',
        'pelanggan'     => 'Hotel Savana Malang',
        'kontak'        => 'Ibu Mariana (Purchasing)',
        'wa'            => '081198765432',
        'tgl_trx'       => '2026-09-30',
        'tempo_hari'    => 14,
        'jatuh_tempo'   => '2026-10-14', // 7 hari lagi
        'total'         => 5450000,
        'terbayar'      => 1000000,
        'sisa'          => 4450000,
        'items'         => 'DRIPP Powder Chocolate 20 pch, DRIPP Lychee 18 btl',
        'status'        => 'active',
        'status_label'  => 'Tempo 7 Hari Lagi',
    ],
    [
        'id'            => 'PIU-202610-003',
        'trx_no'        => 'TRX-20261002-005',
        'pelanggan'     => 'Kala Senja Coffee & Eatery',
        'kontak'        => 'Bpk. Fajar',
        'wa'            => '082133445566',
        'tgl_trx'       => '2026-10-02',
        'tempo_hari'    => 14,
        'jatuh_tempo'   => '2026-10-16', // 9 hari lagi
        'total'         => 1998000,
        'terbayar'      => 0,
        'sisa'          => 1998000,
        'items'         => 'DRIPP Apel Malang 12 btl, DRIPP Pandan 6 btl',
        'status'        => 'active',
        'status_label'  => 'Tempo 9 Hari Lagi',
    ],
    [
        'id'            => 'PIU-202610-004',
        'trx_no'        => 'TRX-20261005-018',
        'pelanggan'     => 'Resto Taman Indah Tlogomas',
        'kontak'        => 'Pak Bambang',
        'wa'            => '081377889900',
        'tgl_trx'       => '2026-10-05',
        'tempo_hari'    => 30,
        'jatuh_tempo'   => '2026-11-04', // 28 hari lagi
        'total'         => 6660000,
        'terbayar'      => 0,
        'sisa'          => 6660000,
        'items'         => 'DRIPP Fruit Pulp Harum Manis 24 btl, Ramoe 48 can',
        'status'        => 'active',
        'status_label'  => 'Tempo 28 Hari Lagi',
    ],
    [
        'id'            => 'PIU-202609-001',
        'trx_no'        => 'TRX-20260910-002',
        'pelanggan'     => 'Cafe Arunika Ijen',
        'kontak'        => 'Rizky Barista',
        'wa'            => '082155667788',
        'tgl_trx'       => '2026-09-10',
        'tempo_hari'    => 14,
        'jatuh_tempo'   => '2026-09-24',
        'total'         => 2220000,
        'terbayar'      => 2220000,
        'sisa'          => 0,
        'items'         => 'DRIPP Caramel 12 btl, DRIPP Vanilla 8 btl',
        'status'        => 'paid',
        'status_label'  => 'Lunas (24 Sep 2026)',
    ],
];

// URUTKAN OTOMATIS: Jatuh tempo paling dekat / kritis / overdue di bagian paling atas
$today_str = '2026-10-07';
$today_ts = strtotime($today_str);

usort($piutang_raw, function($a, $b) use ($today_ts) {
    // Jika salah satu lunas, taruh di bawah
    if ($a['status'] === 'paid' && $b['status'] !== 'paid') return 1;
    if ($b['status'] === 'paid' && $a['status'] !== 'paid') return -1;
    
    // Urutkan berdasarkan timestamp jatuh tempo (terkecil/paling lampau/paling dekat di atas)
    $ts_a = strtotime($a['jatuh_tempo']);
    $ts_b = strtotime($b['jatuh_tempo']);
    return $ts_a - $ts_b;
});

// Hitung total statistik
$total_piutang = 0;
$total_overdue = 0;
$count_overdue = 0;
$total_week    = 0;
$count_week    = 0;

foreach ($piutang_raw as $p) {
    if ($p['status'] !== 'paid') {
        $total_piutang += $p['sisa'];
        $ts = strtotime($p['jatuh_tempo']);
        if ($ts <= $today_ts) {
            $total_overdue += $p['sisa'];
            $count_overdue++;
        } elseif ($ts <= ($today_ts + (7 * 86400))) {
            $total_week += $p['sisa'];
            $count_week++;
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Manajemen Piutang & Term of Payment (TOP)</h2>
            <p class="text-xs text-slate-500 mt-1">Daftar penagihan kredit mitra HORECA &bull; Diurutkan otomatis dari jatuh tempo terdekat</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge badge-light text-xs font-semibold px-3 py-1.5 border border-slate-200">
                Hari Ini: <?= tgl_id(strtotime($today_str)) ?>
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak Rekap Piutang
            </button>
        </div>
    </div>

    <!-- Bento Stats Piutang -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 font-bold" style="background:#EBF7F8; color:#088395;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Total Piutang Berjalan</div>
                <div class="text-lg font-bold text-slate-800"><?= rupiah($total_piutang) ?></div>
            </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 font-bold" style="background:#FEF2F2; color:#EF4444;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Kritis / Jatuh Tempo</div>
                <div class="text-lg font-bold text-rose-600"><?= rupiah($total_overdue) ?> <span class="text-xs font-normal">(<?= $count_overdue ?> Mitra)</span></div>
            </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 font-bold" style="background:#FFFBEB; color:#D97706;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Tempo 7 Hari Kedepan</div>
                <div class="text-lg font-bold text-amber-600"><?= rupiah($total_week) ?> <span class="text-xs font-normal">(<?= $count_week ?> Mitra)</span></div>
            </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold" style="background:#ECFDF5; color:#10B981;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Tertagih Bulan Ini</div>
                <div class="text-lg font-bold text-emerald-600">Rp 2.220.000</div>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative w-full">
                <input type="text" id="piutangSearch" placeholder="Cari nama mitra kafe, nomor nota, atau kontak..." class="form-control text-sm w-full pl-9" onkeyup="filterPiutangTable()">
                <svg class="absolute left-3 top-2.5 text-slate-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">Filter Status:</span>
            <button type="button" class="btn btn-sm btn-filter active" id="filter-all" onclick="setPiutangFilter('all')">Semua Tagihan</button>
            <button type="button" class="btn btn-sm btn-filter" id="filter-overdue" onclick="setPiutangFilter('overdue')">Kritis / Overdue</button>
            <button type="button" class="btn btn-sm btn-filter" id="filter-active" onclick="setPiutangFilter('active')">Belum Lunas</button>
            <button type="button" class="btn btn-sm btn-filter" id="filter-paid" onclick="setPiutangFilter('paid')">Lunas</button>
        </div>
    </div>

    <!-- Tabel Daftar Piutang Pelanggan -->
    <div class="card p-0 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Daftar Tagihan Piutang Kafe & Resto (HORECA)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Prioritas penagihan teratas adalah tagihan jatuh tempo terdekat / telah lewat tempo.</p>
            </div>
            <div class="text-xs text-slate-500 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500"></span> Prioritas Penagihan
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-custom w-full" id="tablePiutang">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Mitra & Pelanggan</th>
                        <th>No Invoice / TRX</th>
                        <th>Tgl Transaksi</th>
                        <th style="width: 150px;">Jatuh Tempo</th>
                        <th style="text-align: right;">Sisa Tagihan</th>
                        <th style="width: 170px;">Status Pembayaran</th>
                        <th style="width: 230px; text-align: center;">Aksi Penagihan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($piutang_raw as $p):
                        $ts_due = strtotime($p['jatuh_tempo']);
                        $is_overdue = ($ts_due < $today_ts && $p['status'] !== 'paid');
                        $is_today   = ($ts_due == $today_ts && $p['status'] !== 'paid');
                        
                        // Buat teks WhatsApp otomatis
                        $clean_wa = preg_replace('/[^0-9]/', '', $p['wa']);
                        if (substr($clean_wa, 0, 1) === '0') {
                            $clean_wa = '62' . substr($clean_wa, 1);
                        }

                        $wa_message = "Halo {$p['pelanggan']}, kami dari CV. Mitra Kembar Pradipta (MKP Store) Malang ingin menginformasikan bahwa tagihan Invoice #{$p['trx_no']} sebesar " . rupiah($p['sisa']) . " telah " . ($is_overdue ? "LEWAT" : "mendekati") . " batas jatuh tempo pada tanggal " . date('d/m/Y', $ts_due) . ". \n\nPembayaran dapat ditransfer ke Rekening BCA: 123-456-7890 a.n CV Mitra Kembar Pradipta. Konfirmasi bukti transfer dapat dikirimkan ke kontak ini. Terima kasih banyak atas kerjasamanya! 🙏";
                        $wa_link = "https://wa.me/{$clean_wa}?text=" . rawurlencode($wa_message);
                    ?>
                    <tr class="piutang-row <?= ($is_overdue || $is_today) ? 'bg-rose-50/20' : '' ?>" 
                        data-status="<?= e($p['status']) ?>" 
                        data-overdue="<?= ($is_overdue || $is_today) ? '1' : '0' ?>"
                        data-search="<?= e(strtolower($p['pelanggan'].' '.$p['trx_no'].' '.$p['kontak'].' '.$p['wa'])) ?>">
                        <td class="text-slate-400 font-mono text-xs"><?= $no++ ?></td>
                        <td>
                            <div>
                                <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                                    <?= e($p['pelanggan']) ?>
                                    <?php if ($is_overdue): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 animate-pulse">
                                            Kritis
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                                    <span><?= e($p['kontak']) ?></span>
                                    <span>&bull;</span>
                                    <span class="font-mono text-slate-600"><?= e($p['wa']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono text-xs font-semibold px-2 py-1 bg-slate-100 text-slate-700 rounded border border-slate-200">
                                <?= e($p['trx_no']) ?>
                            </span>
                        </td>
                        <td class="text-xs text-slate-600">
                            <?= date('d M Y', strtotime($p['tgl_trx'])) ?>
                        </td>
                        <td>
                            <div class="font-bold text-xs <?= $is_overdue ? 'text-rose-600 font-bold' : ($is_today ? 'text-amber-600 font-bold' : 'text-slate-700') ?>">
                                <?= date('d M Y', $ts_due) ?>
                            </div>
                            <div class="text-[11px] <?= $is_overdue ? 'text-rose-500 font-semibold' : 'text-slate-400' ?>">
                                <?php 
                                if ($p['status'] === 'paid') {
                                    echo '<span class="text-emerald-600">Selesai</span>';
                                } elseif ($is_today) {
                                    echo '<span class="text-rose-600 font-bold">Jatuh Tempo Hari Ini!</span>';
                                } elseif ($is_overdue) {
                                    $days_late = floor(($today_ts - $ts_due) / 86400);
                                    echo '<span class="text-rose-600 font-bold">Terlewat ' . $days_late . ' hari</span>';
                                } else {
                                    $days_left = floor(($ts_due - $today_ts) / 86400);
                                    echo 'Sisa ' . $days_left . ' hari';
                                }
                                ?>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <div class="font-mono font-bold text-slate-800 text-sm">
                                <?= rupiah($p['sisa']) ?>
                            </div>
                            <?php if ($p['terbayar'] > 0 && $p['sisa'] > 0): ?>
                                <span class="text-[10px] text-emerald-600 block">Tercicil <?= rupiah($p['terbayar']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['status'] === 'paid'): ?>
                                <span class="badge badge-success" style="background:#ECFDF5; color:#059669;">
                                    Lunas
                                </span>
                            <?php elseif ($is_overdue): ?>
                                <span class="badge badge-danger" style="background:#FEF2F2; color:#EF4444; border: 1px solid #FECACA;">
                                    Lewat Tempo
                                </span>
                            <?php elseif ($is_today): ?>
                                <span class="badge badge-warn" style="background:#FEF3C7; color:#D97706; border: 1px solid #FDE68A;">
                                    Tempo Hari Ini
                                </span>
                            <?php else: ?>
                                <span class="badge badge-info" style="background:#EBF7F8; color:#088395;">
                                    TOP <?= $p['tempo_hari'] ?> Hari
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1.5">
                                <?php if ($p['status'] !== 'paid'): ?>
                                    <!-- Tombol Kirim WhatsApp -->
                                    <a href="<?= $wa_link ?>" target="_blank" rel="noopener noreferrer" 
                                       class="btn btn-sm btn-wa flex items-center gap-1.5" 
                                       title="Buka WhatsApp Web / App dengan draf pesan otomatis">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        Kirim WhatsApp
                                    </a>
                                    <!-- Tombol Catat Pembayaran -->
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                            onclick="openPayModal('<?= e($p['id']) ?>', '<?= e($p['trx_no']) ?>', '<?= e($p['pelanggan']) ?>', <?= $p['sisa'] ?>)" 
                                            title="Catat penerimaan pembayaran / pelunasan">
                                        Pelunasan
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Sudah Lunas</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Pelunasan Piutang -->
<div id="modal-pay" class="modal-backdrop hidden">
    <div class="modal-dialog max-w-md">
        <div class="modal-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-bold" style="background:#EBF7F8; color:#088395;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base">Pencatatan Pelunasan Piutang</h3>
            </div>
            <button type="button" class="modal-close" onclick="closePayModal()">&times;</button>
        </div>
        <div class="modal-body space-y-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Mitra Pelanggan</label>
                <input type="text" id="pay-customer" class="form-control text-sm w-full bg-slate-100 font-semibold text-slate-800" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor Nota Invoice</label>
                <input type="text" id="pay-trx" class="form-control text-xs w-full bg-slate-100 font-mono" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Sisa Tagihan Tertunggak</label>
                <input type="text" id="pay-sisa-str" class="form-control text-sm w-full bg-rose-50 text-rose-700 font-mono font-bold" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Pembayaran Diterima (Rp)</label>
                <input type="number" id="pay-amount" class="form-control text-sm w-full font-mono font-bold" placeholder="Masukkan jumlah bayar">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                <select id="pay-method" class="form-control text-xs w-full">
                    <option value="bca">Transfer Bank BCA (011-8899-771 a.n MKP Store)</option>
                    <option value="mandiri">Transfer Bank Mandiri (144-00-998877 a.n MKP Store)</option>
                    <option value="cash">Tunai / Setor Kasir</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Referensi Transfer / Keterangan</label>
                <input type="text" id="pay-ref" class="form-control text-xs w-full" placeholder="Contoh: TRF-BCA-98124">
            </div>
        </div>
        <div class="modal-footer flex items-center justify-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePayModal()">Batal</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="submitPayment()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan & Update Status
            </button>
        </div>
    </div>
</div>

<style>
/* CSS Khusus Piutang */
.btn-wa {
    background: #25D366;
    color: #FFFFFF;
    font-weight: 600;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    transition: all 0.15s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-wa:hover {
    background: #1EBE5D;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 211, 102, 0.25);
}

.btn-filter {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    color: #64748B;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-filter.active {
    background: #088395;
    border-color: #088395;
    color: #FFFFFF;
}

/* Modal styling */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.modal-backdrop.hidden {
    display: none;
}
.modal-dialog {
    background: #FFFFFF;
    border-radius: 16px;
    width: 100%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid #F1F5F9;
    background: #F8FAFC;
}
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #94A3B8;
    cursor: pointer;
}
</style>

<script>
let activePiutangFilter = 'all';
let currentPayId = '';
let currentPaySisa = 0;

function setPiutangFilter(filter) {
    activePiutangFilter = filter;
    document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
    const target = document.getElementById(`filter-${filter}`);
    if (target) target.classList.add('active');
    filterPiutangTable();
}

function filterPiutangTable() {
    const query = (document.getElementById('piutangSearch').value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.piutang-row');

    rows.forEach(row => {
        const status = row.dataset.status;
        const isOverdue = row.dataset.overdue === '1';
        const searchData = row.dataset.search || '';

        let passFilter = true;
        if (activePiutangFilter === 'overdue' && !isOverdue) passFilter = false;
        if (activePiutangFilter === 'active' && status === 'paid') passFilter = false;
        if (activePiutangFilter === 'paid' && status !== 'paid') passFilter = false;

        let passSearch = true;
        if (query) {
            passSearch = searchData.includes(query);
        }

        row.style.display = (passFilter && passSearch) ? '' : 'none';
    });
}

function openPayModal(id, trx, customer, sisa) {
    currentPayId = id;
    currentPaySisa = sisa;
    document.getElementById('pay-customer').value = customer;
    document.getElementById('pay-trx').value = trx;
    document.getElementById('pay-sisa-str').value = 'Rp ' + sisa.toLocaleString('id-ID');
    document.getElementById('pay-amount').value = sisa;
    document.getElementById('pay-ref').value = 'TRF-' + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('modal-pay').classList.remove('hidden');
}

function closePayModal() {
    document.getElementById('modal-pay').classList.add('hidden');
}

function submitPayment() {
    const amount = parseInt(document.getElementById('pay-amount').value, 10);
    if (isNaN(amount) || amount <= 0) {
        alert('Silakan masukkan jumlah pembayaran yang valid.');
        return;
    }

    closePayModal();
    alert('PEMBAYARAN DITERIMA: Tagihan ' + document.getElementById('pay-trx').value + ' sebesar Rp ' + amount.toLocaleString('id-ID') + ' telah dicatat lunas!');
    location.reload();
}
</script>

<?php require $root . 'layouts/footer.php'; ?>
