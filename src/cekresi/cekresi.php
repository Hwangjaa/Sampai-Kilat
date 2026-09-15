<?php
require '../../controller/login/koneksiDB2.php';
$trackingData = [];
$row = null;

if (isset($_POST['nomor_resi'])) {
    $nomor_resi = trim((string) $_POST['nomor_resi']);
    if (!preg_match('/^RS-[0-9]{7}$/', $nomor_resi)) {
        $nomor_resi = '';
    }


    $query = "
            SELECT Distinct r.nomor_resi, 
                   p.nama_pelanggan, 
                   r.nama_penerima,
    
                   CONCAT('Jl. ', alamat_jalan_penerima, ', ', nomor_rumah_penerima, ', ', alamat_kecamatan_penerima, ', ', alamat_kota_penerima) AS alamat_lengkap, 
                   t.tanggal_jam_pengiriman, 
                   pp.posisi_terakhir 
            FROM resi r 
            JOIN pelanggan p ON p.id_pelanggan = r.id_pelanggan 
            JOIN transit t ON r.nomor_resi = t.nomor_resi 
            JOIN posisi_paket pp ON t.id_posisi_terakhir_paket = pp.id_posisi_terakhir_paket 
            WHERE r.nomor_resi = ? 
            ORDER BY t.tanggal_jam_pengiriman DESC
        ";

    $stmt = $conn2->prepare($query);
    if (!$stmt) {
        die("Kesalahan dalam persiapan statement: " . $conn->error);
    }

    $stmt->bind_param("s", $nomor_resi);

    $stmt->execute();

    $row = null;
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trackingData[] = [
                'status_date' => $row['tanggal_jam_pengiriman'],
                'status_description' => $row['posisi_terakhir'],

            ];
        }
        $result->data_seek(0);

        $row = $result->fetch_assoc();


    } else {
        echo "Tidak ada data ditemukan untuk nomor resi tersebut.";
    }

} else {
    echo "Nomor resi tidak diberikan.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Resi</title>
    <link rel="stylesheet" href="../../css/cekresi/cekresi.css">
</head>

<body>

    <header>
        <div class="logo">
            <img src="../../assets/homepage/image/png-clipart-lightning-black-and-white-lightning-angle-white-removebg-preview.png"
                alt="Logo">
            <span>SampaiKilat</span>
        </div>
        <nav>
            <a href="../../homepage.html">Home</a>
            <a>Cek Resi</a>
            <a href="../aboutus/AboutUs.html">About Us</a>
            <a href="../help/Help.html">Help</a>
        </nav>
    </header>

    <div class="container">
        <h1>Cek Resi</h1>
        <div class="nav-buttons">
            <button class="btn-gray"><b>Cek Resi</b></button>
            <button onclick="window.location.href='../cektarif/cektarif.html';"><b>Cek Tarif</b></button>
            <button onclick="window.location.href='../ceklokasi/CekLokasi.html';"><b>Cek Lokasi</b></button>
        </div>
        <div class="input-section">
            <label for="resi-number"><b>Masukkan Nomor Resi</b></label>
            <form action="cekresi.php" method="post" id="tracking-form">
                <input type="text" id="resi-number" name="nomor_resi" pattern="RS-[0-9]{7}" maxlength="10" required
                    placeholder="Contoh: RS-0000001">
            </form>

            <small>Pencet Enter setelah memasukan resi</small>
        </div>
        <div>
            <button class="track-btn" type="submit" form="tracking-form"><b>Tampilkan Resi</b></button>
        </div>
    </div>

    <div id="trackingModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&larr; Back</span>
            <div class="resi-info">
                <p>Nomor Resi : <span><?php if (isset($row['nomor_resi'])) {
                    echo htmlspecialchars($row['nomor_resi'], ENT_QUOTES, 'UTF-8');
                } ?></span></p>
                <p>Nama Pengirim : <span><?php if (isset($row['nama_pelanggan'])) {
                    echo htmlspecialchars($row['nama_pelanggan'], ENT_QUOTES, 'UTF-8');
                } ?></span></span></p>
                <p>Nama Penerima : <span><?php if (isset($row['nama_penerima'])) {
                    echo htmlspecialchars($row['nama_penerima'], ENT_QUOTES, 'UTF-8');
                } ?></span></p>
                <p>Alamat Penerima : <span><?php if (isset($row['alamat_lengkap'])) {
                    echo htmlspecialchars($row['alamat_lengkap'], ENT_QUOTES, 'UTF-8');
                } ?></span></p>
            </div>
            <hr>
            <div class="tracking-status">
                <?php if (isset($trackingData))
                    foreach ($trackingData as $trackingItem):
                        ?>
                        <div class="status-item">
                            <div class="status-date">
                                <?php if (isset($trackingItem['status_date'])) {
                                    echo htmlspecialchars($trackingItem['status_date']);

                                } ?>
                            </div>
                            <div class="status-info">
                                <div class="status-icon"></div>
                                <p><?php if (isset($trackingItem['status_description'])) {
                                    echo htmlspecialchars($trackingItem['status_description']);

                                } ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
            </div>

        </div>
    </div>


    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <div class="footer-logo-container">
                    <img src="../../assets/homepage/image/png-clipart-lightning-black-and-white-lightning-angle-white-removebg-preview.png"
                        alt="Logo" class="footer-logo">
                    <p><b>SampaiKilat</b></p>
                </div>
                <p>CUSTOMER SERVICE</p>
                <p><img src="../../assets/homepage/image/684846.png" alt=""> (021) 222 2222</p>
                <p><img src="../../assets/homepage/image/png-transparent-computer-icons-envelope-mail-envelope-miscellaneous-angle-triangle-removebg-preview (1).png"
                        alt=""> sampai@kilat.co.id</p>
                <div class="social-media">
                    <img src="../../assets/homepage/image/Twitter-new-cross-mark-Icon-PNG-X-removebg-preview.png"
                        alt="Facebook">
                    <img src="../../assets/homepage/image/59439.png" alt="Instagram">
                    <img src="../../assets/homepage/image/images-removebg-preview.png" alt="Twitter">
                    <img src="../../assets/homepage/image/png-transparent-instagram-vector-brand-logos-icon-removebg-preview.png"
                        alt="Instagram">
                </div>
            </div>
            <div class="footer-section">
                <p><b>PERUSAHAAN</b></p>
                <p>Profil Perusahaan</p>
                <p>Bantuan</p>
            </div>
            <div class="footer-section">
                <p><b>LAYANAN</b></p>
                <p>Lacak Pengiriman</p>
                <p>Cek Tarif</p>
                <p>Lokasi</p>
            </div>
            <div class="footer-section">
                <p><b>Peraturan</b></p>
                <p>Larangan Pengiriman</p>
            </div>
        </div>
    </footer>

    <script>

        const modal = document.getElementById("trackingModal");


        const trackButton = document.querySelector(".track-btn");


        const closeBtn = document.querySelector(".close-btn");


        trackButton.onclick = function () {
            modal.style.display = "block";
        };


        closeBtn.onclick = function () {
            modal.style.display = "none";
        };


        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        };
    </script>

</body>

</html>
