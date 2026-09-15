<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_login();
if (empty($_SESSION['shipment_sender'])) { header('Location: Create1.php', true, 303); exit; }
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Buat Pengiriman — Penerima</title><link rel="stylesheet" href="../../css/createDelivery/Create2.css"></head>
<body><main class="page"><header><a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a><form class="logout-form" method="post" action="../../controller/login/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit">Keluar</button></form></header><section class="card"><h1>Buat Pengiriman</h1><p class="step">Langkah 2 dari 3: data penerima</p><form method="post" action="../../controller/login/createcontroller2.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="nama_penerima">Nama penerima</label><input id="nama_penerima" name="nama_penerima" maxlength="30" required>
<label for="no_penerima">Nomor telepon penerima</label><input id="no_penerima" name="no_penerima" inputmode="tel" maxlength="13" required>
<label for="alamat_penerima">Alamat jalan</label><input id="alamat_penerima" name="alamat_penerima" maxlength="20" required>
<label for="no_rumah">Nomor rumah</label><input id="no_rumah" name="no_rumah" maxlength="20" required>
<label for="alamat_kota">Kota</label><input id="alamat_kota" name="alamat_kota" maxlength="20" required>
<label for="alamat_kecamatan">Kecamatan</label><input id="alamat_kecamatan" name="alamat_kecamatan" maxlength="20" required>
<label for="tanggal_pengiriman">Tanggal permintaan</label><input id="tanggal_pengiriman" name="tanggal_pengiriman" type="date" required>
<label for="id_servis">ID servis</label><input id="id_servis" name="id_servis" value="SR-0000001" pattern="SR-[0-9]{7}" maxlength="10" required><p class="hint">Gunakan ID servis terdaftar.</p>
<div class="actions"><a class="button secondary" href="Create1.php">Kembali</a><button type="submit">Lanjut</button></div>
</form></section></main></body></html>
