<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';

require_login();

$stepUrl = '../../src/updatingDelivery/Update3.php';

$backToStep = static function (string $resi, string $message) use ($stepUrl): void {
    $_SESSION['flash_error'] = $message;
    $target = $stepUrl;
    if ($resi !== '' && preg_match('/^RS-[0-9]{7}$/', $resi) === 1) {
        $target .= '?resi=' . rawurlencode($resi);
    }
    header('Location: ' . $target, true, 303);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $backToStep(trim((string) ($_GET['resi'] ?? '')), 'Perubahan lokasi hanya bisa dikirim dari formulir di halaman Update lokasi.');
}

require_csrf();

$resi = strtoupper(trim((string) ($_POST['nomor_resi'] ?? '')));
$position = trim((string) ($_POST['status'] ?? ''));
$allowed = ['PS-001', 'PS-002', 'PS-003', 'PS-004', 'PS-005', 'PS-006'];

if (!preg_match('/^RS-[0-9]{7}$/', $resi)) {
    $backToStep('', 'Nomor resi belum benar. Gunakan format RS diikuti 7 angka, contoh RS-0000001.');
}
if (!in_array($position, $allowed, true)) {
    $backToStep($resi, 'Lokasi terakhir belum dipilih dari daftar yang tersedia, sehingga tidak ada perubahan yang disimpan.');
}

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
} catch (RuntimeException $error) {
    $conn2->rollback();
    error_log('Update delivery rejected: ' . $error->getMessage());
    $backToStep($resi, $error->getMessage() === 'Resi belum memiliki data transit.'
        ? 'Resi ' . $resi . ' tidak ditemukan atau belum memiliki catatan transit, jadi lokasinya belum bisa diperbarui.'
        : 'Lokasi pengiriman gagal diperbarui karena masalah database. Coba lagi beberapa saat lagi.');
} catch (Throwable $error) {
    $conn2->rollback();
    error_log('Update delivery failed: ' . $error->getMessage());
    $backToStep($resi, 'Lokasi pengiriman gagal diperbarui karena masalah database. Coba lagi beberapa saat lagi.');
}

$_SESSION['flash'] = 'Status pengiriman ' . $resi . ' berhasil diperbarui.';
header('Location: ../../src/homepageAS/HomePageAdminStaff.php', true, 303);
exit;
