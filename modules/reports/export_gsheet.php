<?php
/**
 * BACKEND MODUL LAPORAN — EKSPOR LANGSUNG KE GOOGLE SHEETS (GOOGLE APPS SCRIPT WEB APP)
 * File: modules/reports/export_gsheet.php
 * Paradigma: PHP Native Prosedural (cURL POST JSON tanpa Composer/library eksternal)
 * Fungsi: Menyusun payload JSON multi-sheet dari report_data_helper.php, mengirimkannya
 *         ke Google Apps Script Web App (GSHEET_WEBAPP_URL) dengan CURLOPT_FOLLOWLOCATION,
 *         lalu mengembalikan respons JSON {status, message, url} ke frontend.
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/report_data_helper.php';

header('Content-Type: application/json; charset=utf-8');

function gsheet_json_out($http_status, $payload) {
    http_response_code((int)$http_status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// 1. Validasi sesi login & otorisasi khusus Super Admin (mengembalikan JSON agar AJAX rapi)
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    gsheet_json_out(401, [
        'status'  => 'error',
        'message' => 'Sesi login tidak aktif. Silakan login kembali sebagai Super Admin.',
        'url'     => '',
    ]);
}

$user_role = isset($_SESSION['role']) ? normalisasi_role($_SESSION['role']) : '';
if ($user_role !== 'super_admin') {
    gsheet_json_out(403, [
        'status'  => 'error',
        'message' => 'Akses ditolak. Fitur ekspor Google Sheets hanya tersedia untuk Super Admin / Owner.',
        'url'     => '',
    ]);
}

// 2. Pastikan ekstensi cURL aktif dan konfigurasi .env tersedia
if (!function_exists('curl_init')) {
    gsheet_json_out(500, [
        'status'  => 'error',
        'message' => 'Ekstensi PHP cURL belum aktif di server. Aktifkan extension=curl pada php.ini.',
        'url'     => '',
    ]);
}

$webapp_url   = env_get('GSHEET_WEBAPP_URL', '');
$secret_token = env_get('GSHEET_SECRET_TOKEN', 'SIDRIPP_MKP_2026');

if (
    $webapp_url === '' ||
    strpos($webapp_url, 'ISI_DEPLOYMENT_ID') !== false ||
    !filter_var($webapp_url, FILTER_VALIDATE_URL)
) {
    gsheet_json_out(500, [
        'status'  => 'error',
        'message' => 'Konfigurasi GSHEET_WEBAPP_URL belum diisi dengan URL Web App Google Apps Script yang valid pada file .env atau environment server.',
        'url'     => '',
    ]);
}

// 3. Gabungkan parameter filter dari GET, POST, atau JSON body
$input_params = array_merge($_GET, $_POST);
$raw_body     = file_get_contents('php://input');
if (is_string($raw_body) && trim($raw_body) !== '') {
    $json_in = json_decode($raw_body, true);
    if (is_array($json_in)) {
        $input_params = array_merge($input_params, $json_in);
    }
}

$filter      = report_parse_filter($input_params);
$report_data = report_build_multisheet_data($conn, $filter);

// 4. Bentuk payload JSON sesuai kontrak Google Apps Script Web App
$payload_sheets = [];
foreach ($report_data['sheets'] as $sh) {
    $formatted_rows = [];
    foreach ($sh['rows'] as $row_item) {
        $formatted_rows[] = [
            'type'   => $row_item['type'],
            'values' => $row_item['values'],
        ];
    }

    $payload_sheets[] = [
        'sheet_name' => $sh['sheet_name'],
        'title'      => $sh['title'],
        'subtitle'   => $sh['subtitle'],
        'headers'    => $sh['headers'],
        'rows'       => $formatted_rows,
    ];
}

$payload = [
    'token'     => $secret_token,
    'file_name' => $report_data['file_base_name'],
    'sheets'    => $payload_sheets,
];

$json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
if ($json_payload === false) {
    gsheet_json_out(500, [
        'status'  => 'error',
        'message' => 'Gagal menyusun payload JSON untuk Google Sheets.',
        'url'     => '',
    ]);
}

// 5. Kirim POST JSON via PHP cURL ke Google Apps Script Web App
$ch = curl_init($webapp_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $json_payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);

// Penyesuaian sertifikat SSL (Laragon Windows lokal vs Linux Render produksi)
$curl_cainfo    = trim((string)ini_get('curl.cainfo'));
$openssl_cafile = trim((string)ini_get('openssl.cafile'));
$is_windows     = (stripos(PHP_OS, 'WIN') === 0);

$ca_valid_file = null;
if ($curl_cainfo !== '' && file_exists($curl_cainfo)) {
    $ca_valid_file = $curl_cainfo;
} elseif ($openssl_cafile !== '' && file_exists($openssl_cafile)) {
    $ca_valid_file = $openssl_cafile;
}

if ($ca_valid_file !== null) {
    curl_setopt($ch, CURLOPT_CAINFO, $ca_valid_file);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
} elseif (($curl_cainfo !== '' && !file_exists($curl_cainfo)) || $is_windows) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
} else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}

$response   = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$http_code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_errno !== 0) {
    gsheet_json_out(502, [
        'status'  => 'error',
        'message' => 'Gagal terhubung ke Google Apps Script Web App (cURL Error: ' . $curl_error . ').',
        'url'     => '',
    ]);
}

$decoded = json_decode((string)$response, true);

if (!is_array($decoded)) {
    $snippet = trim(strip_tags((string)$response));
    $snippet = substr(preg_replace('/\s+/', ' ', $snippet), 0, 180);
    gsheet_json_out(502, [
        'status'  => 'error',
        'message' => 'Respons dari Google Apps Script tidak berformat JSON (HTTP ' . $http_code . '). Pastikan Web App di-deploy dengan akses "Anyone".' . ($snippet !== '' ? ' Detail: ' . $snippet : ''),
        'url'     => '',
    ]);
}

$gs_url = '';
if (!empty($decoded['url'])) {
    $gs_url = (string)$decoded['url'];
} elseif (!empty($decoded['sheet_url'])) {
    $gs_url = (string)$decoded['sheet_url'];
} elseif (!empty($decoded['spreadsheetUrl'])) {
    $gs_url = (string)$decoded['spreadsheetUrl'];
}

$is_success = (
    (isset($decoded['status']) && strtolower((string)$decoded['status']) === 'success') ||
    (isset($decoded['success']) && $decoded['success'] === true) ||
    ($http_code >= 200 && $http_code < 300 && $gs_url !== '')
);

if (!$is_success) {
    $err_msg = !empty($decoded['message'])
        ? (string)$decoded['message']
        : (!empty($decoded['error']) ? (string)$decoded['error'] : 'Google Apps Script mengembalikan status gagal (HTTP ' . $http_code . ').');

    gsheet_json_out(502, [
        'status'  => 'error',
        'message' => $err_msg,
        'url'     => '',
    ]);
}

$ok_msg = !empty($decoded['message'])
    ? (string)$decoded['message']
    : 'Laporan berhasil disimpan ke Google Drive sebagai Google Sheets (' . count($payload_sheets) . ' worksheet).';

gsheet_json_out(200, [
    'status'    => 'success',
    'message'   => $ok_msg,
    'url'       => $gs_url,
    'file_name' => $report_data['file_base_name'],
]);
