<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_login();
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Buat Pengiriman — Pengirim</title><link rel="stylesheet" href="../../css/createDelivery/Create1.css"></head>
<body>
<main class="page"><header><a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a><form class="logout-form" method="post" action="../../controller/login/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit">Keluar</button></form></header><section class="card"><h1>Buat Pengiriman</h1><p class="step">Langkah 1 dari 3: data pengirim</p><form method="post" action="../../controller/login/createcontroller.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="id_pengirim">ID pengirim</label><input id="id_pengirim" name="id_pengirim" pattern="PE-[0-9]{7}" maxlength="10" required>
<label for="nama_pengirim">Nama pengirim</label><input id="nama_pengirim" name="nama_pengirim" maxlength="40" required>
<label for="nomor_telepon">Nomor telepon</label><input id="nomor_telepon" name="nomor_telepon" inputmode="tel" maxlength="15" required>
<label for="alamat_pengirim">Alamat pengirim</label><input id="alamat_pengirim" name="alamat_pengirim" maxlength="100" required>
<div class="actions"><a class="button secondary" href="../homepageAS/HomePageAdminStaff.php">Batal</a><button type="submit">Lanjut</button></div>
</form></section></main>
</body></html>
