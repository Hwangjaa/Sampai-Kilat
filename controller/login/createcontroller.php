<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();
$fields = ['id_pengirim', 'nama_pengirim', 'nomor_telepon', 'alamat_pengirim'];
$data = [];
foreach ($fields as $field) {
    $value = trim((string) ($_POST[$field] ?? ''));
    if ($value === '' || strlen($value) > 100) { http_response_code(422); exit('Input pengirim tidak valid.'); }
    $data[$field] = $value;
}
if (!preg_match('/^PE-[0-9]{7}$/', $data['id_pengirim']) || !preg_match('/^[+0-9][0-9 +()-]{6,14}$/', $data['nomor_telepon'])) { http_response_code(422); exit('ID atau nomor telepon tidak valid.'); }
$_SESSION['shipment_sender'] = $data;
unset($_SESSION['shipment_receiver']);
header('Location: ../../src/createDelivery/Create2.php', true, 303);
exit;
