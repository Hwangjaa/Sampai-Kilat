<?php
declare(strict_types=1);
require_once '../../controller/login/koneksiDB2.php';

$nomorResi = '';
$error = '';
$empty = '';
$shipment = null;
$timeline = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomorResi = trim((string) ($_POST['nomor_resi'] ?? ''));
    if (!preg_match('/^RS-[0-9]{7}$/', $nomorResi)) {
        $error = 'Masukkan nomor resi dengan format RS-0000001.';
    } else {
        $summary = $conn2->prepare('SELECT r.nomor_resi, p.nama_pelanggan, r.nama_penerima, CONCAT("Jl. ", r.alamat_jalan_penerima, ", ", r.nomor_rumah_penerima, ", ", r.alamat_kecamatan_penerima, ", ", r.alamat_kota_penerima) AS alamat_lengkap FROM resi r JOIN pelanggan p ON p.id_pelanggan = r.id_pelanggan WHERE r.nomor_resi = ? LIMIT 1');
        $summary->bind_param('s', $nomorResi);
        $summary->execute();
        $shipment = $summary->get_result()->fetch_assoc() ?: null;
        $summary->close();

        if ($shipment === null) {
            $empty = 'Nomor resi tidak ditemukan. Periksa kembali nomor resi Anda.';
        } else {
            $events = $conn2->prepare('SELECT t.tanggal_jam_pengiriman, pp.posisi_terakhir FROM transit t JOIN posisi_paket pp ON pp.id_posisi_terakhir_paket = t.id_posisi_terakhir_paket WHERE t.nomor_resi = ? GROUP BY t.tanggal_jam_pengiriman, pp.posisi_terakhir ORDER BY t.tanggal_jam_pengiriman DESC');
            $events->bind_param('s', $nomorResi);
            $events->execute();
            $timeline = $events->get_result()->fetch_all(MYSQLI_ASSOC);
            $events->close();
        }
    }
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cek Resi | SampaiKilat</title>
  <link rel="stylesheet" href="../../css/cekresi/cekresi.css">
</head>
<body>
  <a class="skip-link" href="#utama">Lewati ke isi</a>
  <header class="site-header">
    <a class="brand" href="../../homepage.html"><img src="../../assets/homepage/image/png-clipart-lightning-black-and-white-lightning-angle-white-removebg-preview.png" alt="" width="614" height="406"><span>SampaiKilat</span></a>
    <nav aria-label="Navigasi utama"><a href="../../homepage.html">Beranda</a><a aria-current="page" href="cekresi.php">Cek Resi</a><a href="../cektarif/cektarif.html">Cek Tarif</a><a href="../aboutus/AboutUs.html">Tentang Kami</a><a href="../help/Help.html">Bantuan</a></nav>
  </header>
  <main id="utama" class="page-shell">
    <section class="card" aria-labelledby="page-title">
      <h1 id="page-title">Lacak pengiriman</h1>
      <p>Masukkan nomor resi untuk melihat status paket terbaru.</p>
      <form action="cekresi.php" method="post" class="tracking-form" novalidate>
        <label for="resi-number">Nomor resi</label>
        <div class="tracking-controls"><input type="text" id="resi-number" name="nomor_resi" value="<?= htmlspecialchars($nomorResi, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" pattern="RS-[0-9]{7}" maxlength="10" placeholder="Contoh: RS-0000001" aria-describedby="resi-help resi-feedback" required><button type="submit">Lacak pengiriman</button></div>
        <small id="resi-help">Format nomor resi: RS-0000001.</small>
        <?php $feedback = $error !== '' ? $error : $empty; $feedbackClass = $error !== '' ? 'feedback error' : ($empty !== '' ? 'feedback empty' : 'sr-only'); ?><p id="resi-feedback" class="<?= $feedbackClass ?>"<?= $error !== '' ? ' role="alert"' : ' role="status"' ?>><?= htmlspecialchars($feedback, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
      </form>
    </section>
    <?php if ($shipment !== null): ?>
    <section class="result card" aria-labelledby="tracking-result" aria-live="polite">
      <h2 id="tracking-result" tabindex="-1">Status pengiriman <?= htmlspecialchars($shipment['nomor_resi'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
      <dl class="summary"><div><dt>Pengirim</dt><dd><?= htmlspecialchars($shipment['nama_pelanggan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd></div><div><dt>Penerima</dt><dd><?= htmlspecialchars($shipment['nama_penerima'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd></div><div><dt>Alamat tujuan</dt><dd><?= htmlspecialchars($shipment['alamat_lengkap'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd></div></dl>
      <h3>Riwayat perjalanan</h3>
      <?php if ($timeline): ?><ol class="timeline"><?php foreach ($timeline as $event): ?><li><time datetime="<?= htmlspecialchars($event['tanggal_jam_pengiriman'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($event['tanggal_jam_pengiriman'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></time><strong><?= htmlspecialchars($event['posisi_terakhir'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></li><?php endforeach; ?></ol><?php else: ?><p class="feedback empty">Pengiriman telah dibuat dan menunggu diproses.</p><?php endif; ?>
    </section>
    <script>document.getElementById('tracking-result').focus();</script>
    <?php endif; ?>
  </main>
  <footer class="site-footer"><div><strong>SampaiKilat</strong><p><a href="tel:+622****2222">(021) 222 2222</a> · <a href="mailto:sampai@kilat.co.id">sampai@kilat.co.id</a></p></div></footer>
</body>
</html>
