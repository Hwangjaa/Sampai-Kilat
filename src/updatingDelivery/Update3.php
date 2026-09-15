<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controller/login/bootstrap.php';
require_once __DIR__ . '/../../controller/login/koneksiDB2.php';

require_login();

$flash = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);
$flashError = (string) ($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_error']);

$username = (string) ($_SESSION['username'] ?? 'staf');
$nameParts = preg_split('/[^a-zA-Z0-9]+/', $username) ?: [];
$initials = strtoupper(substr((string) ($nameParts[0] ?? ''), 0, 1) . substr((string) ($nameParts[1] ?? ''), 0, 1));
if ($initials === '') {
    $initials = 'SK';
}

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$formatWaktu = static function (?string $timestamp) use ($bulan): string {
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }
    $value = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp);
    if ($value === false) {
        $value = DateTimeImmutable::createFromFormat('Y-m-d', $timestamp);
    }
    if ($value === false) {
        return $timestamp;
    }
    $label = $value->format('j') . ' ' . $bulan[(int) $value->format('n')] . ' ' . $value->format('Y');
    return strpos($timestamp, ':') !== false ? $label . ', ' . $value->format('H.i') : $label;
};
$relativeWaktu = static function (?string $timestamp): string {
    if ($timestamp === null || $timestamp === '') {
        return '';
    }
    $value = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp);
    if ($value === false) {
        return '';
    }
    $diff = (new DateTimeImmutable('now'))->getTimestamp() - $value->getTimestamp();
    if ($diff < 0) {
        return '';
    }
    if ($diff < 3600) {
        return max(1, (int) floor($diff / 60)) . ' menit lalu';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' jam lalu';
    }
    if ($diff < 2592000) {
        return (int) floor($diff / 86400) . ' hari lalu';
    }
    if ($diff < 31536000) {
        return (int) floor($diff / 2592000) . ' bulan lalu';
    }
    return (int) floor($diff / 31536000) . ' tahun lalu';
};
$formatBerat = static function ($berat): string {
    return rtrim(rtrim(number_format((float) $berat, 2, ',', '.'), '0'), ',');
};
$formatRupiah = static function ($biaya): string {
    return 'Rp' . number_format((float) $biaya, 0, ',', '.');
};

/* Whitelist of real positions used by the update form, read from posisi_paket. */
$positions = [];
$positionResult = $conn2->query('SELECT id_posisi_terakhir_paket, posisi_terakhir FROM posisi_paket ORDER BY id_posisi_terakhir_paket');
if ($positionResult !== false) {
    while ($row = $positionResult->fetch_assoc()) {
        $positions[] = $row;
    }
}
if ($positions === []) {
    error_log('Update3: daftar posisi paket tidak dapat dibaca.');
}

$resi = strtoupper(trim((string) ($_GET['resi'] ?? '')));
$resiValid = preg_match('/^RS-[0-9]{7}$/', $resi) === 1;
$lookupError = '';
$shipment = null;
$riwayat = [];
$posisiSekarang = null;
$isSampai = false;

if ($resi !== '' && !$resiValid) {
    $lookupError = 'Format nomor resi belum sesuai. Gunakan format RS diikuti 7 angka, contoh RS-0000001.';
} elseif ($resiValid) {
    $shipmentStmt = $conn2->prepare(
        'SELECT r.nomor_resi, r.nama_penerima, r.nomor_telpon_penerima, r.alamat_jalan_penerima, r.nomor_rumah_penerima,
                r.alamat_kecamatan_penerima, r.alamat_kota_penerima, r.tanggal_permintaan_pengiriman,
                p.nama_pelanggan AS nama_pengirim,
                s.id_servis, s.biaya, s.total_berat_paket, tp.desk_tipe_pengiriman, pkt.desk_tipe_paket
         FROM resi r
         JOIN pelanggan p ON p.id_pelanggan = r.id_pelanggan
         JOIN servis s ON s.id_servis = r.id_servis
         JOIN tipe_pengiriman tp ON tp.id_tipe_pengiriman = s.id_tipe_pengiriman
         JOIN tipe_paket pkt ON pkt.id_tipe_paket = s.id_tipe_paket
         WHERE r.nomor_resi = ? LIMIT 1'
    );
    if ($shipmentStmt !== false) {
        $shipmentStmt->bind_param('s', $resi);
        $shipmentStmt->execute();
        $shipment = $shipmentStmt->get_result()->fetch_assoc() ?: null;
        $shipmentStmt->close();
    } else {
        error_log('Update3: query ringkasan resi gagal disiapkan.');
    }

    if ($shipment === null) {
        $lookupError = 'Nomor resi ' . $resi . ' tidak ditemukan pada data pengiriman.';
    } else {
        $historyStmt = $conn2->prepare(
            'SELECT t.tanggal_jam_pengiriman, pp.id_posisi_terakhir_paket, pp.posisi_terakhir
             FROM transit t
             JOIN posisi_paket pp ON pp.id_posisi_terakhir_paket = t.id_posisi_terakhir_paket
             WHERE t.nomor_resi = ?
             GROUP BY t.tanggal_jam_pengiriman, pp.id_posisi_terakhir_paket, pp.posisi_terakhir
             ORDER BY t.tanggal_jam_pengiriman DESC'
        );
        if ($historyStmt !== false) {
            $historyStmt->bind_param('s', $resi);
            $historyStmt->execute();
            $riwayat = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $historyStmt->close();
        } else {
            error_log('Update3: query riwayat transit gagal disiapkan.');
        }

        $posisiSekarang = $riwayat[0] ?? null;
        $isSampai = $posisiSekarang !== null && (string) $posisiSekarang['posisi_terakhir'] === 'SAMPAI';
        if ($riwayat === []) {
            $lookupError = 'Resi ' . $resi . ' belum memiliki catatan transit, jadi lokasi terakhirnya belum bisa diperbarui.';
        }
    }
}

$langkah = ($shipment !== null && $riwayat !== []) ? 2 : 1;
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Cari nomor resi lalu perbarui lokasi terakhir paket dari posisi yang tersedia.">
  <title>Update lokasi paket | SampaiKilat</title>
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
      <a href="../createDelivery/Create1.php">Buat pengiriman</a>
      <a href="../updatingDelivery/Update3.php" aria-current="page">Update lokasi</a>
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
      <li aria-current="page">Update lokasi</li>
    </ol>
  </nav>

  <div class="stack">
    <div class="page-head">
      <div>
        <p class="eyebrow">Langkah <?= $langkah ?> dari 2</p>
        <h1>Update lokasi paket</h1>
        <p>Cari nomor resi lebih dulu supaya lokasi yang diperbarui pasti milik pengiriman yang benar.</p>
      </div>
    </div>

    <ol class="steps">
      <li class="steps__item<?= $langkah === 2 ? ' steps__item--done' : '' ?>"<?= $langkah === 1 ? ' aria-current="step"' : '' ?>><span class="steps__num">Langkah 01</span>Cari nomor resi</li>
      <li class="steps__item"<?= $langkah === 2 ? ' aria-current="step"' : '' ?>><span class="steps__num">Langkah 02</span>Perbarui lokasi</li>
    </ol>

    <?php if ($flash !== ''): ?>
      <p class="alert alert--success" role="status"><span><strong class="alert__title">Berhasil</strong><?= e($flash) ?></span></p>
    <?php endif; ?>

    <?php if ($flashError !== ''): ?>
      <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Update lokasi gagal</strong><?= e($flashError) ?></span></p>
    <?php endif; ?>

    <section class="card" aria-labelledby="cari-title">
      <div class="card__head">
        <h2 id="cari-title">Cari pengiriman</h2>
        <span class="muted">Hanya baca, tidak mengubah data</span>
      </div>
      <form class="step-form" method="get" action="Update3.php" id="resi-lookup" data-validate>
        <div data-form-alert hidden></div>
        <div class="field">
          <label class="field__label" for="resi">Nomor resi</label>
          <input class="input input--mono" type="text" id="resi" name="resi" value="<?= e($resi) ?>"
                 data-resi-input required pattern="RS-[0-9]{7}" maxlength="10" autocomplete="off" placeholder="RS-0000001"
                 aria-describedby="resi-help" aria-invalid="false">
          <p class="field__help" id="resi-help">Masukkan nomor resi pada bukti pengiriman. Format RS diikuti 7 angka, contoh RS-0000001.</p>
          <p class="field__error" data-error-for="resi" role="alert"></p>
        </div>
        <div class="form-actions">
          <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Cari resi</span></button>
          <a class="btn btn--secondary" href="../homepageAS/HomePageAdminStaff.php">Batal</a>
        </div>
      </form>

      <div class="stack stack--tight" id="recent-resi-wrap" hidden data-recent-resi-wrap>
        <p class="muted">Pencarian terakhir di perangkat ini</p>
        <div class="chip-row" id="recent-resi-list" data-recent-resi data-recent-form="resi-lookup" data-looked-up-resi="<?= e($resi) ?>"></div>
      </div>
    </section>

    <?php if ($lookupError !== ''): ?>
      <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Data pengiriman belum bisa dibuka</strong><?= e($lookupError) ?></span></p>
    <?php endif; ?>

    <?php if ($shipment !== null): ?>
      <section class="card" aria-labelledby="ringkasan-title">
        <div class="card__head">
          <h2 id="ringkasan-title">Ringkasan pengiriman <span class="mono"><?= e((string) $shipment['nomor_resi']) ?></span></h2>
          <?php if ($posisiSekarang === null): ?>
            <span class="badge badge--created"><span class="badge__dot" aria-hidden="true"></span>Belum ada pergerakan</span>
          <?php elseif ($isSampai): ?>
            <span class="badge badge--delivered"><span class="badge__dot" aria-hidden="true"></span>Sampai tujuan</span>
          <?php else: ?>
            <span class="badge badge--transit"><span class="badge__dot" aria-hidden="true"></span>Dalam perjalanan</span>
          <?php endif; ?>
        </div>
        <dl class="def-list">
          <div><dt>Posisi terakhir</dt><dd><?= $posisiSekarang !== null ? e((string) $posisiSekarang['posisi_terakhir']) : 'Belum ada catatan transit' ?></dd></div>
          <div><dt>Waktu pembaruan terakhir</dt><dd class="mono"><?php if ($posisiSekarang !== null): ?><?= e($formatWaktu((string) $posisiSekarang['tanggal_jam_pengiriman'])) ?><?php if ($relativeWaktu((string) $posisiSekarang['tanggal_jam_pengiriman']) !== ''): ?> (<?= e($relativeWaktu((string) $posisiSekarang['tanggal_jam_pengiriman'])) ?>)<?php endif; ?><?php else: ?>-<?php endif; ?></dd></div>
          <div><dt>Pengirim</dt><dd><?= e((string) $shipment['nama_pengirim']) ?></dd></div>
          <div><dt>Penerima</dt><dd><?= e((string) $shipment['nama_penerima']) ?> · <span class="mono"><?= e((string) $shipment['nomor_telpon_penerima']) ?></span></dd></div>
          <div><dt>Kota tujuan</dt><dd><?= e((string) $shipment['alamat_kota_penerima']) ?>, <?= e((string) $shipment['alamat_kecamatan_penerima']) ?></dd></div>
          <div><dt>Alamat tujuan</dt><dd>Jl. <?= e((string) $shipment['alamat_jalan_penerima']) ?> <?= e((string) $shipment['nomor_rumah_penerima']) ?></dd></div>
          <div><dt>Layanan</dt><dd><span class="mono"><?= e((string) $shipment['id_servis']) ?></span> · <?= e((string) $shipment['desk_tipe_pengiriman']) ?> · paket <?= e((string) $shipment['desk_tipe_paket']) ?></dd></div>
          <div><dt>Berat dan biaya</dt><dd><?= e($formatBerat($shipment['total_berat_paket'])) ?> kg · <?= e($formatRupiah($shipment['biaya'])) ?></dd></div>
          <div><dt>Tanggal permintaan</dt><dd><?= e($formatWaktu((string) $shipment['tanggal_permintaan_pengiriman'])) ?></dd></div>
        </dl>
      </section>

      <section class="card" aria-labelledby="riwayat-title">
        <div class="card__head">
          <h2 id="riwayat-title">Riwayat transit</h2>
          <span class="muted"><?= count($riwayat) ?> catatan, terbaru di atas</span>
        </div>
        <?php if ($riwayat !== []): ?>
          <ol class="timeline">
            <?php foreach ($riwayat as $index => $event): ?>
              <li class="timeline__item<?= $index === 0 ? ' timeline__item--current' : '' ?>">
                <span class="timeline__marker" aria-hidden="true"></span>
                <div class="timeline__body">
                  <span class="timeline__label"><?= e((string) $event['posisi_terakhir']) ?></span>
                  <span class="timeline__meta mono"><?= e($formatWaktu((string) $event['tanggal_jam_pengiriman'])) ?></span>
                  <span class="timeline__meta"><?= e($relativeWaktu((string) $event['tanggal_jam_pengiriman'])) ?></span>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="alert alert--warn"><span><strong class="alert__title">Belum ada catatan transit</strong>Resi ini terdaftar, tetapi belum ada pergerakan paket yang tercatat sehingga lokasinya belum dapat diperbarui dari halaman ini.</span></p>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($shipment !== null && $riwayat !== [] && $positions !== []): ?>
      <section class="card" aria-labelledby="perbarui-title">
        <div class="card__head">
          <h2 id="perbarui-title">Perbarui lokasi paket</h2>
          <span class="muted">Resi <span class="mono"><?= e((string) $shipment['nomor_resi']) ?></span></span>
        </div>
        <form class="step-form" method="post" action="../../controller/login/updateDelivery.php" data-validate>
          <div data-form-alert hidden></div>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="nomor_resi" value="<?= e((string) $shipment['nomor_resi']) ?>">

          <div class="field">
            <label class="field__label" for="status">Lokasi terakhir paket</label>
            <select class="select" id="status" name="status" required
                    data-position-select data-current-id="<?= e((string) ($posisiSekarang['id_posisi_terakhir_paket'] ?? '')) ?>"
                    aria-describedby="status-help status-ringkas">
              <option value="">Pilih lokasi</option>
              <?php foreach ($positions as $position): ?>
                <option value="<?= e((string) $position['id_posisi_terakhir_paket']) ?>"
                        <?= (string) $position['id_posisi_terakhir_paket'] === (string) ($posisiSekarang['id_posisi_terakhir_paket'] ?? '') ? 'selected' : '' ?>><?= e((string) $position['id_posisi_terakhir_paket'] . ' - ' . $position['posisi_terakhir']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="field__help" id="status-help">WH adalah gudang. Urutan umum perjalanan: WH Tangerang, WH Jakarta, WH Depok, WH Bekasi, WH Bogor, lalu SAMPAI bila paket sudah diterima penerima. Pilih lokasi tempat paket berada sekarang.</p>
            <p class="field__help" id="status-ringkas" role="status" data-position-summary>Memuat ringkasan pilihan lokasi.</p>
            <p class="field__error" data-error-for="status" role="alert"></p>
          </div>

          <p class="form-note"><span><strong>Resi <?= e((string) $shipment['nomor_resi']) ?>:</strong> menyimpan akan mencatat lokasi baru beserta waktu pembaruan sekarang pada catatan transit resi ini. Periksa nama penerima dan kota tujuan di atas sebelum menyimpan.</span></p>

          <div class="form-actions">
            <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Simpan update lokasi</span></button>
            <a class="btn btn--secondary" href="../homepageAS/HomePageAdminStaff.php">Batal</a>
          </div>
        </form>
      </section>
    <?php elseif ($shipment !== null && $positions === []): ?>
      <p class="alert alert--danger" role="alert"><span><strong class="alert__title">Daftar lokasi tidak dapat dimuat</strong>Perbarui halaman ini beberapa saat lagi sebelum mengubah lokasi paket.</span></p>
    <?php endif; ?>

    <p class="muted">Riwayat berasal dari pembaruan petugas gudang. Pembaruan hanya tersedia untuk resi yang sudah memiliki catatan transit.</p>
  </div>
</main>

<footer class="site-footer">
  <div class="container site-footer__base">
    <p>Panel staf SampaiKilat. Semua perubahan tercatat pada database operasional.</p>
  </div>
</footer>

<script src="../../javascript/site.js" defer></script>
<script src="../../javascript/pages/shipment.js" defer></script>
</body>
</html>
