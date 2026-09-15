<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';
require_login('RL-001');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
require_csrf();
$resi = trim((string) ($_POST['nomor_resi'] ?? ''));
if (!preg_match('/^RS-[0-9]{7}$/', $resi)) { http_response_code(422); exit('Nomor resi tidak valid.'); }
try {
    $conn2->begin_transaction();
    $stmt = $conn2->prepare('DELETE FROM transit WHERE nomor_resi = ?');
    $stmt->bind_param('s', $resi);
    if (!$stmt->execute()) { throw new RuntimeException('Hapus transit gagal.'); }
    if ($stmt->affected_rows < 1) { throw new OutOfBoundsException('Resi tidak ditemukan.'); }
    $conn2->commit();
} catch (OutOfBoundsException $error) {
    $conn2->rollback();
    http_response_code(404);
    exit('Resi tidak ditemukan.');
} catch (Throwable $error) {
    $conn2->rollback();
    error_log('Delete delivery failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Pengiriman gagal dihapus.');
}
$_SESSION['flash'] = 'Data transit ' . $resi . ' berhasil dihapus.';
header('Location: ../../src/homepageAS/HomePageAdminStaff.php', true, 303);
exit;
