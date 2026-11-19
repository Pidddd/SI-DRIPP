<?php
/**
 * HELPER DATA LAPORAN MULTI-SHEET (REUSABLE UNTUK EXCEL .XLSX & GOOGLE SHEETS)
 * File: modules/reports/report_data_helper.php
 * Fungsi: Menyusun struktur data matriks pencatatan penjualan harian MKP Store
 *         per section kategori (category_header, product, category_subtotal, grand_total),
 *         baik untuk Mode Rentang Tanggal (1 sheet) maupun Mode Per Bulan (multi-sheet).
 */

require_once __DIR__ . '/filter_helper.php';

if (!function_exists('report_sanitize_sheet_name')) {
    function report_sanitize_sheet_name($name) {
        $clean = preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', (string)$name);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') {
            $clean = 'SHEET';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($clean, 0, 31, 'UTF-8');
        }
        return substr($clean, 0, 31);
    }
}

if (!function_exists('report_build_multisheet_data')) {
    /**
     * Membangun struktur data laporan multi-sheet lengkap dari PostgreSQL.
     *
     * @param resource|object $conn   Koneksi pg_connect aktif
     * @param array           $filter Hasil report_parse_filter()
     * @return array
     */
    function report_build_multisheet_data($conn, $filter) {
        $daftar_bulan = report_daftar_bulan();
        $min_unit     = (int)$filter['min_unit'];

        // 1. Ambil seluruh katalog produk dikelompokkan per kategori
        $q_catalog = "SELECT COALESCE(c.id_category, 0) AS id_category,
                             COALESCE(c.nama_category, 'Tanpa Kategori') AS nama_category,
                             p.id_product,
                             p.nama_barang
                      FROM products p
                      LEFT JOIN category c ON p.categoryid_category = c.id_category
                      ORDER BY COALESCE(c.nama_category, 'Tanpa Kategori') ASC, p.nama_barang ASC";
        $res_catalog = pg_query($conn, $q_catalog);

        $catalog_categories = [];
        if ($res_catalog) {
            while ($row = pg_fetch_assoc($res_catalog)) {
                $cid = (string)$row['id_category'];
                if (!isset($catalog_categories[$cid])) {
                    $catalog_categories[$cid] = [
                        'id_category'   => $cid,
                        'nama_category' => $row['nama_category'],
                        'products'      => [],
                    ];
                }
                $catalog_categories[$cid]['products'][] = [
                    'id_product'  => $row['id_product'],
                    'nama_barang' => $row['nama_barang'],
                ];
            }
        }

        // 2. Susun spesifikasi worksheet (Multi-Sheet Per Bulan vs 1 Sheet Rentang Tanggal)
        $sheet_specs = [];

        if ($filter['mode'] === 'monthly') {
            $tahun = (int)$filter['tahun'];
            foreach ($filter['bulan'] as $m) {
                $m             = (int)$m;
                $days_in_month = (int)date('t', mktime(0, 0, 0, $m, 1, $tahun));
                $start_date    = sprintf('%04d-%02d-01', $tahun, $m);
                $end_date      = sprintf('%04d-%02d-%02d', $tahun, $m, $days_in_month);
                $month_name    = $daftar_bulan[$m];

                $dates = [];
                for ($d = 1; $d <= $days_in_month; $d++) {
                    $dates[] = [
                        'iso'        => sprintf('%04d-%02d-%02d', $tahun, $m, $d),
                        'label'      => (string)$d,
                        'month_key'  => sprintf('%04d-%02d', $tahun, $m),
                        'month_name' => strtoupper($month_name) . ' ' . $tahun,
                    ];
                }

                $sheet_specs[] = [
                    'sheet_name'        => report_sanitize_sheet_name(strtoupper($month_name) . ' ' . $tahun),
                    'title'             => 'LAPORAN PENJUALAN HARIAN MKP STORE — ' . strtoupper($month_name) . ' ' . $tahun,
                    'subtitle'          => 'Periode: ' . $month_name . ' ' . $tahun . ' · Filter Minimal Unit Kategori: ' . $min_unit . ' Unit · Dicetak: ' . date('d M Y H:i'),
                    'start_date'        => $start_date,
                    'end_date'          => $end_date,
                    'dates'             => $dates,
                    'show_month_banner' => false,
                ];
            }
            $filename_suffix = 'Bulanan_' . $tahun . '_' . implode('-', $filter['bulan']);
        } else {
            $dt_start = new DateTimeImmutable($filter['tgl_mulai']);
            $dt_end   = new DateTimeImmutable($filter['tgl_akhir']);
            $max_end  = $dt_start->modify('+365 days');
            if ($dt_end > $max_end) {
                $dt_end = $max_end;
            }

            $dates = [];
            $cur   = $dt_start;
            while ($cur <= $dt_end) {
                $m_num   = (int)$cur->format('n');
                $y_num   = $cur->format('Y');
                $dates[] = [
                    'iso'        => $cur->format('Y-m-d'),
                    'label'      => $cur->format('d') . ' ' . report_bulan_singkat($m_num),
                    'month_key'  => $cur->format('Y-m'),
                    'month_name' => strtoupper($daftar_bulan[$m_num]) . ' ' . $y_num,
                ];
                $cur = $cur->modify('+1 day');
            }

            $sheet_specs[] = [
                'sheet_name'        => 'REKAP RENTANG TANGGAL',
                'title'             => 'LAPORAN PENJUALAN HARIAN MKP STORE (RENTANG TANGGAL)',
                'subtitle'          => 'Periode: ' . $filter['label_periode'] . ' · Filter Minimal Unit Kategori: ' . $min_unit . ' Unit · Dicetak: ' . date('d M Y H:i'),
                'start_date'        => $dt_start->format('Y-m-d'),
                'end_date'          => $dt_end->format('Y-m-d'),
                'dates'             => $dates,
                'show_month_banner' => true,
            ];
            $filename_suffix = 'Rentang_' . $filter['tgl_mulai'] . '_sd_' . $filter['tgl_akhir'];
        }

        // 3. Query penjualan harian teragregasi per (produk, tanggal) untuk setiap worksheet
        $q_daily_sales = "SELECT td.productsid_product AS id_product,
                                 TO_CHAR(DATE(t.waktu_transaksi), 'YYYY-MM-DD') AS tgl,
                                 SUM(td.kuantitas) AS qty,
                                 SUM(td.subtotal_harga) AS omzet,
                                 SUM(td.kuantitas * p.harga_beli) AS hpp
                          FROM transaction_details td
                          INNER JOIN transactions t ON td.transactionsid_transaksi = t.id_transaksi
                          INNER JOIN products p ON td.productsid_product = p.id_product
                          WHERE DATE(t.waktu_transaksi) BETWEEN $1 AND $2
                          GROUP BY td.productsid_product, DATE(t.waktu_transaksi)";

        $sheets_data = [];

        foreach ($sheet_specs as $spec) {
            $res_sales = pg_query_params($conn, $q_daily_sales, [$spec['start_date'], $spec['end_date']]);

            $sales_map = [];
            if ($res_sales) {
                while ($s = pg_fetch_assoc($res_sales)) {
                    $pid = (string)$s['id_product'];
                    if (!isset($sales_map[$pid])) {
                        $sales_map[$pid] = [
                            'daily' => [],
                            'unit'  => 0,
                            'omzet' => 0,
                            'hpp'   => 0,
                        ];
                    }
                    $qty_v   = (int)$s['qty'];
                    $omzet_v = (int)$s['omzet'];
                    $hpp_v   = (int)$s['hpp'];

                    $sales_map[$pid]['daily'][$s['tgl']] = $qty_v;
                    $sales_map[$pid]['unit']            += $qty_v;
                    $sales_map[$pid]['omzet']           += $omzet_v;
                    $sales_map[$pid]['hpp']             += $hpp_v;
                }
            }

            $dates     = $spec['dates'];
            $num_dates = count($dates);

            // Susun array header kolom
            $headers = ['NO', 'NAMA PRODUK'];
            foreach ($dates as $d_info) {
                $headers[] = $d_info['label'];
            }
            $headers[]  = 'TOTAL TERJUAL (UNIT)';
            $headers[]  = 'OMZET KOTOR (RP)';
            $headers[]  = 'HPP (RP)';
            $headers[]  = 'LABA KOTOR (RP)';
            $total_cols = count($headers);

            // Susun baris terstruktur (category_header, product, category_subtotal, grand_total)
            $rows        = [];
            $grand_daily = array_fill(0, $num_dates, 0);
            $grand_unit  = 0;
            $grand_omzet = 0;
            $grand_hpp   = 0;
            $grand_laba  = 0;
            $cat_count   = 0;

            foreach ($catalog_categories as $cat) {
                if (empty($cat['products'])) {
                    continue;
                }

                // Cek ambang minimal unit HAVING per kategori
                $cat_unit_check = 0;
                foreach ($cat['products'] as $prod) {
                    $pid = (string)$prod['id_product'];
                    if (isset($sales_map[$pid])) {
                        $cat_unit_check += $sales_map[$pid]['unit'];
                    }
                }
                if ($min_unit > 0 && $cat_unit_check < $min_unit) {
                    continue;
                }

                $cat_count++;
                $cat_header_label  = 'KATEGORI: ' . strtoupper($cat['nama_category']);
                $cat_header_values = array_fill(0, $total_cols, '');
                $cat_header_values[0] = $cat_header_label;

                $rows[] = [
                    'type'          => 'category_header',
                    'label'         => $cat_header_label,
                    'category_name' => $cat['nama_category'],
                    'values'        => $cat_header_values,
                ];

                $cat_daily = array_fill(0, $num_dates, 0);
                $cat_unit  = 0;
                $cat_omzet = 0;
                $cat_hpp   = 0;
                $cat_laba  = 0;
                $no_prod   = 1;

                foreach ($cat['products'] as $prod) {
                    $pid     = (string)$prod['id_product'];
                    $pinfo   = isset($sales_map[$pid]) ? $sales_map[$pid] : ['daily' => [], 'unit' => 0, 'omzet' => 0, 'hpp' => 0];
                    $p_unit  = (int)$pinfo['unit'];
                    $p_omzet = (int)$pinfo['omzet'];
                    $p_hpp   = (int)$pinfo['hpp'];
                    $p_laba  = $p_omzet - $p_hpp;

                    $p_daily = [];
                    $p_vals  = [$no_prod, $prod['nama_barang']];
                    foreach ($dates as $i => $d_info) {
                        $qty_d           = isset($pinfo['daily'][$d_info['iso']]) ? (int)$pinfo['daily'][$d_info['iso']] : 0;
                        $p_daily[]       = $qty_d;
                        $p_vals[]        = $qty_d;
                        $cat_daily[$i]   += $qty_d;
                        $grand_daily[$i] += $qty_d;
                    }
                    $p_vals[] = $p_unit;
                    $p_vals[] = $p_omzet;
                    $p_vals[] = $p_hpp;
                    $p_vals[] = $p_laba;

                    $rows[] = [
                        'type'        => 'product',
                        'no'          => $no_prod,
                        'id_product'  => $prod['id_product'],
                        'nama_barang' => $prod['nama_barang'],
                        'daily'       => $p_daily,
                        'total_unit'  => $p_unit,
                        'omzet'       => $p_omzet,
                        'hpp'         => $p_hpp,
                        'laba'        => $p_laba,
                        'values'      => $p_vals,
                    ];

                    $cat_unit  += $p_unit;
                    $cat_omzet += $p_omzet;
                    $cat_hpp   += $p_hpp;
                    $cat_laba  += $p_laba;
                    $no_prod++;
                }

                $sub_label = 'TOTAL ' . strtoupper($cat['nama_category']);
                $sub_vals  = array_merge([$sub_label, ''], $cat_daily, [$cat_unit, $cat_omzet, $cat_hpp, $cat_laba]);

                $rows[] = [
                    'type'          => 'category_subtotal',
                    'label'         => $sub_label,
                    'category_name' => $cat['nama_category'],
                    'daily'         => $cat_daily,
                    'total_unit'    => $cat_unit,
                    'omzet'         => $cat_omzet,
                    'hpp'           => $cat_hpp,
                    'laba'          => $cat_laba,
                    'values'        => $sub_vals,
                ];

                $grand_unit  += $cat_unit;
                $grand_omzet += $cat_omzet;
                $grand_hpp   += $cat_hpp;
                $grand_laba  += $cat_laba;
            }

            $grand_vals = array_merge(['GRAND TOTAL', ''], $grand_daily, [$grand_unit, $grand_omzet, $grand_hpp, $grand_laba]);
            $rows[] = [
                'type'       => 'grand_total',
                'label'      => 'GRAND TOTAL',
                'daily'      => $grand_daily,
                'total_unit' => $grand_unit,
                'omzet'      => $grand_omzet,
                'hpp'        => $grand_hpp,
                'laba'       => $grand_laba,
                'values'     => $grand_vals,
            ];

            $sheets_data[] = [
                'sheet_name'        => $spec['sheet_name'],
                'title'             => $spec['title'],
                'subtitle'          => $spec['subtitle'],
                'start_date'        => $spec['start_date'],
                'end_date'          => $spec['end_date'],
                'show_month_banner' => $spec['show_month_banner'],
                'dates'             => $dates,
                'headers'           => $headers,
                'category_count'    => $cat_count,
                'rows'              => $rows,
            ];
        }

        $safe_suffix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename_suffix);

        return [
            'file_base_name'  => 'Laporan_Penjualan_SI-DRIPP_' . $safe_suffix,
            'filename_suffix' => $filename_suffix,
            'filter'          => $filter,
            'sheets'          => $sheets_data,
        ];
    }
}
