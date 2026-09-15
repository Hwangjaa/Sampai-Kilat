<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_login();

$username = (string) ($_SESSION['username'] ?? '');
$initials = '';
foreach (preg_split('/[^A-Za-z0-9]+/', $username) ?: [] as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
$initials = substr($initials, 0, 2);
if ($initials === '') {
    $initials = 'SK';
}

$flashError = (string) ($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_error']);
$formErrors = is_array($_SESSION['form_errors'] ?? null) ? $_SESSION['form_errors'] : [];
unset($_SESSION['form_errors']);
$formValues = is_array($_SESSION['form_values'] ?? null) ? $_SESSION['form_values'] : [];
unset($_SESSION['form_values']);

/**
 * Jumlah karakter (bukan byte) untuk mencocokkan panjang kolom VARCHAR MySQL.
 * mbstring tidak dijamin tersedia, jadi ada cadangan berbasis pcre.
 */
function jumlahKarakter(string $teks): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($teks, 'UTF-8');
    }
    $jumlah = preg_match_all('/./us', $teks);
    return $jumlah === false ? strlen($teks) : $jumlah;
}

$value = fn(string $field): string => e((string) ($formValues[$field] ?? ''));
$panjang = fn(string $field): string => e((string) jumlahKarakter((string) ($formValues[$field] ?? '')));
$errorText = fn(string $field): string => (string) ($formErrors[$field] ?? '');
$errorClass = fn(string $field): string => $errorText($field) !== '' ? 'field__error is-visible' : 'field__error';
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Formulir pendaftaran pelanggan baru SampaiKilat untuk staf. ID pelanggan dibuat otomatis oleh server dengan format PE-0000000.">
  <title>Daftar Pelanggan | SampaiKilat</title>
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
      <a href="../updatingDelivery/Update3.php">Update lokasi</a>
      <a href="../mendaftarPelanggan/pelanggan.php" aria-current="page">Daftar pelanggan</a>
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
<main id="utama" class="container app-main">
  <nav aria-label="Remah roti">
    <ol class="breadcrumb">
      <li><a href="../../homepage.html">Beranda</a></li>
      <li><a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a></li>
      <li aria-current="page">Daftar pelanggan</li>
    </ol>
  </nav>

  <div class="page-head">
    <div>
      <h1>Daftar Pelanggan</h1>
      <p>Lengkapi data pelanggan baru di bawah ini. Setelah tersimpan, pelanggan tersebut langsung bisa dipakai sebagai pengirim pada pembuatan pengiriman berikutnya.</p>
    </div>
  </div>

  <div class="cluster">
    <a class="btn btn--secondary" href="../homepageAS/HomePageAdminStaff.php">Kembali ke dashboard</a>
  </div>

  <?php if ($flashError !== ''): ?>
    <p class="alert alert--danger" role="alert"><strong class="alert__title">Pendaftaran belum tersimpan</strong><?= e($flashError) ?></p>
  <?php endif; ?>

  <div class="grid grid--aside">
    <div class="card">
      <div class="card__head">
        <h2>Formulir pelanggan baru</h2>
      </div>
      <form method="post" action="../../controller/login/submitPelanggan.php" data-validate novalidate>
        <div data-form-alert hidden></div>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid form-grid--2">
          <div class="field field--wide">
            <label class="field__label" for="nama_pelanggan">Nama pelanggan</label>
            <input class="input" id="nama_pelanggan" name="nama_pelanggan" type="text" maxlength="40" required
                   autocomplete="off" value="<?= $value('nama_pelanggan') ?>"
                   data-msg-required="Nama pelanggan wajib diisi."
                   data-msg-maxlength="Nama pelanggan maksimal 40 karakter." aria-describedby="nama_pelanggan_help">
            <p class="field__help" id="nama_pelanggan_help">Nama lengkap pelanggan. Maksimal 40 karakter, terhitung <span class="mono" data-count-for="nama_pelanggan" data-limit="40"><?= $panjang('nama_pelanggan') ?> / 40</span>.</p>
            <p class="<?= $errorClass('nama_pelanggan') ?>" data-error-for="nama_pelanggan" role="alert"><?= e($errorText('nama_pelanggan')) ?></p>
          </div>
          <div class="field">
            <label class="field__label" for="alamat_jalan_pelanggan">Alamat jalan</label>
            <input class="input" id="alamat_jalan_pelanggan" name="alamat_jalan_pelanggan" type="text" maxlength="20" required
                   autocomplete="off" value="<?= $value('alamat_jalan_pelanggan') ?>"
                   data-msg-required="Alamat jalan wajib diisi."
                   data-msg-maxlength="Alamat jalan maksimal 20 karakter." aria-describedby="alamat_jalan_pelanggan_help">
            <p class="field__help" id="alamat_jalan_pelanggan_help">Nama jalan dan nomor rumah, misalnya Jl. Melati 12. Maksimal 20 karakter, terhitung <span class="mono" data-count-for="alamat_jalan_pelanggan" data-limit="20"><?= $panjang('alamat_jalan_pelanggan') ?> / 20</span>.</p>
            <p class="<?= $errorClass('alamat_jalan_pelanggan') ?>" data-error-for="alamat_jalan_pelanggan" role="alert"><?= e($errorText('alamat_jalan_pelanggan')) ?></p>
          </div>
          <div class="field">
            <label class="field__label" for="alamat_kecamatan_pelanggan">Alamat kecamatan</label>
            <input class="input" id="alamat_kecamatan_pelanggan" name="alamat_kecamatan_pelanggan" type="text" maxlength="20" required
                   autocomplete="off" value="<?= $value('alamat_kecamatan_pelanggan') ?>"
                   data-msg-required="Alamat kecamatan wajib diisi."
                   data-msg-maxlength="Alamat kecamatan maksimal 20 karakter." aria-describedby="alamat_kecamatan_pelanggan_help">
            <p class="field__help" id="alamat_kecamatan_pelanggan_help">Kecamatan layanan, misalnya Grogol Petamburan. Maksimal 20 karakter, terhitung <span class="mono" data-count-for="alamat_kecamatan_pelanggan" data-limit="20"><?= $panjang('alamat_kecamatan_pelanggan') ?> / 20</span>.</p>
            <p class="<?= $errorClass('alamat_kecamatan_pelanggan') ?>" data-error-for="alamat_kecamatan_pelanggan" role="alert"><?= e($errorText('alamat_kecamatan_pelanggan')) ?></p>
          </div>
          <div class="field">
            <label class="field__label" for="alamat_kota_pelanggan">Alamat kota</label>
            <input class="input" id="alamat_kota_pelanggan" name="alamat_kota_pelanggan" type="text" maxlength="20" required
                   autocomplete="off" value="<?= $value('alamat_kota_pelanggan') ?>"
                   data-msg-required="Alamat kota wajib diisi."
                   data-msg-maxlength="Alamat kota maksimal 20 karakter." aria-describedby="alamat_kota_pelanggan_help">
            <p class="field__help" id="alamat_kota_pelanggan_help">Kota yang masuk cakupan layanan: Jakarta, Tangerang, Depok, Bekasi, atau Bogor. Maksimal 20 karakter, terhitung <span class="mono" data-count-for="alamat_kota_pelanggan" data-limit="20"><?= $panjang('alamat_kota_pelanggan') ?> / 20</span>.</p>
            <p class="<?= $errorClass('alamat_kota_pelanggan') ?>" data-error-for="alamat_kota_pelanggan" role="alert"><?= e($errorText('alamat_kota_pelanggan')) ?></p>
          </div>
          <div class="field">
            <label class="field__label" for="nomor_telpon">Nomor telepon</label>
            <input class="input" id="nomor_telpon" name="nomor_telpon" type="tel" inputmode="tel" maxlength="15" required
                   pattern="^[+0-9][0-9 +()-]{6,14}$" autocomplete="off" value="<?= $value('nomor_telpon') ?>"
                   data-msg-required="Nomor telepon wajib diisi."
                   data-msg-pattern="Nomor telepon belum valid. Gunakan angka, boleh dengan tanda plus atau spasi, contoh +6281234567890."
                   data-msg-maxlength="Nomor telepon maksimal 15 karakter."
                   data-msg-type="Nomor telepon belum valid. Gunakan angka, boleh dengan tanda plus atau spasi, contoh +6281234567890."
                   aria-describedby="nomor_telpon_help">
            <p class="field__help" id="nomor_telpon_help">7 sampai 15 karakter, hanya angka, tanda plus, spasi, tanda kurang, dan tanda kurung. Contoh: +6281234567890. Terhitung <span class="mono" data-count-for="nomor_telpon" data-limit="15"><?= $panjang('nomor_telpon') ?> / 15</span>.</p>
            <p class="<?= $errorClass('nomor_telpon') ?>" data-error-for="nomor_telpon" role="alert"><?= e($errorText('nomor_telpon')) ?></p>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn--primary" type="submit"><span class="btn__spinner" aria-hidden="true"></span><span class="btn__label">Daftarkan pelanggan</span></button>
          <a class="btn btn--secondary" href="../homepageAS/HomePageAdminStaff.php">Batal</a>
        </div>
      </form>
    </div>

    <aside class="card">
      <div class="card__head">
        <h2>Yang terjadi setelah disimpan</h2>
      </div>
      <div class="stack stack--tight">
        <p>ID pelanggan dibuat otomatis oleh server dengan format <span class="mono">PE-0000000</span>. Kolom ID tidak perlu diisi dari sini.</p>
        <p>Pelanggan yang tersimpan langsung bisa dipilih sebagai ID pengirim pada halaman <a class="line-link" href="../createDelivery/Create1.php">Buat pengiriman</a>.</p>
        <dl class="def-list">
          <div><dt>Nama pelanggan</dt><dd>Maksimal 40 karakter</dd></div>
          <div><dt>Alamat jalan</dt><dd>Maksimal 20 karakter</dd></div>
          <div><dt>Alamat kecamatan</dt><dd>Maksimal 20 karakter</dd></div>
          <div><dt>Alamat kota</dt><dd>Maksimal 20 karakter</dd></div>
          <div><dt>Nomor telepon</dt><dd>7 sampai 15 karakter</dd></div>
        </dl>
        <p class="muted">Batas tersebut mengikuti panjang kolom pada tabel pelanggan, jadi data yang lebih panjang akan ditolak oleh server.</p>
      </div>
    </aside>
  </div>
</main>

<script src="../../javascript/site.js" defer></script>
<script src="../../javascript/pages/pelanggan.js" defer></script>
</body>
</html>
