<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login('RL-001');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();

$resi = trim((string) ($_POST['nomor_resi'] ?? ''));
if (!preg_match('/^RS-[0-9]{7}$/', $resi)) exit('Nomor resi tidak valid.');
$stmt = $conn2->prepare('DELETE FROM transit WHERE nomor_resi = ?');
$stmt->bind_param('s', $resi);
$stmt->execute();
header('Location: ../../src/homepageAS/HomePageAdminStaff.php?deleted=1', true, 303);
exit;
