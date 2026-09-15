<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';

require_login();

$stepUrl = '../../src/createDelivery/Create2.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Data penerima hanya bisa dikirim dari formulir di halaman Buat pengiriman langkah 2.';
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

require_csrf();

if (empty($_SESSION['shipment_sender'])) {
    $_SESSION['flash_error'] = 'Isi data pengirim lebih dulu sebelum mengisi data penerima.';
    header('Location: ../../src/createDelivery/Create1.php', true, 303);
    exit;
}

$limits = [
    'nama_penerima' => 30,
    'no_penerima' => 13,
    'alamat_penerima' => 20,
    'no_rumah' => 20,
    'alamat_kota' => 20,
    'alamat_kecamatan' => 20,
];
$data = [];
foreach ($limits as $field => $limit) {
    $raw = $_POST[$field] ?? '';
    $data[$field] = is_string($raw) ? trim($raw) : '';
}
$rawDate = $_POST['tanggal_pengiriman'] ?? '';
$data['tanggal_pengiriman'] = is_string($rawDate) ? trim($rawDate) : '';
$rawService = $_POST['id_servis'] ?? '';
$data['id_servis'] = is_string($rawService) ? trim($rawService) : '';

$labels = [
    'nama_penerima' => 'Nama penerima',
    'no_penerima' => 'Nomor telepon penerima',
    'alamat_penerima' => 'Alamat jalan penerima',
    'no_rumah' => 'Nomor rumah',
    'alamat_kota' => 'Kota tujuan',
    'alamat_kecamatan' => 'Kecamatan tujuan',
];

$errors = [];
foreach ($limits as $field => $limit) {
    if ($data[$field] === '') {
        $errors[$field] = $labels[$field] . ' wajib diisi.';
    } elseif (strlen($data[$field]) > $limit) {
        $errors[$field] = $labels[$field] . ' maksimal ' . $limit . ' karakter.';
    }
}
if (!isset($errors['no_penerima']) && !preg_match('/^[+0-9][0-9 +()-]{6,12}$/', $data['no_penerima'])) {
    $errors['no_penerima'] = 'Nomor telepon penerima belum valid, gunakan angka 7 sampai 13 karakter, contoh 081234567890.';
}

$date = DateTime::createFromFormat('!Y-m-d', $data['tanggal_pengiriman']);
$dateErrors = DateTime::getLastErrors();
if ($data['tanggal_pengiriman'] === '') {
    $errors['tanggal_pengiriman'] = 'Tanggal permintaan pengiriman wajib diisi.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal_pengiriman']) || !$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
    $errors['tanggal_pengiriman'] = 'Tanggal permintaan pengiriman belum valid, gunakan format tanggal yang benar.';
}

if ($data['id_servis'] === '') {
    $errors['id_servis'] = 'Layanan pengiriman wajib dipilih.';
} elseif (!preg_match('/^SR-[0-9]{7}$/', $data['id_servis'])) {
    $errors['id_servis'] = 'ID servis harus berformat SR diikuti 7 angka, contoh SR-0000001.';
} elseif (!isset($errors['id_servis'])) {
    $serviceStmt = $conn2->prepare('SELECT id_servis FROM servis WHERE id_servis = ? LIMIT 1');
    if ($serviceStmt === false) {
        error_log('createcontroller2: pemeriksaan servis gagal disiapkan.');
        $errors['id_servis'] = 'Layanan belum bisa diverifikasi saat ini. Coba lagi beberapa saat lagi.';
    } else {
        $serviceStmt->bind_param('s', $data['id_servis']);
        $serviceStmt->execute();
        $exists = $serviceStmt->get_result()->fetch_assoc();
        $serviceStmt->close();
        if (!$exists) {
            $errors['id_servis'] = 'ID servis ' . $data['id_servis'] . ' tidak terdaftar pada data layanan.';
        }
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $data;
    $_SESSION['flash_error'] = count($errors) === 1
        ? 'Ada 1 kolom yang perlu diperbaiki pada data penerima.'
        : 'Ada ' . count($errors) . ' kolom yang perlu diperbaiki pada data penerima.';
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

$_SESSION['shipment_receiver'] = $data;
unset($_SESSION['form_errors'], $_SESSION['form_old']);

header('Location: ../../src/createDelivery/Create3.php', true, 303);
exit;
