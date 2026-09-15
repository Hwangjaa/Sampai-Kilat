<?php
declare(strict_types=1);
require_once '../../controller/login/bootstrap.php';
require_login();
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Pengiriman</title><link rel="stylesheet" href="../../css/updatingDelivery/Update3.css">
</head>
<body>
  <main class="content"><div class="form-container">
    <h2>UPDATE PENGIRIMAN</h2>
    <form action="../../controller/login/updateDelivery.php" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label for="nomor_resi">Nomor Resi</label>
      <input type="text" id="nomor_resi" name="nomor_resi" pattern="RS-[0-9]{7}" maxlength="10" required>
      <label for="status">Status Pengiriman</label>
      <select id="status" name="status" required>
        <option value="">Pilih status</option><option>WH Jakarta</option><option>WH Depok</option><option>WH Bogor</option><option>SAMPAI</option><option>WH Tangerang</option><option>WH Bekasi</option>
      </select>
      <div class="form-buttons"><button type="submit" class="update-button">Update</button><a class="cancel-button" href="../homepageAS/HomePageAdminStaff.php">Cancel</a></div>
    </form>
  </div></main>
</body>
</html>
