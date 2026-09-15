<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';

require_login('RL-001');

$dashboardUrl = '../../src/homepageAS/HomePageAdminStaff.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Data transit hanya bisa dihapus dari formulir konfirmasi di halaman dashboard.';
    header('Location: ' . $dashboardUrl, true, 303);
    exit;
}

require_csrf();

$resi = strtoupper(trim((string) ($_POST['nomor_resi'] ?? '')));
if (!preg_match('/^RS-[0-9]{7}$/', $resi)) {
    $_SESSION['flash_error'] = 'Nomor resi belum benar. Gunakan format RS diikuti 7 angka, contoh RS-0000001, lalu ulangi penghapusan.';
    header('Location: ' . $dashboardUrl, true, 303);
    exit;
}

try {
    $conn2->begin_transaction();
    $stmt = $conn2->prepare('DELETE FROM transit WHERE nomor_resi = ?');
    $stmt->bind_param('s', $resi);
    if (!$stmt->execute()) { throw new RuntimeException('Hapus transit gagal.'); }
    if ($stmt->affected_rows < 1) { throw new OutOfBoundsException('Resi tidak ditemukan.'); }
    $conn2->commit();
} catch (OutOfBoundsException $error) {
    $conn2->rollback();
    error_log('Delete delivery rejected: ' . $error->getMessage());
    $_SESSION['flash_error'] = 'Resi ' . $resi . ' tidak ditemukan pada data transit, jadi tidak ada data yang dihapus.';
    header('Location: ' . $dashboardUrl, true, 303);
    exit;
} catch (Throwable $error) {
    $conn2->rollback();
    error_log('Delete delivery failed: ' . $error->getMessage());
    $_SESSION['flash_error'] = 'Data transit ' . $resi . ' gagal dihapus karena masalah database. Coba lagi beberapa saat lagi.';
    header('Location: ' . $dashboardUrl, true, 303);
    exit;
}

$_SESSION['flash'] = 'Data transit ' . $resi . ' berhasil dihapus.';
header('Location: ' . $dashboardUrl, true, 303);
exit;
