<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_login();

$stepUrl = '../../src/createDelivery/Create1.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Data pengirim hanya bisa dikirim dari formulir di halaman Buat pengiriman langkah 1.';
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

require_csrf();

$fields = ['id_pengirim', 'nama_pengirim', 'nomor_telepon', 'alamat_pengirim'];
$data = [];
foreach ($fields as $field) {
    $raw = $_POST[$field] ?? '';
    $data[$field] = is_string($raw) ? trim($raw) : '';
}

$errors = [];
if ($data['id_pengirim'] === '') {
    $errors['id_pengirim'] = 'ID pengirim wajib diisi dengan format PE diikuti 7 angka, contoh PE-0000001.';
} elseif (!preg_match('/^PE-[0-9]{7}$/', $data['id_pengirim'])) {
    $errors['id_pengirim'] = 'ID pengirim harus berformat PE diikuti 7 angka, contoh PE-0000001.';
}
if ($data['nama_pengirim'] === '') {
    $errors['nama_pengirim'] = 'Nama pengirim wajib diisi.';
} elseif (strlen($data['nama_pengirim']) > 100) {
    $errors['nama_pengirim'] = 'Nama pengirim maksimal 100 karakter.';
}
if ($data['nomor_telepon'] === '') {
    $errors['nomor_telepon'] = 'Nomor telepon pengirim wajib diisi.';
} elseif (!preg_match('/^[+0-9][0-9 +()-]{6,14}$/', $data['nomor_telepon'])) {
    $errors['nomor_telepon'] = 'Nomor telepon belum valid, gunakan angka 7 sampai 15 karakter, contoh 081234567890.';
}
if ($data['alamat_pengirim'] === '') {
    $errors['alamat_pengirim'] = 'Alamat penjemputan wajib diisi.';
} elseif (strlen($data['alamat_pengirim']) > 100) {
    $errors['alamat_pengirim'] = 'Alamat penjemputan maksimal 100 karakter.';
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $data;
    $_SESSION['flash_error'] = count($errors) === 1
        ? 'Ada 1 kolom yang perlu diperbaiki pada data pengirim.'
        : 'Ada ' . count($errors) . ' kolom yang perlu diperbaiki pada data pengirim.';
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

$_SESSION['shipment_sender'] = $data;
unset($_SESSION['shipment_receiver'], $_SESSION['form_errors'], $_SESSION['form_old']);

header('Location: ../../src/createDelivery/Create2.php', true, 303);
exit;
