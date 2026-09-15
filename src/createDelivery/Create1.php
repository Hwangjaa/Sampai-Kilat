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
  <link rel="stylesheet" href="../../css/createDelivery/Create1.css">
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
      <form action="../../controller/login/createcontroller.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="resi">ID Pengirim</label>
        <input type="text" id="resi" name="id_pengirim"  placeholder="Masukkan ID Pengirim" required>
    
        <label for="status">Pengirim</label>
        <input type="text" id="status" name="nama_pengirim" placeholder="Masukkan Nama Pengirim" required>
    
        <label for="kurir">Nomor Telepon</label>
        <input type="text" id="kurir" name="nomor_telepon" placeholder="Masukkan Nomor Telepon" required>
    
        <label for="staff">Alamat Pengirim</label>
        <input type="text" id="staff" name="alamat_pengirim" placeholder="Masukkan Alamat" required>
    
        <div class="form-buttons">
            <button type="submit" class="create-button">Next</button>
            <button type="reset" class="cancel-button" onclick="return confirm('Apakah Anda yakin ingin membatalkan?')">Cancel</button>
        </div>
    </form>
    
    </div>
  </div>
</body>
</html>

<script>
  document.querySelector("form").onsubmit = function (e) {
      const inputs = document.querySelectorAll("input");
      for (const input of inputs) {
          if (!input.value) {
              alert("Harap isi semua field sebelum melanjutkan.");
              e.preventDefault();
              return false;
          }
      }
      return true;
  };
</script>

