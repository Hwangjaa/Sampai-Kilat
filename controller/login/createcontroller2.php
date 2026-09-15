<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

$required = ['nama_penerima','no_penerima','alamat_penerima','ID_Pelanggan','no_rumah','alamat_kota','alamat_kecamatan','tanggal_pengiriman','ID_Servis'];
$data = [];
foreach ($required as $field) {
    $value = trim((string) ($_POST[$field] ?? ''));
    if ($value === '' || strlen($value) > 100) exit('Input tidak valid.');
    $data[$field] = $value;
}
if (!preg_match('/^PE-[0-9]{7}$/', $data['ID_Pelanggan']) || !preg_match('/^SR-[0-9]{7}$/', $data['ID_Servis'])) exit('ID pelanggan atau servis tidak valid.');
if (!preg_match('/^[+0-9][0-9 +()-]{6,14}$/', $data['no_penerima'])) exit('Nomor telepon tidak valid.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal_pengiriman'])) exit('Tanggal tidak valid.');

$conn2->begin_transaction();
$last = $conn2->query("SELECT nomor_resi FROM resi ORDER BY nomor_resi DESC LIMIT 1 FOR UPDATE")->fetch_assoc();
$next = $last ? ((int) substr($last['nomor_resi'], 3) + 1) : 1;
$resi = sprintf('RS-%07d', $next);
$stmt = $conn2->prepare('INSERT INTO resi (nomor_resi,id_pelanggan,nama_penerima,alamat_jalan_penerima,nomor_rumah_penerima,alamat_kota_penerima,alamat_kecamatan_penerima,nomor_telpon_penerima,tanggal_permintaan_pengiriman,id_servis) VALUES (?,?,?,?,?,?,?,?,?,?)');
$stmt->bind_param('ssssssssss', $resi, $data['ID_Pelanggan'], $data['nama_penerima'], $data['alamat_penerima'], $data['no_rumah'], $data['alamat_kota'], $data['alamat_kecamatan'], $data['no_penerima'], $data['tanggal_pengiriman'], $data['ID_Servis']);
if (!$stmt->execute()) { $conn2->rollback(); http_response_code(500); exit('Data gagal disimpan.'); }
$conn2->commit();
header('Location: ../../src/homepageAS/HomePageAdminStaff.php?created=1', true, 303);
exit;
