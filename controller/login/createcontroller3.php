<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB2.php';

require_login();

$stepUrl = '../../src/createDelivery/Create3.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Pengiriman hanya bisa disimpan dari formulir konfirmasi di halaman Buat pengiriman langkah 3.';
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

require_csrf();

$sender = $_SESSION['shipment_sender'] ?? null;
$receiver = $_SESSION['shipment_receiver'] ?? null;
if (!is_array($sender) || !is_array($receiver)) {
    $_SESSION['flash_error'] = 'Data pengirim dan penerima belum lengkap, mulai lagi dari langkah 1.';
    header('Location: ../../src/createDelivery/Create1.php', true, 303);
    exit;
}

$usi = 'Pengiriman gagal disimpan. Coba lagi beberapa saat lagi.';
$resi = '';

try {
    $conn2->begin_transaction();
    $senderStmt = $conn2->prepare('SELECT id_pelanggan FROM pelanggan WHERE id_pelanggan = ? FOR UPDATE');
    $senderStmt->bind_param('s', $sender['id_pengirim']);
    $senderStmt->execute();
    if (!$senderStmt->get_result()->fetch_assoc()) { throw new RuntimeException('ID pengirim tidak terdaftar.'); }
    $serviceStmt = $conn2->prepare('SELECT id_servis FROM servis WHERE id_servis = ? FOR UPDATE');
    $serviceStmt->bind_param('s', $receiver['id_servis']);
    $serviceStmt->execute();
    if (!$serviceStmt->get_result()->fetch_assoc()) { throw new RuntimeException('ID servis tidak terdaftar.'); }
    $package = $conn2->query('SELECT id_isi_paket FROM isi_paket ORDER BY id_isi_paket LIMIT 1 FOR UPDATE')->fetch_assoc();
    $driver = $conn2->query('SELECT plat_nomor_kendaraan FROM supir ORDER BY plat_nomor_kendaraan LIMIT 1 FOR UPDATE')->fetch_assoc();
    $position = $conn2->query("SELECT id_posisi_terakhir_paket FROM posisi_paket WHERE posisi_terakhir = 'WH Tangerang' LIMIT 1 FOR UPDATE")->fetch_assoc();
    if (!$package || !$driver || !$position) { throw new RuntimeException('Referensi transit tidak tersedia.'); }
    $last = $conn2->query('SELECT nomor_resi FROM resi ORDER BY nomor_resi DESC LIMIT 1 FOR UPDATE')->fetch_assoc();
    $next = $last ? (int) substr($last['nomor_resi'], 3) + 1 : 1;
    if ($next > 9999999) { throw new RuntimeException('Nomor resi habis.'); }
    $resi = sprintf('RS-%07d', $next);
    $resiStmt = $conn2->prepare('INSERT INTO resi (nomor_resi, id_pelanggan, nama_penerima, alamat_jalan_penerima, nomor_rumah_penerima, alamat_kota_penerima, alamat_kecamatan_penerima, nomor_telpon_penerima, tanggal_permintaan_pengiriman, id_servis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $resiStmt->bind_param('ssssssssss', $resi, $sender['id_pengirim'], $receiver['nama_penerima'], $receiver['alamat_penerima'], $receiver['no_rumah'], $receiver['alamat_kota'], $receiver['alamat_kecamatan'], $receiver['no_penerima'], $receiver['tanggal_pengiriman'], $receiver['id_servis']);
    if (!$resiStmt->execute()) { throw new RuntimeException('Resi gagal disimpan.'); }
    $transitStmt = $conn2->prepare('INSERT INTO transit (nomor_resi, id_isi_paket, plat_nomor_kendaraan, tanggal_jam_pengiriman, id_posisi_terakhir_paket) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)');
    $transitStmt->bind_param('ssss', $resi, $package['id_isi_paket'], $driver['plat_nomor_kendaraan'], $position['id_posisi_terakhir_paket']);
    if (!$transitStmt->execute()) { throw new RuntimeException('Transit awal gagal disimpan.'); }
    $conn2->commit();
} catch (Throwable $error) {
    $conn2->rollback();
    error_log('Create delivery failed: ' . $error->getMessage());
    $reason = $error->getMessage();
    $userMessage = match ($reason) {
        'ID pengirim tidak terdaftar.' => 'ID pengirim tidak terdaftar pada data pelanggan. Perbaiki data pengirim lewat tautan Ubah pada bagian Pengirim, atau daftarkan pelanggan baru lebih dulu.',
        'ID servis tidak terdaftar.' => 'Layanan yang dipilih tidak terdaftar pada data servis. Perbaiki data penerima lewat tautan Ubah pada bagian Penerima dan layanan.',
        'Referensi transit tidak tersedia.' => 'Data isi paket, kurir, atau posisi awal belum tersedia di database, sehingga pengiriman belum bisa dibuat.',
        'Nomor resi habis.' => 'Nomor resi sudah mencapai batas maksimum. Hubungi administrator sistem.',
        'Resi gagal disimpan.', 'Transit awal gagal disimpan.' => 'Pengiriman gagal disimpan karena masalah database. Data Anda belum hilang, coba simpan lagi.',
        default => 'Pengiriman gagal disimpan. Coba lagi beberapa saat lagi.',
    };
    $_SESSION['flash_error'] = $userMessage;
    header('Location: ' . $stepUrl, true, 303);
    exit;
}

unset($_SESSION['shipment_sender'], $_SESSION['shipment_receiver']);
$_SESSION['flash'] = 'Pengiriman ' . $resi . ' berhasil dibuat.';
header('Location: ../../src/homepageAS/HomePageAdminStaff.php', true, 303);
exit;
