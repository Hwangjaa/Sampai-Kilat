<?php
require_once '../../controller/login/bootstrap.php';
require_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Pelanggan</title>
  <link rel="stylesheet" href="../../css/mendaftarkanPelanggan/pelanggan.css">
</head>
<body>
  <div class="form-container">
    <h2>Form Pendaftaran Pelanggan</h2>
    <form action="../../controller/login/submitPelanggan.php" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label for="nama_pelanggan">Nama Pelanggan</label>
      <input type="text" id="nama_pelanggan" name="nama_pelanggan" maxlength="40" required>

      <label for="alamat_jalan_pelanggan">Alamat Jalan</label>
      <input type="text" id="alamat_jalan_pelanggan" name="alamat_jalan_pelanggan" maxlength="20" required>

      <label for="alamat_kecamatan_pelanggan">Alamat Kecamatan</label>
      <input type="text" id="alamat_kecamatan_pelanggan" name="alamat_kecamatan_pelanggan" maxlength="20" required>

      <label for="alamat_kota_pelanggan">Alamat Kota</label>
      <input type="text" id="alamat_kota_pelanggan" name="alamat_kota_pelanggan" maxlength="20" required>

      <label for="nomor_telpon">Nomor Telepon</label>
      <input type="tel" id="nomor_telpon" name="nomor_telpon" placeholder="Contoh: +6281234567890" required pattern="^[+0-9]+$" maxlength="15">

      <button type="submit">Daftarkan Pelanggan</button>
    </form>
  </div>
</body>
</html>
