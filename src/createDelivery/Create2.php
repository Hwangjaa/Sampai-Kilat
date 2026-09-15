<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controller/login/bootstrap.php';
require_once __DIR__ . '/../../controller/login/koneksiDB2.php';

require_login();

if (empty($_SESSION['shipment_sender'])) {
    $_SESSION['flash_error'] = 'Isi data pengirim lebih dulu sebelum mengisi data penerima.';
    header('Location: Create1.php', true, 303);
    exit;
}

$errors = is_array($_SESSION['form_errors'] ?? null) ? $_SESSION['form_errors'] : [];
$old = is_array($_SESSION['form_old'] ?? null) ? $_SESSION['form_old'] : [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$flashError = (string) ($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_error']);

$sender = is_array($_SESSION['shipment_sender']) ? $_SESSION['shipment_sender'] : [];
$receiver = is_array($_SESSION['shipment_receiver'] ?? null) ? $_SESSION['shipment_receiver'] : [];

$value = static function (string $field) use ($old, $receiver): string {
    $raw = $old[$field] ?? $receiver[$field] ?? '';
    return is_string($raw) ? $raw : '';
};
$fieldError = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? (string) $errors[$field] : '';
};

$username = (string) ($_SESSION['username'] ?? 'staf');
$nameParts = preg_split('/[^a-zA-Z0-9]+/', $username) ?: [];
$initials = strtoupper(substr((string) ($nameParts[0] ?? ''), 0, 1) . substr((string) ($nameParts[1] ?? ''), 0, 1));
if ($initials === '') {
    $initials = 'SK';
}

/* Real service catalogue straight from the servis table, nothing hardcoded. */
$services = [];
$serviceResult = $conn2->query(
    'SELECT s.id_servis, s.total_berat_paket, s.biaya, tp.desk_tipe_pengiriman, pkt.desk_tipe_paket,
            jp.desk_jenis_paket, jb.desk_jenis_pembayaran
     FROM servis s
     JOIN tipe_pengiriman tp ON tp.id_tipe_pengiriman = s.id_tipe_pengiriman
     JOIN tipe_paket pkt ON pkt.id_tipe_paket = s.id_tipe_paket
     JOIN jenis_paket jp ON jp.id_jenis_paket = s.id_jenis_paket
     JOIN jenis_pembayaran jb ON jb.id_jenis_pembayaran = s.id_jenis_pembayaran
     ORDER BY s.id_servis'
);
if ($serviceResult !== false) {
    while ($row = $serviceResult->fetch_assoc()) {
        $services[] = $row;
    }
}
if ($services === []) {
    error_log('Create2: daftar servis tidak dapat dibaca.');
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

$serviceIds = array_column($services, 'id_servis');
$selectedService = $value('id_servis');
if ($selectedService === '' || !in_array($selectedService, $serviceIds, true)) {
    $selectedService = (string) ($serviceIds[0] ?? '');
}
$selectedRow = null;
foreach ($services as $service) {
    if ((string) $service['id_servis'] === $selectedService) {
        $selectedRow = $service;
        break;
    }
}

$tanggalValue = $value('tanggal_pengiriman');
if ($tanggalValue === '') {
    $tanggalValue = date('Y-m-d');
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Langkah 2 dari 3 pembuatan pengiriman: data penerima dan layanan.">
  <title>Buat pengiriman: data penerima | SampaiKilat</title>
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
      <li aria-current="page">Data penerima</li>
    </ol>
  </nav>

  <div class="stack">
    <div class="page-head">
      <div>
        <p class="eyebrow">Langkah 2 dari 3</p>
        <h1>Buat pengiriman: data penerima</h1>
        <p>Isi tujuan pengiriman dan pilih layanan. Kolom dengan batas karakter menampilkan sisa karakter saat Anda mengetik.</p>
      </div>
    </div>

    <ol class="steps">
      <li class="steps__item steps__item--done"><span class="steps__num">Langkah 01</span>Data pengirim</li>
      <li class="steps__item" aria-current="step"><span class="steps__num">Langkah 02</span>Data penerima</li>
      <li class="steps__item"><span class="steps__num">Langkah 03</span>Konfirmasi</li>
    </ol>

    <?php if ($flashError !== ''): ?>
      <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Data penerima belum bisa dilanjutkan</strong><?= e($flashError) ?></span></p>
    <?php endif; ?>

    <section class="card" aria-labelledby="pengirim-title">
      <div class="card__head">
        <h2 id="pengirim-title">Pengirim tersimpan</h2>
        <a class="btn btn--ghost btn--sm" href="Create1.php">Ubah data pengirim</a>
      </div>
      <dl class="def-list">
        <div><dt>ID pengirim</dt><dd class="mono"><?= e((string) ($sender['id_pengirim'] ?? '')) ?></dd></div>
        <div><dt>Nama</dt><dd><?= e((string) ($sender['nama_pengirim'] ?? '')) ?></dd></div>
        <div><dt>Telepon</dt><dd class="mono"><?= e((string) ($sender['nomor_telepon'] ?? '')) ?></dd></div>
        <div><dt>Alamat penjemputan</dt><dd><?= e((string) ($sender['alamat_pengirim'] ?? '')) ?></dd></div>
      </dl>
    </section>

    <section class="card" aria-labelledby="form-penerima-title">
      <div class="card__head">
        <h2 id="form-penerima-title">Data penerima dan layanan</h2>
        <span class="muted">Wajib diisi semua</span>
      </div>

      <form class="step-form" method="post" action="../../controller/login/createcontroller2.php" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div data-form-alert hidden></div>

        <div class="form-grid form-grid--2">
          <div class="field<?= $fieldError('nama_penerima') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="nama_penerima">Nama penerima</label>
            <input class="input" type="text" id="nama_penerima" name="nama_penerima" value="<?= e($value('nama_penerima')) ?>"
                   maxlength="30" required autocomplete="off" data-count="nama_penerima-count"
                   aria-describedby="nama_penerima-help"<?= $fieldError('nama_penerima') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="nama_penerima-help">Nama orang yang menerima paket.</p>
            <p class="field__help" id="nama_penerima-count">Batas 30 karakter. Sisa 30.</p>
            <p class="field__error" data-error-for="nama_penerima" role="alert"><?= e($fieldError('nama_penerima')) ?></p>
          </div>

          <div class="field<?= $fieldError('no_penerima') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="no_penerima">Nomor telepon penerima</label>
            <input class="input" type="tel" id="no_penerima" name="no_penerima" value="<?= e($value('no_penerima')) ?>"
                   inputmode="tel" maxlength="13" required autocomplete="off" data-count="no_penerima-count" placeholder="081234567890"
                   aria-describedby="no_penerima-help"<?= $fieldError('no_penerima') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="no_penerima-help">Kontak penerima untuk pemberitahuan pengantaran.</p>
            <p class="field__help" id="no_penerima-count">Batas 13 karakter. Sisa 13.</p>
            <p class="field__error" data-error-for="no_penerima" role="alert"><?= e($fieldError('no_penerima')) ?></p>
          </div>

          <div class="field<?= $fieldError('alamat_penerima') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="alamat_penerima">Alamat jalan</label>
            <input class="input" type="text" id="alamat_penerima" name="alamat_penerima" value="<?= e($value('alamat_penerima')) ?>"
                   maxlength="20" required autocomplete="off" data-count="alamat_penerima-count"
                   aria-describedby="alamat_penerima-help"<?= $fieldError('alamat_penerima') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="alamat_penerima-help">Nama jalan tanpa nomor rumah, maksimal 20 karakter.</p>
            <p class="field__help" id="alamat_penerima-count">Batas 20 karakter. Sisa 20.</p>
            <p class="field__error" data-error-for="alamat_penerima" role="alert"><?= e($fieldError('alamat_penerima')) ?></p>
          </div>

          <div class="field<?= $fieldError('no_rumah') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="no_rumah">Nomor rumah</label>
            <input class="input" type="text" id="no_rumah" name="no_rumah" value="<?= e($value('no_rumah')) ?>"
                   maxlength="20" required autocomplete="off" data-count="no_rumah-count" placeholder="No. 12"
                   aria-describedby="no_rumah-help"<?= $fieldError('no_rumah') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="no_rumah-help">Nomor rumah, blok, atau patokan bangunan.</p>
            <p class="field__help" id="no_rumah-count">Batas 20 karakter. Sisa 20.</p>
            <p class="field__error" data-error-for="no_rumah" role="alert"><?= e($fieldError('no_rumah')) ?></p>
          </div>

          <div class="field<?= $fieldError('alamat_kota') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="alamat_kota">Kota tujuan</label>
            <input class="input" type="text" id="alamat_kota" name="alamat_kota" value="<?= e($value('alamat_kota')) ?>"
                   maxlength="20" required autocomplete="off" data-count="alamat_kota-count"
                   aria-describedby="alamat_kota-help"<?= $fieldError('alamat_kota') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="alamat_kota-help">Kota atau kabupaten tujuan, maksimal 20 karakter.</p>
            <p class="field__help" id="alamat_kota-count">Batas 20 karakter. Sisa 20.</p>
            <p class="field__error" data-error-for="alamat_kota" role="alert"><?= e($fieldError('alamat_kota')) ?></p>
          </div>

          <div class="field<?= $fieldError('alamat_kecamatan') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="alamat_kecamatan">Kecamatan</label>
            <input class="input" type="text" id="alamat_kecamatan" name="alamat_kecamatan" value="<?= e($value('alamat_kecamatan')) ?>"
                   maxlength="20" required autocomplete="off" data-count="alamat_kecamatan-count"
                   aria-describedby="alamat_kecamatan-help"<?= $fieldError('alamat_kecamatan') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="alamat_kecamatan-help">Kecamatan tujuan, maksimal 20 karakter.</p>
            <p class="field__help" id="alamat_kecamatan-count">Batas 20 karakter. Sisa 20.</p>
            <p class="field__error" data-error-for="alamat_kecamatan" role="alert"><?= e($fieldError('alamat_kecamatan')) ?></p>
          </div>

          <div class="field<?= $fieldError('tanggal_pengiriman') !== '' ? ' field--invalid' : '' ?>">
            <label class="field__label" for="tanggal_pengiriman">Tanggal permintaan pengiriman</label>
            <input class="input" type="date" id="tanggal_pengiriman" name="tanggal_pengiriman" value="<?= e($tanggalValue) ?>"
                   required aria-describedby="tanggal_pengiriman-help"<?= $fieldError('tanggal_pengiriman') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
            <p class="field__help" id="tanggal_pengiriman-help">Tanggal yang diminta pengirim. Terisi tanggal hari ini secara otomatis.</p>
            <p class="field__error" data-error-for="tanggal_pengiriman" role="alert"><?= e($fieldError('tanggal_pengiriman')) ?></p>
          </div>

          <?php if ($services !== []): ?>
            <div class="field<?= $fieldError('id_servis') !== '' ? ' field--invalid' : '' ?>">
              <label class="field__label" for="id_servis">Layanan pengiriman</label>
              <select class="select" id="id_servis" name="id_servis" required data-servis-select
                      aria-describedby="id_servis-help id_servis-ringkas"<?= $fieldError('id_servis') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
                <?php foreach ($services as $service): ?>
                  <option value="<?= e((string) $service['id_servis']) ?>"
                          data-tipe="<?= e((string) $service['desk_tipe_pengiriman']) ?>"
                          data-paket="<?= e((string) $service['desk_tipe_paket']) ?>"
                          data-jenis="<?= e((string) $service['desk_jenis_paket']) ?>"
                          data-bayar="<?= e((string) $service['desk_jenis_pembayaran']) ?>"
                          data-berat="<?= e($formatBerat($service['total_berat_paket'])) ?> kg"
                          data-biaya="<?= e($formatRupiah($service['biaya'])) ?>"
                          <?= (string) $service['id_servis'] === $selectedService ? 'selected' : '' ?>><?= e((string) $service['id_servis'] . ' - ' . $service['desk_tipe_pengiriman'] . ' - ' . $formatBerat($service['total_berat_paket']) . ' kg - ' . $formatRupiah($service['biaya'])) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="field__help" id="id_servis-help">Pilih layanan sesuai berat dan tarif yang disepakati dengan pengirim. Daftar ini diambil langsung dari tabel servis.</p>
              <p class="field__help" id="id_servis-ringkas" data-servis-summary role="status"><?php if ($selectedRow !== null): ?><?= e((string) $selectedRow['id_servis'] . ': ' . $selectedRow['desk_tipe_pengiriman'] . ', paket ' . $selectedRow['desk_tipe_paket'] . ', ' . $formatBerat($selectedRow['total_berat_paket']) . ' kg, biaya ' . $formatRupiah($selectedRow['biaya']) . ', pembayaran ' . $selectedRow['desk_jenis_pembayaran'] . '.') ?><?php endif; ?></p>
              <p class="field__error" data-error-for="id_servis" role="alert"><?= e($fieldError('id_servis')) ?></p>
            </div>
          <?php else: ?>
            <div class="field<?= $fieldError('id_servis') !== '' ? ' field--invalid' : '' ?>">
              <label class="field__label" for="id_servis">ID servis</label>
              <input class="input input--mono" type="text" id="id_servis" name="id_servis" value="<?= e($value('id_servis')) ?>"
                     pattern="SR-[0-9]{7}" maxlength="10" required autocomplete="off" placeholder="SR-0000001"
                     aria-describedby="id_servis-help"<?= $fieldError('id_servis') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
              <p class="field__help" id="id_servis-help">Daftar layanan sedang tidak dapat dimuat. Masukkan ID servis terdaftar dengan format SR diikuti 7 angka.</p>
              <p class="field__error" data-error-for="id_servis" role="alert"><?= e($fieldError('id_servis')) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($services !== []): ?>
          <div class="accordion">
            <details>
              <summary>Rujukan <?= count($services) ?> layanan terdaftar</summary>
              <div class="accordion__body">
                <div class="table-wrap">
                  <table class="table table--stack">
                    <thead>
                      <tr>
                        <th scope="col">ID servis</th>
                        <th scope="col">Layanan</th>
                        <th scope="col">Berat tercatat</th>
                        <th scope="col">Tipe paket</th>
                        <th scope="col">Jenis paket</th>
                        <th scope="col">Biaya</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($services as $service): ?>
                        <tr>
                          <td data-label="ID servis" class="cell-resi"><?= e((string) $service['id_servis']) ?></td>
                          <td data-label="Layanan"><?= e((string) $service['desk_tipe_pengiriman']) ?> / <?= e((string) $service['desk_jenis_pembayaran']) ?></td>
                          <td data-label="Berat tercatat"><?= e($formatBerat($service['total_berat_paket'])) ?> kg</td>
                          <td data-label="Tipe paket"><?= e((string) $service['desk_tipe_paket']) ?></td>
                          <td data-label="Jenis paket"><?= e((string) $service['desk_jenis_paket']) ?></td>
                          <td data-label="Biaya"><?= e($formatRupiah($service['biaya'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </details>
          </div>
        <?php endif; ?>

        <?php if ($receiver !== []): ?>
          <p class="form-note"><span><strong>Data penerima tersimpan:</strong> perubahan pada langkah ini akan mengganti data penerima yang tersimpan sebelumnya (<?= e((string) ($receiver['nama_penerima'] ?? '')) ?>).</span></p>
        <?php endif; ?>

        <div class="form-actions">
          <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Lanjut ke konfirmasi</span></button>
          <a class="btn btn--secondary" href="Create1.php">Kembali</a>
        </div>
      </form>
    </section>

    <p class="muted">Tanggal permintaan terisi otomatis dengan tanggal hari ini, ubah bila pengirim meminta tanggal lain.</p>
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
