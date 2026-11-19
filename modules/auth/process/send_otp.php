<?php
/**
 * BACKEND MODUL AUTH - TAHAP 1: KIRIM KODE OTP RESET KATA SANDI (BREVO API cURL)
 * File: modules/auth/process/send_otp.php
 * Paradigma: PHP Native Prosedural Modular (tanpa Composer/library eksternal)
 * Fungsi: Memvalidasi email terdaftar di tabel users, membuat OTP 6 digit di session
 *         (berlaku 10 menit), dan mengirimkannya melalui Brevo Transactional Email API.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../config/bootstrap.php';

// 1. Keamanan method: Hanya menerima request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php");
    exit();
}

// 2. Tangkap, bersihkan spasi, dan validasi format email
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../forgot_password.php?error=" . urlencode("Format email tidak valid. Masukkan alamat email yang benar."));
    exit();
}

// 3. Cek apakah email terdaftar pada tabel users di PostgreSQL Supabase
$query_user = "SELECT id_user, nama, email FROM users WHERE LOWER(email) = LOWER($1) LIMIT 1";
$res_user   = pg_query_params($conn, $query_user, [$email]);

if (!$res_user || pg_num_rows($res_user) === 0) {
    header("Location: ../forgot_password.php?error=" . urlencode("Email tidak terdaftar di sistem SI-DRIPP."));
    exit();
}

$user = pg_fetch_assoc($res_user);

// 4. Pastikan konfigurasi Brevo di .env / environment hosting sudah diisi
$brevo_api_key      = env_get('BREVO_API_KEY', '');
$brevo_sender_email = env_get('BREVO_SENDER_EMAIL', '');
$brevo_sender_name  = env_get('BREVO_SENDER_NAME', 'Admin SI-DRIPP');

if (
    $brevo_api_key === '' ||
    $brevo_api_key === 'ISI_API_KEY_BREVO_KAMU' ||
    $brevo_sender_email === '' ||
    $brevo_sender_email === 'ISI_EMAIL_SENDER_TERVERIFIKASI_DI_BREVO'
) {
    header("Location: ../forgot_password.php?error=" . urlencode("Konfigurasi Brevo API belum diisi. Lengkapi BREVO_API_KEY dan BREVO_SENDER_EMAIL pada file .env atau environment server."));
    exit();
}

if (!function_exists('curl_init')) {
    header("Location: ../forgot_password.php?error=" . urlencode("Ekstensi PHP cURL belum aktif di server. Aktifkan extension=curl pada php.ini."));
    exit();
}

// 5. Generate kode OTP 6 digit acak & simpan ke session (berlaku 10 menit / 600 detik)
$otp = (string) random_int(100000, 999999);
$_SESSION['reset_otp']     = $otp;
$_SESSION['reset_email']   = $user['email'];
$_SESSION['reset_expires'] = time() + 600;

// 6. Susun isi email HTML sederhana yang rapi dan mudah dibaca
$nama_safe = htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8');
$otp_safe  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

$html_content = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kode OTP Reset Kata Sandi</title>
</head>
<body style="margin:0;padding:24px;background-color:#faf6f0;font-family:Arial,Helvetica,sans-serif;color:#2b2118;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;margin:0 auto;background-color:#ffffff;border:1px solid #eadcc9;border-radius:14px;overflow:hidden;">
    <tr>
      <td style="padding:24px 28px;background-color:#b45309;color:#ffffff;">
        <h2 style="margin:0;font-size:20px;font-weight:700;">SI-DRIPP · MKP Store</h2>
        <p style="margin:6px 0 0;font-size:13px;opacity:0.9;">Pemulihan Kata Sandi Akun</p>
      </td>
    </tr>
    <tr>
      <td style="padding:28px;">
        <p style="margin:0 0 12px;font-size:15px;">Halo, <strong>{$nama_safe}</strong>,</p>
        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#57473a;">
          Kami menerima permintaan pengaturan ulang kata sandi untuk akun SI-DRIPP Anda. Gunakan kode OTP 6 digit berikut untuk melanjutkan proses verifikasi:
        </p>
        <div style="margin:0 0 20px;padding:18px;background-color:#fdf6ec;border:1px dashed #d97706;border-radius:10px;text-align:center;">
          <span style="display:inline-block;font-size:32px;font-weight:800;letter-spacing:8px;color:#9a3412;font-family:monospace;">{$otp_safe}</span>
        </div>
        <p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#57473a;">
          <strong>Penting:</strong> Kode OTP ini hanya berlaku selama <strong>10 menit</strong>. Jangan bagikan kode ini kepada siapa pun.
        </p>
        <p style="margin:0;font-size:12px;line-height:1.6;color:#8c7a6b;">
          Jika Anda tidak merasa meminta pengaturan ulang kata sandi, abaikan email ini dan kata sandi akun Anda akan tetap aman.
        </p>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 28px;background-color:#faf6f0;border-top:1px solid #eadcc9;font-size:12px;color:#8c7a6b;text-align:center;">
        &copy; 2026 CV Mitra Kembar Pradipta · SI-DRIPP Malang
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

$payload = [
    'sender' => [
        'name'  => $brevo_sender_name,
        'email' => $brevo_sender_email,
    ],
    'to' => [
        [
            'email' => $user['email'],
            'name'  => $user['nama'],
        ],
    ],
    'subject'     => 'Kode OTP Reset Kata Sandi - SI-DRIPP MKP Store',
    'htmlContent' => $html_content,
];

// 7. Kirim request POST ke Brevo Transactional Email API menggunakan PHP cURL
$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'api-key: ' . $brevo_api_key,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
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
$http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 8. Tangani error koneksi cURL
if ($curl_errno !== 0) {
    unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
    header("Location: ../forgot_password.php?error=" . urlencode("Gagal terhubung ke server email Brevo (cURL Error: {$curl_error})."));
    exit();
}

// 9. Tangani respons HTTP dari Brevo (sukses = 201 Created)
if ($http_code !== 201) {
    unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_expires']);

    $brevo_msg = 'Respons tidak dikenali dari server Brevo.';
    $decoded   = json_decode((string)$response, true);
    if (is_array($decoded)) {
        if (!empty($decoded['message'])) {
            $brevo_msg = $decoded['message'];
        } elseif (!empty($decoded['code'])) {
            $brevo_msg = $decoded['code'];
        }
    } elseif (is_string($response) && trim($response) !== '') {
        $brevo_msg = substr(trim($response), 0, 180);
    }

    header("Location: ../forgot_password.php?error=" . urlencode("Gagal mengirim kode OTP (HTTP {$http_code}): {$brevo_msg}"));
    exit();
}

// 10. Berhasil (HTTP 201) -> Arahkan ke halaman verifikasi OTP & simpan kata sandi baru
header("Location: ../reset_password.php?pesan=" . urlencode("Kode OTP 6 digit telah dikirim ke email Anda. Berlaku selama 10 menit."));
exit();
