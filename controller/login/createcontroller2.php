<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();
if (empty($_SESSION['shipment_sender'])) { header('Location: ../../src/createDelivery/Create1.php', true, 303); exit; }
$fields = ['nama_penerima' => 30, 'no_penerima' => 13, 'alamat_penerima' => 20, 'no_rumah' => 20, 'alamat_kota' => 20, 'alamat_kecamatan' => 20, 'tanggal_pengiriman' => 10, 'id_servis' => 10];
$data = [];
foreach ($fields as $field => $limit) {
    $value = trim((string) ($_POST[$field] ?? ''));
    if ($value === '' || strlen($value) > $limit) { http_response_code(422); exit('Input penerima tidak valid.'); }
    $data[$field] = $value;
}
$date = DateTime::createFromFormat('!Y-m-d', $data['tanggal_pengiriman']);
$dateErrors = DateTime::getLastErrors();
if (!preg_match('/^[+0-9][0-9 +()-]{6,12}$/', $data['no_penerima']) || !preg_match('/^SR-[0-9]{7}$/', $data['id_servis']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal_pengiriman']) || !$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) { http_response_code(422); exit('Nomor telepon, tanggal, atau servis tidak valid.'); }
$_SESSION['shipment_receiver'] = $data;
header('Location: ../../src/createDelivery/Create3.php', true, 303);
exit;
