<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controller/login/bootstrap.php';
require_once __DIR__ . '/../../controller/login/koneksiDB2.php';

require_login();

if (empty($_SESSION['shipment_sender']) || empty($_SESSION['shipment_receiver'])) {
    $_SESSION['flash_error'] = 'Lengkapi data pengirim dan penerima lebih dulu sebelum konfirmasi.';
    header('Location: Create1.php', true, 303);
    exit;
}

$flashError = (string) ($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_error']);

$sender = (array) $_SESSION['shipment_sender'];
$receiver = (array) $_SESSION['shipment_receiver'];

$username = (string) ($_SESSION['username'] ?? 'staf');
$nameParts = preg_split('/[^a-zA-Z0-9]+/', $username) ?: [];
$initials = strtoupper(substr((string) ($nameParts[0] ?? ''), 0, 1) . substr((string) ($nameParts[1] ?? ''), 0, 1));
if ($initials === '') {
    $initials = 'SK';
}

/* Real service record for the chosen service, plus the registered customer name for the sender id. */
$service = null;
$serviceStmt = $conn2->prepare(
    'SELECT s.id_servis, s.total_berat_paket, s.biaya, tp.desk_tipe_pengiriman, pkt.desk_tipe_paket,
            jp.desk_jenis_paket, jb.desk_jenis_pembayaran
     FROM servis s
     JOIN tipe_pengiriman tp ON tp.id_tipe_pengiriman = s.id_tipe_pengiriman
     JOIN tipe_paket pkt ON pkt.id_tipe_paket = s.id_tipe_paket
     JOIN jenis_paket jp ON jp.id_jenis_paket = s.id_jenis_paket
     JOIN jenis_pembayaran jb ON jb.id_jenis_pembayaran = s.id_jenis_pembayaran
     WHERE s.id_servis = ? LIMIT 1'
);
if ($serviceStmt !== false) {
    $serviceId = (string) ($receiver['id_servis'] ?? '');
    $serviceStmt->bind_param('s', $serviceId);
    $serviceStmt->execute();
    $service = $serviceStmt->get_result()->fetch_assoc() ?: null;
    $serviceStmt->close();
}
if ($service === null) {
    error_log('Create3: data servis tidak dapat dibaca untuk konfirmasi.');
}

$customer = null;
$customerStmt = $conn2->prepare('SELECT nama_pelanggan FROM pelanggan WHERE id_pelanggan = ? LIMIT 1');
if ($customerStmt !== false) {
    $senderId = (string) ($sender['id_pengirim'] ?? '');
    $customerStmt->bind_param('s', $senderId);
    $customerStmt->execute();
    $customer = $customerStmt->get_result()->fetch_assoc() ?: null;
    $customerStmt->close();
}

$formatBerat = static function ($berat): string {
    return rtrim(rtrim(number_format((float) $berat, 2, ',', '.'), '0'), ',');
};
$formatRupiah = static function ($biaya): string {
    return 'Rp' . number_format((float) $biaya, 0, ',', '.');
};
$formatTanggal = static function (string $tanggal): string {
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $value = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
    if ($value === false) {
        return $tanggal;
    }
    return $value->format('j') . ' ' . $bulan[(int) $value->format('n')] . ' ' . $value->format('Y');
};
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Langkah 3 dari 3 pembuatan pengiriman: periksa dan simpan data.">
  <title>Buat pengiriman: konfirmasi | SampaiKilat</title>
  <link rel="icon" href="../../assets/brand/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../../css/site-system.css">
  <link rel="stylesheet" href="../../css/internal.css">
</head>
<body>
<a class="skip-link" href="#utama">Lewati ke isi</a>

<header class="app-bar">
  <div class="container app-bar__inner">
    <a class="brand" href="../homepageAS/HomePageAdminStaff.php">
      <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
      <span class="brand__text">Sampai<em>Kilat</em></span>
    </a>
    <nav class="app-nav" aria-label="Navigasi staf">
      <a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a>
      <a href="../createDelivery/Create1.php" aria-current="page">Buat pengiriman</a>
      <a href="../updatingDelivery/Update3.php">Update lokasi</a>
      <a href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan</a>
    </nav>
    <div class="app-user">
      <span class="app-user__name"><span class="app-user__avatar" aria-hidden="true"><?= e($initials) ?></span><?= e($username) ?></span>
      <form method="post" action="../../controller/login/logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="btn btn--ghost btn--sm" type="submit">Keluar</button>
      </form>
    </div>
  </div>
</header>

<main id="utama" class="container app-main app-main--form">
  <nav aria-label="Remah roti">
    <ol class="breadcrumb">
      <li><a href="../../homepage.html">Beranda</a></li>
      <li><a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a></li>
      <li><a href="Create1.php">Buat pengiriman</a></li>
      <li aria-current="page">Konfirmasi</li>
    </ol>
  </nav>

  <div class="stack">
    <div class="page-head">
      <div>
        <p class="eyebrow">Langkah 3 dari 3</p>
        <h1>Konfirmasi pengiriman</h1>
        <p>Periksa kembali data berikut. Nomor resi baru dibuat server pada saat data disimpan.</p>
      </div>
    </div>

    <ol class="steps">
      <li class="steps__item steps__item--done"><span class="steps__num">Langkah 01</span>Data pengirim</li>
      <li class="steps__item steps__item--done"><span class="steps__num">Langkah 02</span>Data penerima</li>
      <li class="steps__item" aria-current="step"><span class="steps__num">Langkah 03</span>Konfirmasi</li>
    </ol>

    <?php if ($flashError !== ''): ?>
      <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Pengiriman belum tersimpan</strong><?= e($flashError) ?></span></p>
    <?php endif; ?>

    <section class="card" aria-labelledby="ringkasan-pengirim">
      <div class="card__head">
        <h2 id="ringkasan-pengirim">Pengirim</h2>
        <a class="btn btn--ghost btn--sm" href="Create1.php">Ubah</a>
      </div>
      <dl class="confirm-list">
        <div><dt>ID pengirim</dt><dd class="mono"><?= e((string) ($sender['id_pengirim'] ?? '')) ?></dd></div>
        <div><dt>Nama pengirim</dt><dd><?= e((string) ($sender['nama_pengirim'] ?? '')) ?></dd></div>
        <div><dt>Telepon pengirim</dt><dd class="mono"><?= e((string) ($sender['nomor_telepon'] ?? '')) ?></dd></div>
        <div><dt>Alamat penjemputan</dt><dd><?= e((string) ($sender['alamat_pengirim'] ?? '')) ?></dd></div>
        <?php if ($customer !== null): ?>
          <div><dt>Pelanggan terdaftar</dt><dd><?= e((string) $customer['nama_pelanggan']) ?></dd></div>
        <?php else: ?>
          <div><dt>Pelanggan terdaftar</dt><dd>Nama pelanggan tidak dapat dibaca saat ini. Keberadaan ID pengirim tetap diperiksa saat penyimpanan.</dd></div>
        <?php endif; ?>
      </dl>
    </section>

    <section class="card" aria-labelledby="ringkasan-penerima">
      <div class="card__head">
        <h2 id="ringkasan-penerima">Penerima dan layanan</h2>
        <a class="btn btn--ghost btn--sm" href="Create2.php">Ubah</a>
      </div>
      <dl class="confirm-list">
        <div><dt>Nama penerima</dt><dd><?= e((string) ($receiver['nama_penerima'] ?? '')) ?></dd></div>
        <div><dt>Telepon penerima</dt><dd class="mono"><?= e((string) ($receiver['no_penerima'] ?? '')) ?></dd></div>
        <div><dt>Alamat tujuan</dt><dd><?= e((string) ($receiver['alamat_penerima'] ?? '')) ?> <?= e((string) ($receiver['no_rumah'] ?? '')) ?>, <?= e((string) ($receiver['alamat_kecamatan'] ?? '')) ?>, <?= e((string) ($receiver['alamat_kota'] ?? '')) ?></dd></div>
        <div><dt>Tanggal permintaan</dt><dd><?= e($formatTanggal((string) ($receiver['tanggal_pengiriman'] ?? ''))) ?></dd></div>
        <div><dt>ID servis</dt><dd class="mono"><?= e((string) ($receiver['id_servis'] ?? '')) ?></dd></div>
        <?php if ($service !== null): ?>
          <div><dt>Layanan</dt><dd><?= e((string) $service['desk_tipe_pengiriman']) ?> / paket <?= e((string) $service['desk_tipe_paket']) ?> / jenis <?= e((string) $service['desk_jenis_paket']) ?> / pembayaran <?= e((string) $service['desk_jenis_pembayaran']) ?></dd></div>
          <div><dt>Berat tercatat</dt><dd><?= e($formatBerat($service['total_berat_paket'])) ?> kg</dd></div>
          <div><dt>Biaya pengiriman</dt><dd><?= e($formatRupiah($service['biaya'])) ?></dd></div>
        <?php else: ?>
          <div><dt>Layanan</dt><dd>Rincian layanan tidak dapat dibaca saat ini. ID servis tetap diperiksa saat penyimpanan.</dd></div>
        <?php endif; ?>
      </dl>
    </section>

    <p class="form-note"><span><strong>Nomor resi:</strong> dibuat otomatis oleh server dengan format RS diikuti 7 angka, di dalam satu transaksi bersama data pengiriman. <strong>Isi paket dan kurir</strong> ditetapkan otomatis dari data isi paket dan supir yang tersedia, serta posisi awal diambil dari data posisi paket.</span></p>

    <section class="card" aria-labelledby="simpan-title">
      <h2 id="simpan-title">Simpan pengiriman</h2>
      <p>Tekan Simpan pengiriman untuk membuat resi baru. Setelah tersimpan Anda kembali ke dashboard dan nomor resi tampil pada pesan berhasil.</p>
      <form method="post" action="../../controller/login/createcontroller3.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-actions">
          <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Simpan pengiriman</span></button>
          <a class="btn btn--secondary" href="Create2.php">Kembali</a>
        </div>
      </form>
    </section>

    <p class="muted">Kembali ke langkah 2 mempertahankan data penerima yang sudah diisi, jadi Anda hanya perlu memperbaiki bagian yang salah.</p>
  </div>
</main>

<footer class="site-footer">
  <div class="container site-footer__base">
    <p>Panel staf SampaiKilat. Semua perubahan tercatat pada database operasional.</p>
  </div>
</footer>

<script src="../../javascript/site.js" defer></script>
</body>
</html>
