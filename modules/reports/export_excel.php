<?php
/**
 * BACKEND MODUL LAPORAN — EXPORT EXCEL NATIVE MULTI-SHEET (.XLSX)
 * File: modules/reports/export_excel.php
 * Paradigma: PHP Native Prosedural (ZipArchive + OOXML murni, tanpa Composer/library eksternal)
 * Fungsi: Menghasilkan file .xlsx asli dengan format pencatatan harian MKP Store
 *         menggunakan struktur data terpadu dari report_data_helper.php,
 *         rumus =SUM(...) untuk subtotal & grand total, dan kontras warna 3 tingkat.
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/auth_guard.php';
require_once __DIR__ . '/report_data_helper.php';

cek_hak_akses(['super_admin']);

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die('Ekstensi PHP ZipArchive belum aktif di server. Aktifkan extension=zip pada php.ini untuk mengekspor file Excel (.xlsx).');
}

/* ============================================================================
 * 1. HELPER OOXML (KONVERSI KOLOM, ESCAPING XML, SHARED STRINGS, STYLES)
 * ============================================================================ */

function xlsx_col_letter($index) {
    $letter = '';
    while ($index > 0) {
        $mod    = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index  = (int)(($index - $mod - 1) / 26);
    }
    return $letter;
}

function xlsx_xml_escape($str) {
    return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_add_shared_string(&$ss_map, &$ss_list, &$ss_refs, $text) {
    $key = (string)$text;
    $ss_refs++;
    if (!array_key_exists($key, $ss_map)) {
        $ss_map[$key] = count($ss_list);
        $ss_list[]    = $key;
    }
    return $ss_map[$key];
}

function xlsx_cell_str(&$ss_map, &$ss_list, &$ss_refs, $ref, $value, $style = 0) {
    $idx = xlsx_add_shared_string($ss_map, $ss_list, $ss_refs, $value);
    return '<c r="' . $ref . '" t="s" s="' . (int)$style . '"><v>' . $idx . '</v></c>';
}

function xlsx_cell_num($ref, $value, $style = 0) {
    $num = is_numeric($value) ? (0 + $value) : 0;
    return '<c r="' . $ref . '" s="' . (int)$style . '"><v>' . $num . '</v></c>';
}

function xlsx_cell_formula($ref, $formula, $cached_value, $style = 0) {
    $num = is_numeric($cached_value) ? (0 + $cached_value) : 0;
    return '<c r="' . $ref . '" s="' . (int)$style . '"><f>' . xlsx_xml_escape($formula) . '</f><v>' . $num . '</v></c>';
}

function xlsx_cell_empty($ref, $style = 0) {
    return '<c r="' . $ref . '" s="' . (int)$style . '"/>';
}

function xlsx_build_styles_xml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1">'
        . '<numFmt numFmtId="164" formatCode="#,##0"/>'
        . '</numFmts>'
        . '<fonts count="6">'
        // 0: Default 10pt Espresso
        . '<font><sz val="10"/><color rgb="FF2B1B12"/><name val="Calibri"/></font>'
        // 1: Title 14pt Bold Espresso
        . '<font><b/><sz val="14"/><color rgb="FF3B2314"/><name val="Calibri"/></font>'
        // 2: Subtitle 10pt Italic Muted
        . '<font><i/><sz val="10"/><color rgb="FF6B4E3D"/><name val="Calibri"/></font>'
        // 3: Header & Subtotal 10pt Bold White
        . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        // 4: Category Header & Month Banner 10pt Bold Dark Espresso (#3B2314)
        . '<font><b/><sz val="10"/><color rgb="FF3B2314"/><name val="Calibri"/></font>'
        // 5: Grand Total 11pt Bold White
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="7">'
        // 0 & 1: Mandatory Excel fills
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        // 2: Table Header Roasted Caramel #7A4419
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF7A4419"/><bgColor indexed="64"/></patternFill></fill>'
        // 3: Month Banner Soft Cream #FBF6F0
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFBF6F0"/><bgColor indexed="64"/></patternFill></fill>'
        // 4: Category Section Header Soft Cream #F4E3CE
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4E3CE"/><bgColor indexed="64"/></patternFill></fill>'
        // 5: Category Subtotal High-Contrast Caramel #9A5B25
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF9A5B25"/><bgColor indexed="64"/></patternFill></fill>'
        // 6: Grand Total Deep Espresso #2B180C
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF2B180C"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="4">'
        // 0: None
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        // 1: Standard Thin Caramel Border
        . '<border>'
        . '<left style="thin"><color rgb="FFD6B792"/></left>'
        . '<right style="thin"><color rgb="FFD6B792"/></right>'
        . '<top style="thin"><color rgb="FFD6B792"/></top>'
        . '<bottom style="thin"><color rgb="FFD6B792"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        // 2: Bold Top & Bottom Border for Category Subtotal (#5C3412)
        . '<border>'
        . '<left style="thin"><color rgb="FF7A4419"/></left>'
        . '<right style="thin"><color rgb="FF7A4419"/></right>'
        . '<top style="medium"><color rgb="FF5C3412"/></top>'
        . '<bottom style="medium"><color rgb="FF5C3412"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        // 3: Bold Top & Double Bottom Border for Grand Total (#2B180C)
        . '<border>'
        . '<left style="thin"><color rgb="FF2B180C"/></left>'
        . '<right style="thin"><color rgb="FF2B180C"/></right>'
        . '<top style="medium"><color rgb="FF2B180C"/></top>'
        . '<bottom style="double"><color rgb="FF2B180C"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="15">'
        // 0: Normal
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        // 1: Report Title
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 2: Report Subtitle
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 3: Table Header (Roasted Caramel White Bold Center Wrap)
        . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        // 4: Month Banner Header (Soft Cream Bold Center)
        . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        // 5: Category Section Header (#F4E3CE + #3B2314 Bold)
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 6: Data Text Left
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 7: Data Integer Center
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        // 8: Data Rupiah Right
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        // 9: Subtotal Label Left (#9A5B25 + White Bold + Medium Top/Bottom Borders)
        . '<xf numFmtId="0" fontId="3" fillId="5" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 10: Subtotal Integer Center (#9A5B25 + White Bold + Medium Top/Bottom Borders)
        . '<xf numFmtId="164" fontId="3" fillId="5" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        // 11: Subtotal Rupiah Right (#9A5B25 + White Bold + Medium Top/Bottom Borders)
        . '<xf numFmtId="164" fontId="3" fillId="5" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        // 12: Grand Total Label Left (#2B180C + White Bold)
        . '<xf numFmtId="0" fontId="5" fillId="6" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        // 13: Grand Total Integer Center (#2B180C + White Bold)
        . '<xf numFmtId="164" fontId="5" fillId="6" borderId="3" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        // 14: Grand Total Rupiah Right (#2B180C + White Bold)
        . '<xf numFmtId="164" fontId="5" fillId="6" borderId="3" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

/* ============================================================================
 * 2. AMBIL STRUKTUR DATA MULTI-SHEET DARI report_data_helper.php
 * ============================================================================ */

$filter      = report_parse_filter($_GET);
$report_data = report_build_multisheet_data($conn, $filter);

/* ============================================================================
 * 3. RENDER XML WORKSHEET DARI STRUKTUR DATA TERPADU
 * ============================================================================ */

$ss_map     = [];
$ss_list    = [];
$ss_refs    = 0;
$sheets_xml = [];

foreach ($report_data['sheets'] as $sheet) {
    $dates          = $sheet['dates'];
    $num_dates      = count($dates);
    $first_date_idx = 3; // Col C
    $last_date_idx  = $first_date_idx + $num_dates - 1;
    $col_unit_idx   = $last_date_idx + 1;
    $col_omzet_idx  = $last_date_idx + 2;
    $col_hpp_idx    = $last_date_idx + 3;
    $col_laba_idx   = $last_date_idx + 4;
    $total_cols     = $col_laba_idx;

    $first_date_col = xlsx_col_letter($first_date_idx);
    $last_date_col  = xlsx_col_letter($last_date_idx);
    $col_unit       = xlsx_col_letter($col_unit_idx);
    $col_omzet      = xlsx_col_letter($col_omzet_idx);
    $col_hpp        = xlsx_col_letter($col_hpp_idx);
    $col_laba       = xlsx_col_letter($col_laba_idx);
    $last_col       = xlsx_col_letter($total_cols);

    $rows_xml = [];
    $merges   = [];
    $r        = 1;

    // Row 1: Judul Laporan
    $rows_xml[] = '<row r="' . $r . '" ht="26" customHeight="1">'
        . xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, $sheet['title'], 1)
        . '</row>';
    $merges[] = 'A' . $r . ':' . $last_col . $r;
    $r++;

    // Row 2: Subjudul & Periode
    $rows_xml[] = '<row r="' . $r . '" ht="19" customHeight="1">'
        . xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, $sheet['subtitle'], 2)
        . '</row>';
    $merges[] = 'A' . $r . ':' . $last_col . $r;
    $r++;

    // Row 3: Baris Kosong Pemisah
    $rows_xml[] = '<row r="' . $r . '" ht="10" customHeight="1"/>';
    $r++;

    // Header Tabel (2 baris jika Mode Rentang Tanggal memiliki Banner Bulan, 1 baris jika Mode Per Bulan)
    if ($sheet['show_month_banner']) {
        $banner_r = $r;
        $header_r = $r + 1;

        $banner_cells   = [];
        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $banner_r, 'NO', 3);
        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'B' . $banner_r, 'NAMA PRODUK', 3);
        $merges[]       = 'A' . $banner_r . ':A' . $header_r;
        $merges[]       = 'B' . $banner_r . ':B' . $header_r;

        $month_groups = [];
        foreach ($dates as $i => $d_info) {
            $col_i = $first_date_idx + $i;
            $mk    = $d_info['month_key'];
            $g_cnt = count($month_groups);
            if ($g_cnt === 0 || $month_groups[$g_cnt - 1]['key'] !== $mk) {
                $month_groups[] = [
                    'key'       => $mk,
                    'label'     => $d_info['month_name'],
                    'start_idx' => $col_i,
                    'end_idx'   => $col_i,
                ];
            } else {
                $month_groups[$g_cnt - 1]['end_idx'] = $col_i;
            }
        }

        foreach ($month_groups as $mg) {
            for ($ci = $mg['start_idx']; $ci <= $mg['end_idx']; $ci++) {
                $ref = xlsx_col_letter($ci) . $banner_r;
                if ($ci === $mg['start_idx']) {
                    $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $ref, $mg['label'], 4);
                } else {
                    $banner_cells[] = xlsx_cell_empty($ref, 4);
                }
            }
            if ($mg['end_idx'] > $mg['start_idx']) {
                $merges[] = xlsx_col_letter($mg['start_idx']) . $banner_r . ':' . xlsx_col_letter($mg['end_idx']) . $banner_r;
            }
        }

        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $col_unit . $banner_r, 'TOTAL TERJUAL (UNIT)', 3);
        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $col_omzet . $banner_r, 'OMZET KOTOR (RP)', 3);
        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $col_hpp . $banner_r, 'HPP (RP)', 3);
        $banner_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $col_laba . $banner_r, 'LABA KOTOR (RP)', 3);

        $merges[] = $col_unit . $banner_r . ':' . $col_unit . $header_r;
        $merges[] = $col_omzet . $banner_r . ':' . $col_omzet . $header_r;
        $merges[] = $col_hpp . $banner_r . ':' . $col_hpp . $header_r;
        $merges[] = $col_laba . $banner_r . ':' . $col_laba . $header_r;

        $rows_xml[] = '<row r="' . $banner_r . '" ht="22" customHeight="1">' . implode('', $banner_cells) . '</row>';

        $hdr_cells   = [];
        $hdr_cells[] = xlsx_cell_empty('A' . $header_r, 3);
        $hdr_cells[] = xlsx_cell_empty('B' . $header_r, 3);
        foreach ($dates as $i => $d_info) {
            $ref         = xlsx_col_letter($first_date_idx + $i) . $header_r;
            $hdr_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $ref, $d_info['label'], 3);
        }
        $hdr_cells[] = xlsx_cell_empty($col_unit . $header_r, 3);
        $hdr_cells[] = xlsx_cell_empty($col_omzet . $header_r, 3);
        $hdr_cells[] = xlsx_cell_empty($col_hpp . $header_r, 3);
        $hdr_cells[] = xlsx_cell_empty($col_laba . $header_r, 3);

        $rows_xml[] = '<row r="' . $header_r . '" ht="24" customHeight="1">' . implode('', $hdr_cells) . '</row>';
        $r          = $header_r + 1;
    } else {
        $hdr_cells = [];
        foreach ($sheet['headers'] as $idx_h => $h_label) {
            $ref         = xlsx_col_letter($idx_h + 1) . $r;
            $hdr_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, $ref, $h_label, 3);
        }
        $rows_xml[] = '<row r="' . $r . '" ht="28" customHeight="1">' . implode('', $hdr_cells) . '</row>';
        $r++;
    }

    // Render baris data dari report_data_helper.php dengan rumus =SUM(...) aktif
    $subtotal_rows = [];
    $first_prod_r  = null;
    $last_prod_r   = null;

    if ((int)$sheet['category_count'] === 0) {
        $empty_cells = [xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, 'Tidak ada kategori yang memenuhi kriteria filter pada periode ini.', 6)];
        for ($ci = 2; $ci <= $total_cols; $ci++) {
            $empty_cells[] = xlsx_cell_empty(xlsx_col_letter($ci) . $r, 6);
        }
        $rows_xml[] = '<row r="' . $r . '" ht="22" customHeight="1">' . implode('', $empty_cells) . '</row>';
        $merges[]   = 'A' . $r . ':' . $last_col . $r;
        $r++;
    }

    foreach ($sheet['rows'] as $row_item) {
        $type = $row_item['type'];

        if ($type === 'category_header') {
            $cat_cells   = [];
            $cat_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, $row_item['label'], 5);
            for ($ci = 2; $ci <= $total_cols; $ci++) {
                $cat_cells[] = xlsx_cell_empty(xlsx_col_letter($ci) . $r, 5);
            }
            $rows_xml[]   = '<row r="' . $r . '" ht="21" customHeight="1">' . implode('', $cat_cells) . '</row>';
            $merges[]     = 'A' . $r . ':' . $last_col . $r;
            $first_prod_r = $r + 1;
            $last_prod_r  = $r + 1;
            $r++;
        } elseif ($type === 'product') {
            $p_cells   = [];
            $p_cells[] = xlsx_cell_num('A' . $r, $row_item['no'], 7);
            $p_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'B' . $r, $row_item['nama_barang'], 6);
            foreach ($row_item['daily'] as $i => $qty_d) {
                $p_cells[] = xlsx_cell_num(xlsx_col_letter($first_date_idx + $i) . $r, $qty_d, 7);
            }
            $p_cells[] = xlsx_cell_formula($col_unit . $r, 'SUM(' . $first_date_col . $r . ':' . $last_date_col . $r . ')', $row_item['total_unit'], 7);
            $p_cells[] = xlsx_cell_num($col_omzet . $r, $row_item['omzet'], 8);
            $p_cells[] = xlsx_cell_num($col_hpp . $r, $row_item['hpp'], 8);
            $p_cells[] = xlsx_cell_formula($col_laba . $r, $col_omzet . $r . '-' . $col_hpp . $r, $row_item['laba'], 8);

            $rows_xml[]  = '<row r="' . $r . '" ht="19" customHeight="1">' . implode('', $p_cells) . '</row>';
            $last_prod_r = $r;
            $r++;
        } elseif ($type === 'category_subtotal') {
            $sub_cells   = [];
            $sub_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, $row_item['label'], 9);
            $sub_cells[] = xlsx_cell_empty('B' . $r, 9);
            $merges[]    = 'A' . $r . ':B' . $r;

            foreach ($row_item['daily'] as $i => $val_d) {
                $dc          = xlsx_col_letter($first_date_idx + $i);
                $sub_cells[] = xlsx_cell_formula($dc . $r, 'SUM(' . $dc . $first_prod_r . ':' . $dc . $last_prod_r . ')', $val_d, 10);
            }
            $sub_cells[] = xlsx_cell_formula($col_unit . $r, 'SUM(' . $col_unit . $first_prod_r . ':' . $col_unit . $last_prod_r . ')', $row_item['total_unit'], 10);
            $sub_cells[] = xlsx_cell_formula($col_omzet . $r, 'SUM(' . $col_omzet . $first_prod_r . ':' . $col_omzet . $last_prod_r . ')', $row_item['omzet'], 11);
            $sub_cells[] = xlsx_cell_formula($col_hpp . $r, 'SUM(' . $col_hpp . $first_prod_r . ':' . $col_hpp . $last_prod_r . ')', $row_item['hpp'], 11);
            $sub_cells[] = xlsx_cell_formula($col_laba . $r, 'SUM(' . $col_laba . $first_prod_r . ':' . $col_laba . $last_prod_r . ')', $row_item['laba'], 11);

            $rows_xml[]      = '<row r="' . $r . '" ht="22" customHeight="1">' . implode('', $sub_cells) . '</row>';
            $subtotal_rows[] = $r;
            $r++;
        } elseif ($type === 'grand_total') {
            $gt_cells   = [];
            $gt_cells[] = xlsx_cell_str($ss_map, $ss_list, $ss_refs, 'A' . $r, $row_item['label'], 12);
            $gt_cells[] = xlsx_cell_empty('B' . $r, 12);
            $merges[]   = 'A' . $r . ':B' . $r;

            $build_grand_formula = function ($col_let) use ($subtotal_rows) {
                if (empty($subtotal_rows)) {
                    return '0';
                }
                $refs = [];
                foreach ($subtotal_rows as $sr) {
                    $refs[] = $col_let . $sr;
                }
                return 'SUM(' . implode(',', $refs) . ')';
            };

            foreach ($row_item['daily'] as $i => $val_d) {
                $dc         = xlsx_col_letter($first_date_idx + $i);
                $gt_cells[] = xlsx_cell_formula($dc . $r, $build_grand_formula($dc), $val_d, 13);
            }
            $gt_cells[] = xlsx_cell_formula($col_unit . $r, $build_grand_formula($col_unit), $row_item['total_unit'], 13);
            $gt_cells[] = xlsx_cell_formula($col_omzet . $r, $build_grand_formula($col_omzet), $row_item['omzet'], 14);
            $gt_cells[] = xlsx_cell_formula($col_hpp . $r, $build_grand_formula($col_hpp), $row_item['hpp'], 14);
            $gt_cells[] = xlsx_cell_formula($col_laba . $r, $build_grand_formula($col_laba), $row_item['laba'], 14);

            $rows_xml[] = '<row r="' . $r . '" ht="24" customHeight="1">' . implode('', $gt_cells) . '</row>';
            $r++;
        }
    }

    $date_col_width = $sheet['show_month_banner'] ? 9.5 : 5.5;
    $cols_xml = '<cols>'
        . '<col min="1" max="1" width="6" customWidth="1"/>'
        . '<col min="2" max="2" width="34" customWidth="1"/>'
        . '<col min="' . $first_date_idx . '" max="' . $last_date_idx . '" width="' . $date_col_width . '" customWidth="1"/>'
        . '<col min="' . $col_unit_idx . '" max="' . $col_unit_idx . '" width="18" customWidth="1"/>'
        . '<col min="' . $col_omzet_idx . '" max="' . $col_laba_idx . '" width="18" customWidth="1"/>'
        . '</cols>';

    $merge_xml = '';
    if (!empty($merges)) {
        $merge_tags = [];
        foreach ($merges as $m_ref) {
            $merge_tags[] = '<mergeCell ref="' . $m_ref . '"/>';
        }
        $merge_xml = '<mergeCells count="' . count($merge_tags) . '">' . implode('', $merge_tags) . '</mergeCells>';
    }

    $freeze_row   = $sheet['show_month_banner'] ? 6 : 5;
    $tab_sel_attr = count($sheets_xml) === 0 ? ' tabSelected="1"' : '';
    $sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView' . $tab_sel_attr . ' workbookViewId="0">'
        . '<pane xSplit="2" ySplit="' . ($freeze_row - 1) . '" topLeftCell="C' . $freeze_row . '" activePane="bottomRight" state="frozen"/>'
        . '</sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="19"/>'
        . $cols_xml
        . '<sheetData>' . implode('', $rows_xml) . '</sheetData>'
        . $merge_xml
        . '</worksheet>';

    $sheets_xml[] = [
        'name' => $sheet['sheet_name'],
        'xml'  => $sheet_xml,
    ];
}

/* ============================================================================
 * 4. RAKIT ARSIP .XLSX DENGAN ZIPARCHIVE DAN KIRIM SEBAGAI DOWNLOAD
 * ============================================================================ */

$num_sheets = count($sheets_xml);

$ct_overrides = [
    '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
    '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>',
    '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>',
];
for ($si = 1; $si <= $num_sheets; $si++) {
    $ct_overrides[] = '<Override PartName="/xl/worksheets/sheet' . $si . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
}
$content_types_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . implode('', $ct_overrides)
    . '</Types>';

$root_rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$wb_sheets = [];
$wb_rels   = [];
foreach ($sheets_xml as $idx => $sh) {
    $sid         = $idx + 1;
    $wb_sheets[] = '<sheet name="' . xlsx_xml_escape($sh['name']) . '" sheetId="' . $sid . '" r:id="rId' . $sid . '"/>';
    $wb_rels[]   = '<Relationship Id="rId' . $sid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sid . '.xml"/>';
}
$rid_styles = $num_sheets + 1;
$rid_ss     = $num_sheets + 2;
$wb_rels[]  = '<Relationship Id="rId' . $rid_styles . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
$wb_rels[]  = '<Relationship Id="rId' . $rid_ss . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

$workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<workbookPr/>'
    . '<sheets>' . implode('', $wb_sheets) . '</sheets>'
    . '<calcPr calcId="191029" fullCalcOnLoad="1"/>'
    . '</workbook>';

$workbook_rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . implode('', $wb_rels)
    . '</Relationships>';

$ss_items = [];
foreach ($ss_list as $txt) {
    $ss_items[] = '<si><t xml:space="preserve">' . xlsx_xml_escape($txt) . '</t></si>';
}
$shared_strings_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . (int)$ss_refs . '" uniqueCount="' . count($ss_items) . '">'
    . implode('', $ss_items)
    . '</sst>';

$tmp_xlsx = tempnam(sys_get_temp_dir(), 'sidripp_xlsx_');
$zip      = new ZipArchive();
if ($zip->open($tmp_xlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die('Gagal membuat arsip file Excel (.xlsx).');
}

$zip->addFromString('[Content_Types].xml', $content_types_xml);
$zip->addFromString('_rels/.rels', $root_rels_xml);
$zip->addFromString('xl/workbook.xml', $workbook_xml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels_xml);
$zip->addFromString('xl/styles.xml', xlsx_build_styles_xml());
$zip->addFromString('xl/sharedStrings.xml', $shared_strings_xml);

foreach ($sheets_xml as $idx => $sh) {
    $zip->addFromString('xl/worksheets/sheet' . ($idx + 1) . '.xml', $sh['xml']);
}

$zip->close();

$download_name = $report_data['file_base_name'] . '.xlsx';

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($tmp_xlsx));
header('Cache-Control: max-age=0');
header('Pragma: public');

readfile($tmp_xlsx);
@unlink($tmp_xlsx);
exit();
