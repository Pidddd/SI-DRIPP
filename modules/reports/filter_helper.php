<?php
/**
 * HELPER FILTER PERIODE LAPORAN PENJUALAN (DUA MODE: RENTANG TANGGAL & PER BULAN)
 * File: modules/reports/filter_helper.php
 * Fungsi: Menyeragamkan parsing parameter GET, pembentukan klausa SQL berparameter
 *         (pg_query_params), label periode Indonesia, dan query string export PDF/Excel.
 */

if (!function_exists('report_daftar_bulan')) {
    function report_daftar_bulan() {
        return [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}

if (!function_exists('report_bulan_singkat')) {
    function report_bulan_singkat($bulan_num) {
        $map = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        return isset($map[(int)$bulan_num]) ? $map[(int)$bulan_num] : '';
    }
}

if (!function_exists('report_parse_filter')) {
    function report_parse_filter($source = null) {
        $src = is_array($source) ? $source : $_GET;

        $mode = (isset($src['mode']) && $src['mode'] === 'monthly') ? 'monthly' : 'range';

        $tgl_mulai = !empty($src['tgl_mulai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$src['tgl_mulai'])
            ? (string)$src['tgl_mulai']
            : date('Y-m-01');
        $tgl_akhir = !empty($src['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$src['tgl_akhir'])
            ? (string)$src['tgl_akhir']
            : date('Y-m-d');

        if ($tgl_mulai > $tgl_akhir) {
            $tmp       = $tgl_mulai;
            $tgl_mulai = $tgl_akhir;
            $tgl_akhir = $tmp;
        }

        $tahun = isset($src['tahun']) && preg_match('/^\d{4}$/', (string)$src['tahun'])
            ? (int)$src['tahun']
            : (int)date('Y');
        if ($tahun < 2020 || $tahun > 2100) {
            $tahun = (int)date('Y');
        }

        $bulan_raw  = isset($src['bulan']) ? $src['bulan'] : [];
        if (!is_array($bulan_raw)) {
            $bulan_raw = [$bulan_raw];
        }
        $bulan_list = [];
        foreach ($bulan_raw as $b) {
            $bi = (int)$b;
            if ($bi >= 1 && $bi <= 12) {
                $bulan_list[$bi] = $bi;
            }
        }
        if (empty($bulan_list)) {
            $cur_m = (int)date('n');
            $bulan_list[$cur_m] = $cur_m;
        }
        ksort($bulan_list);
        $bulan_list = array_values($bulan_list);

        $min_unit = isset($src['min_unit']) ? max(0, (int)$src['min_unit']) : 0;

        $nama_bulan = report_daftar_bulan();
        if ($mode === 'monthly') {
            $nama_terpilih = [];
            foreach ($bulan_list as $bm) {
                $nama_terpilih[] = $nama_bulan[$bm];
            }
            $label_periode = implode(', ', $nama_terpilih) . ' ' . $tahun;
        } else {
            $label_periode = date('d M Y', strtotime($tgl_mulai)) . ' s/d ' . date('d M Y', strtotime($tgl_akhir));
        }

        return [
            'mode'          => $mode,
            'tgl_mulai'     => $tgl_mulai,
            'tgl_akhir'     => $tgl_akhir,
            'tahun'         => $tahun,
            'bulan'         => $bulan_list,
            'min_unit'      => $min_unit,
            'label_periode' => $label_periode,
        ];
    }
}

if (!function_exists('report_sql_period_clause')) {
    /**
     * Menghasilkan potongan klausa SQL berparameter ($1, $2, ...) dan array nilai parameter.
     *
     * @param array  $filter       Hasil dari report_parse_filter()
     * @param string $col_expr     Nama kolom timestamp (mis. 't.waktu_transaksi' atau 'waktu_transaksi')
     * @param int    $start_index  Indeks parameter PostgreSQL awal (default 1)
     * @return array ['sql' => string, 'params' => array, 'next_index' => int]
     */
    function report_sql_period_clause($filter, $col_expr = 't.waktu_transaksi', $start_index = 1) {
        $p1 = '$' . $start_index;
        $p2 = '$' . ($start_index + 1);

        if ($filter['mode'] === 'monthly') {
            $pg_array = '{' . implode(',', array_map('intval', $filter['bulan'])) . '}';
            return [
                'sql'        => "EXTRACT(YEAR FROM {$col_expr}) = {$p1} AND EXTRACT(MONTH FROM {$col_expr}) = ANY({$p2}::int[])",
                'params'     => [(int)$filter['tahun'], $pg_array],
                'next_index' => $start_index + 2,
            ];
        }

        return [
            'sql'        => "DATE({$col_expr}) BETWEEN {$p1} AND {$p2}",
            'params'     => [$filter['tgl_mulai'], $filter['tgl_akhir']],
            'next_index' => $start_index + 2,
        ];
    }
}

if (!function_exists('report_build_query_string')) {
    function report_build_query_string($filter) {
        $parts = ['mode=' . urlencode($filter['mode'])];
        if ($filter['mode'] === 'monthly') {
            $parts[] = 'tahun=' . (int)$filter['tahun'];
            foreach ($filter['bulan'] as $b) {
                $parts[] = 'bulan[]=' . (int)$b;
            }
        } else {
            $parts[] = 'tgl_mulai=' . urlencode($filter['tgl_mulai']);
            $parts[] = 'tgl_akhir=' . urlencode($filter['tgl_akhir']);
        }
        $parts[] = 'min_unit=' . (int)$filter['min_unit'];
        return implode('&', $parts);
    }
}
