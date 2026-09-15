<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

const FORM_URL = '../../src/mendaftarPelanggan/pelanggan.php';
const DASHBOARD_URL = '../../src/homepageAS/HomePageAdminStaff.php';

/* Batas kolom mengikuti skema tabel pelanggan:
   nama_pelanggan varchar(40), alamat_jalan_pelanggan varchar(20),
   alamat_kecamatan_pelanggan varchar(20), alamat_kota_pelanggan varchar(20),
   nomor_telpon varchar(15). */
$fieldRules = [
    'nama_pelanggan' => ['label' => 'Nama pelanggan', 'max' => 40],
    'alamat_jalan_pelanggan' => ['label' => 'Alamat jalan', 'max' => 20],
    'alamat_kecamatan_pelanggan' => ['label' => 'Alamat kecamatan', 'max' => 20],
    'alamat_kota_pelanggan' => ['label' => 'Alamat kota', 'max' => 20],
    'nomor_telpon' => ['label' => 'Nomor telepon', 'max' => 15],
];

/**
 * Hitung panjang teks dalam karakter (bukan byte) agar cocok dengan batas kolom
 * VARCHAR pada MySQL. mbstring tidak selalu tersedia di server, jadi disediakan cadangan.
 */
function jumlahKarakter(string $teks): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($teks, 'UTF-8');
    }
    $jumlah = preg_match_all('/./us', $teks);
    return $jumlah === false ? strlen($teks) : $jumlah;
}

/**
 * Kembalikan pengguna ke formulir pendaftaran dengan pesan kesalahan yang bisa dibaca
 * dan nilai yang sudah diketik, supaya isian tidak hilang.
 */
function backToForm(array $errors, array $values): void
{
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_values'] = $values;
    $_SESSION['flash_error'] = count($errors) === 1
        ? (string) reset($errors)
        : 'Ada ' . count($errors) . ' kolom yang perlu diperbaiki: ' . implode(' ', array_values($errors));
    header('Location: ' . FORM_URL, true, 303);
    exit;
}

$values = [];
$errors = [];

foreach ($fieldRules as $field => $rule) {
    $value = trim((string) ($_POST[$field] ?? ''));
    $values[$field] = $value;
    $length = jumlahKarakter($value);
    if ($value === '') {
        $errors[$field] = $rule['label'] . ' wajib diisi.';
        continue;
    }
    if ($length > $rule['max']) {
        $errors[$field] = $rule['label'] . ' maksimal ' . $rule['max'] . ' karakter, sedangkan data yang dikirim ' . $length . ' karakter.';
    }
}

if (!isset($errors['nomor_telpon'])) {
    $telepon = $values['nomor_telpon'];
    if (!preg_match('/^[+0-9][0-9 +()-]{6,14}$/', $telepon)) {
        $errors['nomor_telpon'] = 'Nomor telepon belum valid. Gunakan angka dengan panjang 7 sampai 15 karakter, hanya boleh memuat tanda plus, spasi, tanda kurang, dan tanda kurung. Contoh: +6281234567890.';
    }
}

if ($errors) {
    backToForm($errors, $values);
}

$last = $conn2->query('SELECT id_pelanggan FROM pelanggan ORDER BY id_pelanggan DESC LIMIT 1');
$lastRow = $last ? $last->fetch_assoc() : null;
$next = $lastRow ? ((int) substr((string) $lastRow['id_pelanggan'], 3) + 1) : 1;
$id = sprintf('PE-%07d', $next);

$stmt = $conn2->prepare('INSERT INTO pelanggan (id_pelanggan,nama_pelanggan,alamat_jalan_pelanggan,alamat_kecamatan_pelanggan,alamat_kota_pelanggan,nomor_telpon) VALUES (?,?,?,?,?,?)');
if (!$stmt) {
    error_log('Tambah pelanggan gagal menyiapkan statement: ' . $conn2->error);
    backToForm(['__form__' => 'Data pelanggan gagal disimpan. Coba beberapa saat lagi.'], $values);
}
$stmt->bind_param('ssssss', $id, $values['nama_pelanggan'], $values['alamat_jalan_pelanggan'], $values['alamat_kecamatan_pelanggan'], $values['alamat_kota_pelanggan'], $values['nomor_telpon']);
$tersimpan = $stmt->execute();
if (!$tersimpan) {
    error_log('Tambah pelanggan gagal: ' . $stmt->error);
    backToForm(['__form__' => 'Data pelanggan gagal disimpan. Coba beberapa saat lagi.'], $values);
}

$_SESSION['flash'] = 'Pelanggan ' . $id . ' bernama ' . $values['nama_pelanggan'] . ' berhasil didaftarkan.';
header('Location: ' . DASHBOARD_URL, true, 303);
exit;
