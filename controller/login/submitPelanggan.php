<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

$fields = ['nama_pelanggan','alamat_jalan_pelanggan','alamat_kecamatan_pelanggan','alamat_kota_pelanggan','nomor_telpon'];
$data = [];
foreach ($fields as $field) {
    $value = trim((string) ($_POST[$field] ?? ''));
    if ($value === '' || strlen($value) > 40) exit('Input tidak valid.');
    $data[$field] = $value;
}
if (!preg_match('/^[+0-9][0-9 +()-]{6,14}$/', $data['nomor_telpon'])) exit('Nomor telepon tidak valid.');
$last = $conn2->query("SELECT id_pelanggan FROM pelanggan ORDER BY id_pelanggan DESC LIMIT 1")->fetch_assoc();
$next = $last ? ((int) substr($last['id_pelanggan'], 3) + 1) : 1;
$id = sprintf('PE-%07d', $next);
$stmt = $conn2->prepare('INSERT INTO pelanggan (id_pelanggan,nama_pelanggan,alamat_jalan_pelanggan,alamat_kecamatan_pelanggan,alamat_kota_pelanggan,nomor_telpon) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('ssssss', $id, $data['nama_pelanggan'], $data['alamat_jalan_pelanggan'], $data['alamat_kecamatan_pelanggan'], $data['alamat_kota_pelanggan'], $data['nomor_telpon']);
if (!$stmt->execute()) { http_response_code(500); exit('Data gagal disimpan.'); }
header('Location: ../../src/homepageAS/HomePageAdminStaff.php?customer_created=1', true, 303);
exit;
