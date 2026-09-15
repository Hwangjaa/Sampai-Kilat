<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();
$resi = trim((string) ($_POST['nomor_resi'] ?? ''));
$position = trim((string) ($_POST['status'] ?? ''));
$allowed = ['PS-001', 'PS-002', 'PS-003', 'PS-004', 'PS-005', 'PS-006'];
if (!preg_match('/^RS-[0-9]{7}$/', $resi) || !in_array($position, $allowed, true)) { http_response_code(422); exit('Resi atau lokasi tidak valid.'); }
try {
    $conn2->begin_transaction();
    $exists = $conn2->prepare('SELECT nomor_resi FROM transit WHERE nomor_resi = ? LIMIT 1 FOR UPDATE');
    $exists->bind_param('s', $resi);
    $exists->execute();
    if (!$exists->get_result()->fetch_assoc()) { throw new RuntimeException('Resi belum memiliki data transit.'); }
    $stmt = $conn2->prepare('UPDATE transit SET id_posisi_terakhir_paket = ?, tanggal_jam_pengiriman = CURRENT_TIMESTAMP WHERE nomor_resi = ?');
    $stmt->bind_param('ss', $position, $resi);
    if (!$stmt->execute()) { throw new RuntimeException('Update transit gagal.'); }
    $conn2->commit();
} catch (Throwable $error) {
    $conn2->rollback();
    error_log('Update delivery failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Pengiriman gagal diperbarui.');
}
$_SESSION['flash'] = 'Status pengiriman ' . $resi . ' berhasil diperbarui.';
header('Location: ../../src/homepageAS/HomePageAdminStaff.php', true, 303);
exit;
