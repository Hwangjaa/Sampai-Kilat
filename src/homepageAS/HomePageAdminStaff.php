<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_once '../../controller/login/koneksiDB2.php';
require_login();

$roleId = (string) ($_SESSION['role'] ?? '');
$username = (string) ($_SESSION['username'] ?? '');
$flash = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);

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

/* 1. Seluruh riwayat transit dalam satu query, lalu dikelompokkan per nomor resi di PHP. */
$history = [];
$sqlHistory = 'SELECT t.nomor_resi, t.tanggal_jam_pengiriman, p.posisi_terakhir, s.nama_supir, i.desk_isi_paket FROM transit t JOIN isi_paket i ON i.id_isi_paket = t.id_isi_paket JOIN supir s ON s.plat_nomor_kendaraan = t.plat_nomor_kendaraan JOIN posisi_paket p ON p.id_posisi_terakhir_paket = t.id_posisi_terakhir_paket ORDER BY t.tanggal_jam_pengiriman DESC, t.nomor_resi DESC';
$result = $conn2->query($sqlHistory);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[(string) $row['nomor_resi']][] = $row;
    }
    $result->free();
} else {
    error_log('Dashboard riwayat transit query failed');
}

/* 2. Daftar resi beserta pemiliknya, agar resi tanpa transit tetap tampil (Menunggu diproses). */
$pengiriman = [];
$result = $conn2->query('SELECT r.nomor_resi, pl.nama_pelanggan FROM resi r JOIN pelanggan pl ON pl.id_pelanggan = r.id_pelanggan ORDER BY r.nomor_resi DESC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pengiriman[] = $row;
    }
    $result->free();
} else {
    error_log('Dashboard daftar resi query failed');
}

/* 3. Jumlah pelanggan terdaftar (nilai nyata dari tabel pelanggan). */
$totalPelanggan = 0;
$result = $conn2->query('SELECT COUNT(*) AS jumlah FROM pelanggan');
if ($result) {
    $countRow = $result->fetch_assoc();
    $totalPelanggan = (int) ($countRow['jumlah'] ?? 0);
    $result->free();
} else {
    error_log('Dashboard hitung pelanggan query failed');
}

$items = [];
$jumlahDalamPerjalanan = 0;
$jumlahSampai = 0;
$jumlahMenunggu = 0;

foreach ($pengiriman as $row) {
    $resi = (string) $row['nomor_resi'];
    $pelanggan = (string) $row['nama_pelanggan'];
    $riwayat = $history[$resi] ?? [];
    $terakhir = $riwayat[0] ?? null;
    $posisi = $terakhir ? (string) $terakhir['posisi_terakhir'] : '';
    $waktu = $terakhir ? (string) $terakhir['tanggal_jam_pengiriman'] : '';

    if ($terakhir === null) {
        $status = 'menunggu';
        $jumlahMenunggu++;
    } elseif ($posisi === 'SAMPAI') {
        $status = 'sampai';
        $jumlahSampai++;
    } else {
        $status = 'transit';
        $jumlahDalamPerjalanan++;
    }

    /* Isi paket dan kurir pada waktu terbaru: gabungkan nilai unik bila satu resi punya beberapa baris transit. */
    $paketTerbaru = [];
    $kurirTerbaru = [];
    foreach ($riwayat as $baris) {
        if ((string) $baris['tanggal_jam_pengiriman'] !== $waktu) {
            break;
        }
        $paketTerbaru[] = (string) $baris['desk_isi_paket'];
        $kurirTerbaru[] = (string) $baris['nama_supir'];
    }

    $semuaPaket = [];
    $semuaKurir = [];
    foreach ($riwayat as $baris) {
        $semuaPaket[] = (string) $baris['desk_isi_paket'];
        $semuaKurir[] = (string) $baris['nama_supir'];
    }

    $kataKunci = array_unique(array_merge([$resi, $pelanggan], $semuaPaket, $semuaKurir));

    $items[] = [
        'resi' => $resi,
        'pelanggan' => $pelanggan,
        'isi' => $paketTerbaru ? implode(', ', array_unique($paketTerbaru)) : 'Belum ada transit',
        'kurir' => $kurirTerbaru ? implode(', ', array_unique($kurirTerbaru)) : 'Belum ada transit',
        'waktu' => $waktu !== '' ? $waktu : 'Belum ada transit',
        'posisi' => $posisi,
        'status' => $status,
        'riwayat' => $riwayat,
        'kata_kunci' => implode(' ', $kataKunci),
    ];
}

$totalPengiriman = count($items);
$adaPengiriman = $totalPengiriman > 0;

$statusLabels = [
    'menunggu' => ['label' => 'Menunggu diproses', 'class' => 'badge--created'],
    'transit' => ['label' => 'Dalam perjalanan', 'class' => 'badge--transit'],
    'sampai' => ['label' => 'Sampai', 'class' => 'badge--delivered'],
];
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Dashboard staf SampaiKilat: ringkasan pengiriman, status terakhir, dan riwayat transit setiap nomor resi.">
  <title>Dashboard Pengiriman | SampaiKilat</title>
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
      <a href="../homepageAS/HomePageAdminStaff.php" aria-current="page">Dashboard</a>
      <a href="../createDelivery/Create1.php">Buat pengiriman</a>
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
<main id="utama" class="container app-main">
  <nav aria-label="Remah roti">
    <ol class="breadcrumb"><li><a href="../../homepage.html">Beranda</a></li><li aria-current="page">Dashboard pengiriman</li></ol>
  </nav>

  <div class="page-head">
    <div>
      <h1>Dashboard Pengiriman</h1>
      <p>Pantau semua nomor resi beserta posisi terakhirnya. Buka tombol Riwayat pada satu baris untuk melihat perjalanan paket tersebut.</p>
    </div>
  </div>

  <div class="cluster">
    <a class="btn btn--primary" href="../createDelivery/Create1.php">Buat pengiriman</a>
    <a class="btn btn--secondary" href="../mendaftarPelanggan/pelanggan.php">Daftar pelanggan</a>
    <?php if ($roleId === 'RL-001'): ?>
      <button class="btn btn--danger" type="button" data-open-dialog="delete-dialog">Hapus transit</button>
    <?php endif; ?>
    <a class="line-link" href="../../homepage.html">Situs publik</a>
  </div>

  <?php if ($flash !== ''): ?>
    <p class="alert alert--success" role="status"><?= e($flash) ?></p>
  <?php endif; ?>

  <section class="app-section" aria-labelledby="ringkasan-heading">
    <h2 class="eyebrow" id="ringkasan-heading">Ringkasan pengiriman</h2>
    <div class="stat-grid">
      <div class="stat">
        <span class="stat__label">Total pengiriman</span>
        <span class="stat__value"><?= e((string) $totalPengiriman) ?></span>
        <span class="stat__meta">Nomor resi terdaftar pada basis data operasional</span>
      </div>
      <div class="stat">
        <span class="stat__label">Dalam perjalanan</span>
        <span class="stat__value"><?= e((string) $jumlahDalamPerjalanan) ?></span>
        <span class="stat__meta">Posisi terakhir belum SAMPAI</span>
      </div>
      <div class="stat">
        <span class="stat__label">Sampai</span>
        <span class="stat__value"><?= e((string) $jumlahSampai) ?></span>
        <span class="stat__meta">Posisi terakhir sudah SAMPAI</span>
      </div>
      <div class="stat">
        <span class="stat__label">Pelanggan terdaftar</span>
        <span class="stat__value"><?= e((string) $totalPelanggan) ?></span>
        <span class="stat__meta">Jumlah baris pada tabel pelanggan</span>
      </div>
    </div>
  </section>

  <section class="app-section" aria-labelledby="daftar-heading">
    <h2 id="daftar-heading">Daftar pengiriman</h2>
    <p class="muted">Pencarian dan filter bekerja langsung di halaman ini. Setiap baris menampilkan posisi terakhir paket.</p>

    <div class="dash-toolbar" data-dash-toolbar<?= $adaPengiriman ? '' : ' hidden' ?>>
      <div class="field dash-search">
        <label class="field__label" for="dash-search">Cari pengiriman</label>
        <span class="dash-search__icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" d="M10.5 3a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm5.6 13.1L21 21"/></svg>
        </span>
        <input class="input" type="search" id="dash-search" name="q" autocomplete="off" placeholder="Nomor resi, nama pelanggan, isi paket, atau kurir" data-filter-search>
      </div>
      <div class="segmented" role="group" aria-label="Filter status pengiriman">
        <button type="button" aria-pressed="true" data-status-filter="all">Semua</button>
        <button type="button" aria-pressed="false" data-status-filter="menunggu">Menunggu diproses</button>
        <button type="button" aria-pressed="false" data-status-filter="transit">Dalam perjalanan</button>
        <button type="button" aria-pressed="false" data-status-filter="sampai">Sampai</button>
      </div>
      <p class="table-toolbar__count" data-filter-count aria-live="polite">Menampilkan <?= e((string) $totalPengiriman) ?> dari <?= e((string) $totalPengiriman) ?> pengiriman</p>
    </div>

    <div class="table-wrap" data-dash-table<?= $adaPengiriman ? '' : ' hidden' ?>>
      <table class="table table--stack">
        <caption class="sr-only">Daftar pengiriman beserta isi paket, kurir, waktu terakhir, dan status terbarunya</caption>
        <thead>
          <tr>
            <th scope="col">Resi</th>
            <th scope="col">Isi paket</th>
            <th scope="col">Kurir</th>
            <th scope="col">Waktu terakhir</th>
            <th scope="col">Status</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody data-dash-rows>
          <?php foreach ($items as $item): ?>
            <tr data-dash-row data-status="<?= e($item['status']) ?>" data-search="<?= e($item['kata_kunci']) ?>">
              <td data-label="Resi" class="cell-resi"><?= e($item['resi']) ?></td>
              <td data-label="Isi paket"><?= e($item['isi']) ?></td>
              <td data-label="Kurir"><?= e($item['kurir']) ?></td>
              <td data-label="Waktu terakhir" class="cell-time"><?= e($item['waktu']) ?></td>
              <td data-label="Status">
                <span class="badge <?= e($statusLabels[$item['status']]['class']) ?>">
                  <span class="badge__dot" aria-hidden="true"></span><?= e($statusLabels[$item['status']]['label']) ?>
                </span>
              </td>
              <td data-label="Aksi" class="cell-actions">
                <button class="row-toggle" type="button" aria-expanded="false" aria-controls="riwayat-<?= e($item['resi']) ?>" data-row-toggle>Riwayat<span class="sr-only"> <?= e($item['resi']) ?></span></button>
              </td>
            </tr>
            <tr class="detail-row" id="riwayat-<?= e($item['resi']) ?>" data-detail-row hidden>
              <td colspan="6" data-empty>
                <div class="detail-row__inner">
                  <p><strong>Riwayat transit <?= e($item['resi']) ?></strong> untuk <?= e($item['pelanggan']) ?>, terbaru lebih dulu.</p>
                  <?php if (!$item['riwayat']): ?>
                    <p class="muted">Nomor resi ini belum punya catatan transit. Statusnya masih menunggu diproses.</p>
                  <?php else: ?>
                    <ol class="timeline">
                      <?php foreach ($item['riwayat'] as $indexRiwayat => $baris): ?>
                        <li class="timeline__item<?= $indexRiwayat === 0 ? ' timeline__item--current' : '' ?>">
                          <span class="timeline__marker" aria-hidden="true"></span>
                          <div class="timeline__body">
                            <span class="timeline__label"><?= e((string) $baris['posisi_terakhir']) ?></span>
                            <span class="timeline__meta">Waktu: <span class="mono"><?= e((string) $baris['tanggal_jam_pengiriman']) ?></span></span>
                            <span class="timeline__meta">Kurir: <?= e((string) $baris['nama_supir']) ?></span>
                            <span class="timeline__meta">Isi paket: <?= e((string) $baris['desk_isi_paket']) ?></span>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ol>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="empty-state" id="dash-empty"<?= $adaPengiriman ? ' hidden' : '' ?>>
      <span class="empty-state__icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M3 7.5 12 3l9 4.5v9L12 21 3 16.5v-9Zm0 0 9 4.5 9-4.5M12 12v9"/></svg>
      </span>
      <h3>Belum ada data pengiriman</h3>
      <p>Tabel resi pada basis data operasional masih kosong. Mulai dengan membuat pengiriman pertama.</p>
      <a class="btn btn--primary" href="../createDelivery/Create1.php">Buat pengiriman</a>
    </div>

    <div class="empty-state" id="dash-nomatch" hidden>
      <span class="empty-state__icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M10.5 3a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm5.6 13.1L21 21M7.5 10.5h6"/></svg>
      </span>
      <h3>Pengiriman tidak ditemukan</h3>
      <p>Tidak ada baris yang cocok dengan kata kunci atau filter status yang dipilih. Kosongkan pencarian untuk melihat semua pengiriman.</p>
      <button class="btn btn--secondary" type="button" data-reset-filter>Reset filter</button>
    </div>
  </section>
</main>

<?php if ($roleId === 'RL-001'): ?>
  <dialog class="modal" id="delete-dialog" aria-labelledby="delete-dialog-title">
    <form method="post" action="../../controller/login/deleteData.php">
      <div class="modal__head">
        <div>
          <h2 id="delete-dialog-title">Hapus data transit</h2>
          <p>Tindakan ini menghapus semua transit untuk resi terpilih.</p>
        </div>
        <button class="modal__close" type="button" data-close-dialog aria-label="Tutup dialog">&times;</button>
      </div>
      <div class="modal__body">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <label class="field__label" for="delete-resi">Nomor resi</label>
          <input class="input input--mono" id="delete-resi" name="nomor_resi" data-resi-input pattern="RS-[0-9]{7}" maxlength="10" required aria-describedby="delete-resi-help" data-autofocus>
          <p class="field__help" id="delete-resi-help">Format RS diikuti 7 angka, contoh RS-0000001. Hapus hanya jika pengiriman memang batal.</p>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--secondary" type="button" data-close-dialog>Batal</button>
        <button class="btn btn--danger" type="submit">Hapus transit</button>
      </div>
    </form>
  </dialog>
<?php endif; ?>

<script src="../../javascript/site.js" defer></script>
<script src="../../javascript/pages/dashboard.js" defer></script>
</body>
</html>
