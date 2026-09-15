<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controller/login/bootstrap.php';
require_once __DIR__ . '/../../controller/login/koneksiDB2.php';

require_login();

$errors = is_array($_SESSION['form_errors'] ?? null) ? $_SESSION['form_errors'] : [];
$old = is_array($_SESSION['form_old'] ?? null) ? $_SESSION['form_old'] : [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$flashError = (string) ($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_error']);

$sender = is_array($_SESSION['shipment_sender'] ?? null) ? $_SESSION['shipment_sender'] : [];

$value = static function (string $field) use ($old, $sender): string {
    $raw = $old[$field] ?? $sender[$field] ?? '';
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

/* Registered customers only: served from the real pelanggan table, nothing hardcoded. */
$customers = [];
$customerStmt = $conn2->prepare('SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY id_pelanggan');
if ($customerStmt !== false) {
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    if ($customerResult !== false) {
        while ($row = $customerResult->fetch_assoc()) {
            $customers[] = $row;
        }
    }
    $customerStmt->close();
}
if ($customers === []) {
    error_log('Create1: daftar pelanggan terdaftar tidak dapat dibaca.');
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Langkah 1 dari 3 pembuatan pengiriman: data pengirim.">
  <title>Buat pengiriman: data pengirim | SampaiKilat</title>
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
      <li aria-current="page">Buat pengiriman</li>
    </ol>
  </nav>

  <div class="stack">
    <div class="page-head">
      <div>
        <p class="eyebrow">Langkah 1 dari 3</p>
        <h1>Buat pengiriman: data pengirim</h1>
        <p>Pengirim menentukan pemilik resi. Setelah data ini tersimpan, Anda mengisi data penerima lalu mengonfirmasi pengiriman.</p>
      </div>
    </div>

    <ol class="steps">
      <li class="steps__item" aria-current="step"><span class="steps__num">Langkah 01</span>Data pengirim</li>
      <li class="steps__item"><span class="steps__num">Langkah 02</span>Data penerima</li>
      <li class="steps__item"><span class="steps__num">Langkah 03</span>Konfirmasi</li>
    </ol>

    <?php if ($flashError !== ''): ?>
      <p class="alert alert--danger" role="alert" tabindex="-1" data-focus-target><span><strong class="alert__title">Data pengirim belum bisa dilanjutkan</strong><?= e($flashError) ?></span></p>
    <?php endif; ?>

    <section class="card" aria-labelledby="form-pengirim-title">
      <div class="card__head">
        <h2 id="form-pengirim-title">Data pengirim</h2>
        <span class="muted">Wajib diisi semua</span>
      </div>

      <form class="step-form" method="post" action="../../controller/login/createcontroller.php" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div data-form-alert hidden></div>

        <div class="field<?= $fieldError('id_pengirim') !== '' ? ' field--invalid' : '' ?>">
          <label class="field__label" for="id_pengirim">ID pengirim</label>
          <input class="input input--mono" type="text" id="id_pengirim" name="id_pengirim" value="<?= e($value('id_pengirim')) ?>"
                 list="pelanggan-terdaftar" data-pelanggan-input pattern="PE-[0-9]{7}" maxlength="10" required
                 autocomplete="off" placeholder="PE-0000001"
                 aria-describedby="id_pengirim-help id_pengirim-cocok"<?= $fieldError('id_pengirim') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
          <p class="field__help" id="id_pengirim-help">Harus pelanggan yang sudah terdaftar di data pelanggan. Format PE diikuti 7 angka, contoh PE-0000001. Belum terdaftar? <a href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan baru</a>.</p>
          <p class="field__help" id="id_pengirim-cocok" data-pelanggan-match hidden></p>
          <p class="field__error" data-error-for="id_pengirim" role="alert"><?= e($fieldError('id_pengirim')) ?></p>
        </div>

        <?php if ($customers !== []): ?>
          <datalist id="pelanggan-terdaftar">
            <?php foreach ($customers as $customer): ?>
              <option value="<?= e((string) $customer['id_pelanggan']) ?>"><?= e((string) $customer['nama_pelanggan']) ?></option>
            <?php endforeach; ?>
          </datalist>
          <p class="form-note"><span><strong>Pelanggan terdaftar:</strong> <?= count($customers) ?> ID pelanggan tersedia sebagai saran saat mengetik di kolom ID pengirim. Belum terdaftar? <a href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan baru</a>.</span></p>
        <?php else: ?>
          <p class="alert alert--warn" role="status"><span><strong class="alert__title">Daftar pelanggan tidak dapat dimuat</strong>Saran ID pelanggan sedang tidak tersedia. Masukkan ID pengirim yang terdaftar secara manual, atau <a href="../mendaftarPelanggan/pelanggan.php">daftarkan pelanggan baru</a> lebih dulu.</span></p>
        <?php endif; ?>

        <div class="field<?= $fieldError('nama_pengirim') !== '' ? ' field--invalid' : '' ?>">
          <label class="field__label" for="nama_pengirim">Nama pengirim</label>
          <input class="input" type="text" id="nama_pengirim" name="nama_pengirim" value="<?= e($value('nama_pengirim')) ?>"
                 maxlength="40" required autocomplete="name"
                 aria-describedby="nama_pengirim-help"<?= $fieldError('nama_pengirim') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
          <p class="field__help" id="nama_pengirim-help">Nama orang yang menyerahkan paket, maksimal 40 karakter.</p>
          <p class="field__error" data-error-for="nama_pengirim" role="alert"><?= e($fieldError('nama_pengirim')) ?></p>
        </div>

        <div class="field<?= $fieldError('nomor_telepon') !== '' ? ' field--invalid' : '' ?>">
          <label class="field__label" for="nomor_telepon">Nomor telepon pengirim</label>
          <input class="input" type="tel" id="nomor_telepon" name="nomor_telepon" value="<?= e($value('nomor_telepon')) ?>"
                 inputmode="tel" maxlength="15" required autocomplete="tel" placeholder="081234567890"
                 aria-describedby="nomor_telepon-help"<?= $fieldError('nomor_telepon') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
          <p class="field__help" id="nomor_telepon-help">Nomor yang bisa dihubungi kurir, 7 sampai 15 karakter angka, contoh 081234567890.</p>
          <p class="field__error" data-error-for="nomor_telepon" role="alert"><?= e($fieldError('nomor_telepon')) ?></p>
        </div>

        <div class="field<?= $fieldError('alamat_pengirim') !== '' ? ' field--invalid' : '' ?>">
          <label class="field__label" for="alamat_pengirim">Alamat penjemputan</label>
          <input class="input" type="text" id="alamat_pengirim" name="alamat_pengirim" value="<?= e($value('alamat_pengirim')) ?>"
                 maxlength="100" required autocomplete="street-address"
                 aria-describedby="alamat_pengirim-help"<?= $fieldError('alamat_pengirim') !== '' ? ' aria-invalid="true"' : ' aria-invalid="false"' ?>>
          <p class="field__help" id="alamat_pengirim-help">Alamat tempat paket dijemput, maksimal 100 karakter.</p>
          <p class="field__error" data-error-for="alamat_pengirim" role="alert"><?= e($fieldError('alamat_pengirim')) ?></p>
        </div>

        <div class="form-actions">
          <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Lanjut ke data penerima</span></button>
          <a class="btn btn--secondary" href="../homepageAS/HomePageAdminStaff.php">Batal</a>
        </div>
      </form>
    </section>

    <p class="muted">Belum punya ID pengirim? <a href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan baru</a> lebih dulu, lalu kembali ke halaman ini.</p>
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
