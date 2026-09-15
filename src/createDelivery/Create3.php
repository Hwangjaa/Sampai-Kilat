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
  <link rel="stylesheet" href="../../css/createDelivery/Create3.css">
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
      <form action="../../controller/login/createcontroller3.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="resi">Resi</label>
        <input type="text" id="resi" name="resi1" placeholder="Masukkan Resi" required>
    
        <label for="status">Status Pengiriman</label>
        <input type="text" id="status" name="status1" placeholder="Masukkan Status Pengiriman" required>
    
        <label for="kurir">Kurir</label>
        <input type="text" id="kurir" placeholder="Masukkan Nama Kurir" required>
    
        <label for="layanan">Layanan Pengiriman</label>
        <input type="text" id="layanan" name="layanan1" placeholder="Masukkan Layanan Pengiriman" required>
    
        <div class="form-buttons">
            <button type="submit" class="create-button">Create</button>
            <button type="reset" class="cancel-button" onclick="return confirm('Apakah Anda yakin ingin membatalkan?')">Cancel</button>
        </div>
    </form>

    <div id="successPopup" class="popup-container">
      <div class="popup-content">
          <p>Data pengiriman telah berhasil dimasukkan!</p>
          <button onclick="redirectToDashboard()">OK</button>
      </div>
  </div>
  
    
    </div>
  </div>
</body>
</html>

<script>
  function showSuccessPopup(event) {
      event.preventDefault(); // Mencegah pengiriman form
      document.getElementById('successPopup').style.display = 'flex';
  }

  function redirectToDashboard() {
      window.location.href = "../homepageAS/HomePageAdminStaff.html";
  }
</script>
