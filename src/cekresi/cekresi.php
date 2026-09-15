<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controller/login/bootstrap.php';
require_once __DIR__ . '/../../controller/login/koneksiDB2.php';

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function format_waktu(?string $timestamp, array $bulan, bool $withTime = true): string
{
    if ($timestamp === null || $timestamp === '') {
        return '-';
    }
    $value = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp) ?: DateTimeImmutable::createFromFormat('Y-m-d', $timestamp);
    if ($value === false) {
        return $timestamp;
    }
    $label = $value->format('j') . ' ' . $bulan[(int) $value->format('n')] . ' ' . $value->format('Y');
    return $withTime ? $label . ', ' . $value->format('H.i') : $label;
}

function relative_waktu(?string $timestamp): string
{
    if ($timestamp === null || $timestamp === '') {
        return '';
    }
    $value = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp);
    if ($value === false) {
        return '';
    }
    $now = new DateTimeImmutable('now');
    $diff = $now->getTimestamp() - $value->getTimestamp();
    if ($diff < 0) {
        return '';
    }
    if ($diff < 3600) {
        $menit = max(1, (int) floor($diff / 60));
        return $menit . ' menit lalu';
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
}

$resiInput = '';
$error = '';
$notFound = '';
$shipment = null;
$timeline = [];
$lastEvent = null;
$currentPosition = '';
$isDelivered = false;
$currentStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resiInput = strtoupper(trim((string) ($_POST['nomor_resi'] ?? '')));

    if (!preg_match('/^RS-[0-9]{7}$/', $resiInput)) {
        $error = 'Nomor resi harus berformat RS diikuti 7 angka, contoh RS-0000001.';
    } else {
        $summary = $conn2->prepare(
            'SELECT r.nomor_resi, r.nama_penerima, r.tanggal_permintaan_pengiriman,
                    r.alamat_jalan_penerima, r.nomor_rumah_penerima, r.alamat_kecamatan_penerima, r.alamat_kota_penerima,
                    p.nama_pelanggan AS nama_pengirim,
                    s.total_berat_paket, s.biaya, tp.desk_tipe_pengiriman, jp.desk_jenis_pembayaran
             FROM resi r
             JOIN pelanggan p ON p.id_pelanggan = r.id_pelanggan
             JOIN servis s ON s.id_servis = r.id_servis
             JOIN tipe_pengiriman tp ON tp.id_tipe_pengiriman = s.id_tipe_pengiriman
             JOIN jenis_pembayaran jp ON jp.id_jenis_pembayaran = s.id_jenis_pembayaran
             WHERE r.nomor_resi = ? LIMIT 1'
        );
        $summary->bind_param('s', $resiInput);
        $summary->execute();
        $shipment = $summary->get_result()->fetch_assoc() ?: null;
        $summary->close();

        if ($shipment === null) {
            $notFound = 'Nomor resi ' . $resiInput . ' tidak ditemukan.';
        } else {
            $events = $conn2->prepare(
                'SELECT t.tanggal_jam_pengiriman, pp.posisi_terakhir, pp.id_posisi_terakhir_paket
                 FROM transit t
                 JOIN posisi_paket pp ON pp.id_posisi_terakhir_paket = t.id_posisi_terakhir_paket
                 WHERE t.nomor_resi = ?
                 GROUP BY t.tanggal_jam_pengiriman, pp.posisi_terakhir, pp.id_posisi_terakhir_paket
                 ORDER BY t.tanggal_jam_pengiriman DESC'
            );
            $events->bind_param('s', $resiInput);
            $events->execute();
            $timeline = $events->get_result()->fetch_all(MYSQLI_ASSOC);
            $events->close();

            $lastEvent = $timeline[0] ?? null;
            $currentPosition = $lastEvent['posisi_terakhir'] ?? '';
            $isDelivered = $currentPosition === 'SAMPAI';
            $currentStep = $isDelivered ? 3 : (count($timeline) > 1 ? 2 : 1);
        }
    }
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Lacak status dan riwayat perjalanan paket SampaiKilat dengan nomor resi.">
  <title>Cek Resi | SampaiKilat</title>
  <link rel="icon" href="../../assets/brand/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../../css/site-system.css">
  <link rel="stylesheet" href="../../css/cekresi/cekresi.css">
</head>
<body>
  <a class="skip-link" href="#utama">Lewati ke isi</a>

  <header class="site-header">
    <div class="container site-header__inner">
      <a class="brand" href="../../homepage.html">
        <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
        <span class="brand__text">Sampai<em>Kilat</em></span>
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Buka menu" data-nav-toggle>
        <span class="nav-toggle__bars" aria-hidden="true"></span><span class="nav-toggle__label">Menu</span>
      </button>
      <nav class="site-nav" id="site-nav" aria-label="Navigasi utama">
        <a href="../../homepage.html">Beranda</a>
        <a href="cekresi.php" aria-current="page">Cek Resi</a>
        <a href="../cektarif/cektarif.html">Cek Tarif</a>
        <a href="../ceklokasi/CekLokasi.html">Lokasi</a>
        <a href="../aboutus/AboutUs.html">Tentang Kami</a>
        <a href="../help/Help.html">Bantuan</a>
        <a class="btn btn--sm site-nav__cta" href="../login/loginpage.html">Masuk staf</a>
      </nav>
    </div>
  </header>

  <main id="utama" class="container page">
    <nav aria-label="Remah roti">
      <ol class="breadcrumb"><li><a href="../../homepage.html">Beranda</a></li><li aria-current="page">Cek Resi</li></ol>
    </nav>

    <div class="page-head">
      <div>
        <h1>Lacak pengiriman</h1>
        <p>Masukkan nomor resi untuk melihat posisi terakhir dan seluruh riwayat perjalanan paket.</p>
      </div>
    </div>

    <section class="card track-form-card" aria-labelledby="form-title">
      <h2 id="form-title" class="sr-only">Formulir pelacakan</h2>
      <form action="cekresi.php" method="post" data-validate novalidate>
        <div data-form-alert hidden></div>
        <div class="field">
          <label class="field__label" for="resi-number">Nomor resi</label>
          <div class="track-form-card__row">
            <input class="input input--mono input--lg" type="text" id="resi-number" name="nomor_resi"
                   value="<?= e($resiInput) ?>" data-resi-input required pattern="RS-[0-9]{7}" maxlength="10"
                   placeholder="RS-0000001" autocomplete="off" aria-describedby="resi-help" aria-invalid="false">
            <button class="btn btn--lg" type="submit">
              <span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Lacak pengiriman</span>
            </button>
          </div>
          <p class="field__help" id="resi-help">Nomor resi tertera pada bukti pengiriman. Format: RS dan 7 angka.</p>
          <p class="field__error" data-error-for="resi-number" role="alert"></p>
        </div>
      </form>

      <div class="track-form-card__recent" id="recent-resi" hidden>
        <p class="track-form-card__recent-title">Pencarian terakhir di perangkat ini</p>
        <div class="chip-row" id="recent-resi-list"></div>
      </div>

      <?php if ($error !== ''): ?>
        <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Nomor resi belum benar</strong><?= e($error) ?></span></p>
      <?php elseif ($notFound !== ''): ?>
        <div class="empty-state" role="status">
          <span class="empty-state__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path></svg>
          </span>
          <h3><?= e($notFound) ?></h3>
          <p>Periksa kembali penulisan nomor resi pada bukti pengiriman. Kalau nomornya sudah benar tetapi status tidak muncul, hubungi layanan pelanggan kami.</p>
          <div class="cluster">
            <button class="btn btn--secondary" type="button" data-clear-resi>Lacak nomor lain</button>
            <a class="btn btn--ghost" href="../help/Help.html">Hubungi bantuan</a>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($shipment !== null): ?>
      <section class="track-result stack" aria-labelledby="result-title">
        <article class="card">
          <header class="result-head">
            <div>
              <p class="eyebrow">Hasil pelacakan</p>
              <h2 id="result-title" class="result-head__resi mono" tabindex="-1" data-focus-target><?= e($shipment['nomor_resi']) ?></h2>
              <p class="muted">
                <?php if ($lastEvent !== null): ?>
                  Pembaruan terakhir <?= e(relative_waktu($lastEvent['tanggal_jam_pengiriman'])) ?> · <?= e(format_waktu($lastEvent['tanggal_jam_pengiriman'], $bulan)) ?>

                <?php else: ?>
                  Belum ada pembaruan perjalanan
                <?php endif; ?>
              </p>
            </div>
            <div class="result-head__actions">
              <span class="badge <?= $isDelivered ? 'badge--delivered' : 'badge--transit' ?>">
                <span class="badge__dot" aria-hidden="true"></span><?= $isDelivered ? 'Sampai tujuan' : 'Dalam perjalanan' ?>
              </span>
              <button class="copy-btn" type="button" data-copy="<?= e($shipment['nomor_resi']) ?>" data-copy-label="Salin resi">Salin resi</button>
              <button class="copy-btn" type="button" data-print>Cetak</button>
            </div>
          </header>

          <ol class="steps result-steps">
            <li class="steps__item <?= $currentStep > 1 ? 'steps__item--done' : '' ?>" <?= $currentStep === 1 ? 'aria-current="step"' : '' ?>>
              <span class="steps__num">Tahap 01</span>Paket diterima
            </li>
            <li class="steps__item <?= $currentStep > 2 ? 'steps__item--done' : '' ?>" <?= $currentStep === 2 ? 'aria-current="step"' : '' ?>>
              <span class="steps__num">Tahap 02</span>Dalam perjalanan
            </li>
            <li class="steps__item <?= $isDelivered ? 'steps__item--done' : '' ?>" <?= $currentStep === 3 ? 'aria-current="step"' : '' ?>>
              <span class="steps__num">Tahap 03</span>Sampai tujuan
            </li>
          </ol>
          <div class="progress progress--step-<?= $currentStep ?> <?= $isDelivered ? 'progress--done' : '' ?>" role="img" aria-label="Progres pengiriman: tahap <?= $currentStep ?> dari 3">
            <span class="progress__bar"></span>
          </div>

          <dl class="def-list result-summary">
            <div><dt>Posisi terakhir</dt><dd><?= $currentPosition !== '' ? e($currentPosition) : 'Belum ada pergerakan' ?></dd></div>
            <div><dt>Pengirim</dt><dd><?= e($shipment['nama_pengirim']) ?></dd></div>
            <div><dt>Penerima</dt><dd><?= e($shipment['nama_penerima']) ?></dd></div>
            <div><dt>Alamat tujuan</dt><dd>Jl. <?= e($shipment['alamat_jalan_penerima']) ?> <?= e($shipment['nomor_rumah_penerima']) ?>, <?= e($shipment['alamat_kecamatan_penerima']) ?>, <?= e($shipment['alamat_kota_penerima']) ?></dd></div>
            <div><dt>Layanan</dt><dd><?= e($shipment['desk_tipe_pengiriman']) ?> · <?= e($shipment['desk_jenis_pembayaran']) ?></dd></div>
            <div><dt>Berat tercatat</dt><dd><?= e(rtrim(rtrim(number_format((float) $shipment['total_berat_paket'], 2, ',', '.'), '0'), ',')) ?> kg</dd></div>
            <div><dt>Biaya pengiriman</dt><dd>Rp<?= e(number_format((float) $shipment['biaya'], 0, ',', '.')) ?></dd></div>
            <div><dt>Tanggal permintaan</dt><dd><?= e(format_waktu($shipment['tanggal_permintaan_pengiriman'], $bulan, false)) ?></dd></div>
          </dl>
        </article>

        <article class="card" aria-labelledby="timeline-title">
          <div class="card__head">
            <h2 id="timeline-title">Riwayat perjalanan</h2>
            <span class="muted"><?= count($timeline) ?> catatan, terbaru di atas</span>
          </div>
          <?php if ($timeline): ?>
            <ol class="timeline">
              <?php foreach ($timeline as $index => $event): ?>
                <li class="timeline__item<?= $index === 0 ? ' timeline__item--current' : '' ?>">
                  <span class="timeline__marker" aria-hidden="true"></span>
                  <div class="timeline__body">
                    <span class="timeline__label"><?= e($event['posisi_terakhir']) ?></span>
                    <span class="timeline__meta mono"><?= e(format_waktu($event['tanggal_jam_pengiriman'], $bulan)) ?> · <?= e(relative_waktu($event['tanggal_jam_pengiriman'])) ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php else: ?>
            <p class="alert">Pengiriman sudah tercatat, tetapi petugas belum menambahkan pergerakan paket. Status pertama biasanya muncul saat paket diproses di gudang asal.</p>
          <?php endif; ?>
        </article>

        <div class="track-result__foot">
          <p class="muted">Riwayat ini berasal dari pembaruan petugas gudang. Ada yang tidak sesuai? <a href="../help/Help.html">Ajukan komplain</a> dengan menyertakan nomor resi.</p>
          <button class="btn btn--secondary" type="button" data-clear-resi>Lacak resi lain</button>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container site-footer__grid">
      <div class="site-footer__brand">
        <a class="brand" href="../../homepage.html">
          <svg class="brand__mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 2 4 13.2h5.6L9.4 22 19 10.8h-5.6z"/></svg>
          <span class="brand__text">Sampai<em>Kilat</em></span>
        </a>
        <p>Paket jalan. Anda tenang. Layanan pengiriman domestik dengan status yang bisa dibaca.</p>
      </div>
      <nav class="site-footer__col" aria-labelledby="foot-layanan">
        <h2 id="foot-layanan">Layanan</h2>
        <a href="cekresi.php">Cek resi</a>
        <a href="../cektarif/cektarif.html">Cek tarif</a>
        <a href="../ceklokasi/CekLokasi.html">Cakupan lokasi</a>
      </nav>
      <nav class="site-footer__col" aria-labelledby="foot-dukungan">
        <h2 id="foot-dukungan">Dukungan</h2>
        <a href="../help/Help.html">Bantuan dan komplain</a>
        <a href="../rules/RulesPage.html">Larangan pengiriman</a>
        <a href="../aboutus/AboutUs.html">Tentang kami</a>
      </nav>
      <div class="site-footer__col">
        <h2 id="foot-kontak">Hubungi</h2>
        <a href="tel:+62212222222">(021) 222 2222</a>
        <a href="mailto:sampai@kilat.co.id">sampai@kilat.co.id</a>
        <span>Senin–Minggu, 24 jam</span>
      </div>
    </div>
    <div class="container site-footer__base">
      <p>© 2026 SampaiKilat. Proyek akademik Secure Programming, BINUS University.</p>
      <p>Jakarta · Tangerang · Depok · Bekasi · Bogor</p>
    </div>
  </footer>

  <script src="../../javascript/site.js" defer></script>
  <script src="../../javascript/pages/cekresi.js" defer></script>
</body>
</html>
