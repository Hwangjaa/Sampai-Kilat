<?php
require_once '../../controller/login/bootstrap.php';
require_login();
$username=$_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Pengiriman</title>
  <link rel="stylesheet" href="../../css/createDelivery/Create2.css">
</head>
<body>
  <div class="sidebar">
    <div class="profile">
      <div class="profile-picture">
        <img src="../../assets/homepagestaff/image/UserIcon.png" alt="Profile Picture">
      </div>
      <p class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    <a class="logout-button" href="../../controller/login/logout.php">
      <img src="../../assets/homepagestaff/image/logout-512.jpg" alt="Logout Icon">
      <span>LogOut</span>
    </a>
  </div>

  <div class="content">
    <div class="form-container">
      <h2>INPUT PENGIRIMAN</h2>
      <form action="../../controller/login/createcontroller2.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="penerima">Nama Penerima</label>
        <input type="text" id="penerima" name="nama_penerima" placeholder="Masukkan Nama Penerima" required>

        <label for="penerima">ID Pelanggan</label>
        <input type="text" id="penerima" name="ID_Pelanggan" placeholder="Masukkan Nama Penerima" required>
    
        <label for="telp">Nomor Telepon Penerima</label>
        <input type="text" id="telp" name="no_penerima" placeholder="Masukkan Nomor Telepon" required>
    
        <label for="alamat">Alamat Penerima</label>
        <input type="text" id="alamat" name="alamat_penerima" placeholder="Masukkan Alamat Penerima" required>

        <label for="alamat">No Rumah</label>
        <input type="text" id="alamat" name="no_rumah" placeholder="Masukkan Alamat Penerima" required>
        
        <label for="alamat">Alamat Kota</label>
        <input type="text" id="alamat" name="alamat_kota" placeholder="Masukkan Alamat Penerima" required>

        <label for="alamat">Alamat Kecamatan</label>
        <input type="text" id="alamat" name="alamat_kecamatan" placeholder="Masukkan Alamat Penerima" required>

        <label for="alamat">Tanggal Pengiriman</label>
        <input type="date" id="tanggal_pengiriman" name="tanggal_pengiriman" required>

        <label for="alamat">ID Servis</label>
        <input type="text" id="alamat" name="ID_Servis" placeholder="Masukkan Alamat Penerima" required>


        <div class="form-buttons">
            <button type="submit" class="create-button">Next</button>
            <button type="reset" class="cancel-button" onclick="return confirm('Apakah Anda yakin ingin membatalkan?')">Cancel</button>
        </div>
    </form>
    
    </div>
  </div>
</body>
</html>
