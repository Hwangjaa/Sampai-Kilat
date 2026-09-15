<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

$fields = ['id_pengirim', 'nama_pengirim', 'nomor_telepon', 'alamat_pengirim'];
foreach ($fields as $field) {
    if (trim((string) ($_POST[$field] ?? '')) === '') exit('Semua field harus diisi.');
}
$_SESSION['shipment_sender'] = array_map(static fn($field) => trim((string) $_POST[$field]), $fields);
header('Location: ../../src/createDelivery/Create2.php', true, 303);
exit;
