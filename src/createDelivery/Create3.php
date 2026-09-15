<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_login();
if (empty($_SESSION['shipment_sender']) || empty($_SESSION['shipment_receiver'])) { header('Location: Create1.php', true, 303); exit; }
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Buat Pengiriman — Konfirmasi</title><link rel="stylesheet" href="../../css/createDelivery/Create3.css"></head>
<body><main class="page"><header><a href="../homepageAS/HomePageAdminStaff.php">Dashboard</a><form class="logout-form" method="post" action="../../controller/login/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit">Keluar</button></form></header><section class="card"><h1>Konfirmasi Pengiriman</h1><p class="step">Langkah 3 dari 3. Resi dibuat saat data disimpan.</p><dl><dt>Pengirim</dt><dd><?= e($_SESSION['shipment_sender']['nama_pengirim']) ?></dd><dt>Penerima</dt><dd><?= e($_SESSION['shipment_receiver']['nama_penerima']) ?></dd><dt>Alamat</dt><dd><?= e($_SESSION['shipment_receiver']['alamat_penerima']) ?>, <?= e($_SESSION['shipment_receiver']['alamat_kota']) ?></dd></dl><form method="post" action="../../controller/login/createcontroller3.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="actions"><a class="button secondary" href="Create2.php">Kembali</a><button type="submit">Simpan pengiriman</button></div></form></section></main></body></html>
