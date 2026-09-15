<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

$resi = trim((string) ($_POST['nomor_resi'] ?? ''));
$status = trim((string) ($_POST['status'] ?? ''));
$allowed = ['WH Jakarta' => 'PS-002', 'WH Depok' => 'PS-003', 'WH Bogor' => 'PS-005', 'SAMPAI' => 'PS-006', 'WH Tangerang' => 'PS-001', 'WH Bekasi' => 'PS-004'];
if (!preg_match('/^RS-[0-9]{7}$/', $resi) || !isset($allowed[$status])) exit('Resi atau status tidak valid.');

$stmt = $conn2->prepare('UPDATE transit SET id_posisi_terakhir_paket = ?, tanggal_jam_pengiriman = CURRENT_TIMESTAMP WHERE nomor_resi = ?');
$position = $allowed[$status];
$stmt->bind_param('ss', $position, $resi);
if (!$stmt->execute() || $stmt->affected_rows < 1) { http_response_code(404); exit('Resi belum memiliki data transit.'); }
header('Location: ../../src/homepageAS/HomePageAdminStaff.php?updated=1', true, 303);
exit;
