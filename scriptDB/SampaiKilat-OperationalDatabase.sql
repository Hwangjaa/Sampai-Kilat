CREATE DATABASE IF NOT EXISTS sampaikilat_operational;
USE sampaikilat_operational;

CREATE TABLE jenis_pembayaran (
    id_jenis_pembayaran CHAR(6) NOT NULL,
    desk_jenis_pembayaran VARCHAR(10) NOT NULL,
    PRIMARY KEY (id_jenis_pembayaran),
    CONSTRAINT id_jenis_pembayaran_format CHECK (id_jenis_pembayaran REGEXP '^JP-[0-9]{3}+$')
);

CREATE TABLE tipe_pengiriman (
    id_tipe_pengiriman CHAR(6) NOT NULL,
    desk_tipe_pengiriman VARCHAR(8) NOT NULL,
    PRIMARY KEY (id_tipe_pengiriman),
    CONSTRAINT id_tipe_pengiriman_format CHECK (id_tipe_pengiriman REGEXP '^TP-[0-9]{3}+$')
);

CREATE TABLE tipe_paket (
    id_tipe_paket CHAR(6) NOT NULL,
    desk_tipe_paket VARCHAR(8) NOT NULL,
    PRIMARY KEY (id_tipe_paket),
    CONSTRAINT id_tipe_paket_format CHECK (id_tipe_paket REGEXP '^TT-[0-9]{3}+$')
);

CREATE TABLE jenis_paket (
    id_jenis_paket CHAR(6) NOT NULL,
    desk_jenis_paket VARCHAR(8) NOT NULL,
    PRIMARY KEY (id_jenis_paket),
    CONSTRAINT id_jenis_paket_format CHECK (id_jenis_paket REGEXP '^JT-[0-9]{3}+$')
);

CREATE TABLE pelanggan (
    id_pelanggan CHAR(10) NOT NULL,
    nama_pelanggan VARCHAR(40) NOT NULL,
    alamat_jalan_pelanggan VARCHAR(20) NOT NULL,
    alamat_kecamatan_pelanggan VARCHAR(20) NOT NULL,
    alamat_kota_pelanggan VARCHAR(20) NOT NULL,
    nomor_telpon VARCHAR(15) NOT NULL,
    PRIMARY KEY (id_pelanggan),
    CONSTRAINT id_pelanggan_format CHECK (id_pelanggan REGEXP '^PE-[0-9]{7}+$'),
    CONSTRAINT nomor_telpon_format CHECK (nomor_telpon REGEXP '^[+0-9]')
);

CREATE TABLE servis (
    id_servis CHAR(10) NOT NULL,
    total_berat_paket FLOAT NOT NULL,
    id_tipe_pengiriman CHAR(6) NOT NULL,
    id_tipe_paket CHAR(6) NOT NULL,
    biaya FLOAT NOT NULL,
    id_jenis_pembayaran CHAR(6) NOT NULL,
    jam_pembayaran TIME NOT NULL,
    id_jenis_paket CHAR(6) NOT NULL,
    PRIMARY KEY (id_servis),
    FOREIGN KEY (id_tipe_pengiriman) REFERENCES tipe_pengiriman (id_tipe_pengiriman) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_tipe_paket) REFERENCES tipe_paket (id_tipe_paket) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_jenis_pembayaran) REFERENCES jenis_pembayaran (id_jenis_pembayaran) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_jenis_paket) REFERENCES jenis_paket (id_jenis_paket) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT id_servis_format CHECK (id_servis REGEXP '^SR-[0-9]{7}+$')
);

CREATE TABLE resi (
    nomor_resi CHAR(10) NOT NULL,
    id_pelanggan CHAR(10) NOT NULL,
    nama_penerima VARCHAR(30) NOT NULL,
    alamat_jalan_penerima VARCHAR(20) NOT NULL,
    nomor_rumah_penerima VARCHAR(20) NOT NULL,
    alamat_kota_penerima VARCHAR(20) NOT NULL,
    alamat_kecamatan_penerima VARCHAR(20) NOT NULL,
    nomor_telpon_penerima VARCHAR(13) NOT NULL,
    tanggal_permintaan_pengiriman DATE NOT NULL,
    id_servis CHAR(10) NOT NULL,
    PRIMARY KEY (nomor_resi),
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan (id_pelanggan),
    FOREIGN KEY (id_servis) REFERENCES servis (id_servis),
    CONSTRAINT nomor_telpon_penerima_format CHECK (nomor_telpon_penerima REGEXP '^[+0-9]')
);

CREATE TABLE isi_paket (
    id_isi_paket CHAR(20) NOT NULL,
    desk_isi_paket VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_isi_paket),
    CONSTRAINT id_isi_paket_format CHECK (id_isi_paket REGEXP '^IP-[0-9]{17}')
);

CREATE TABLE posisi_paket (
    id_posisi_terakhir_paket CHAR(6) NOT NULL,
    posisi_terakhir VARCHAR(14) NOT NULL,
    PRIMARY KEY (id_posisi_terakhir_paket),
    CONSTRAINT id_posisi_terakhir_paket_format CHECK (id_posisi_terakhir_paket REGEXP '^PS-[0-9]{3}+$')
);

CREATE TABLE supir (
    plat_nomor_kendaraan VARCHAR(12) NOT NULL,
    nama_supir VARCHAR(50) NOT NULL,
    daerah_supir VARCHAR(30) NOT NULL,
    jenis_kendaraan VARCHAR(10) NOT NULL,
    PRIMARY KEY (plat_nomor_kendaraan)
);

CREATE TABLE transit (
    nomor_resi CHAR(10) NOT NULL,
    id_isi_paket CHAR(20) NOT NULL,
    plat_nomor_kendaraan VARCHAR(12) not null,
    tanggal_jam_pengiriman TIMESTAMP not null,
    id_posisi_terakhir_paket CHAR(6) not null,
    FOREIGN KEY (nomor_resi) REFERENCES resi (nomor_resi),
    FOREIGN KEY (id_isi_paket) REFERENCES isi_paket (id_isi_paket),
    FOREIGN KEY (plat_nomor_kendaraan) REFERENCES supir (plat_nomor_kendaraan),
    FOREIGN KEY (id_posisi_terakhir_paket) REFERENCES posisi_paket (id_posisi_terakhir_paket)
);


INSERT INTO pelanggan (id_pelanggan, nama_pelanggan, alamat_jalan_pelanggan, alamat_kecamatan_pelanggan, alamat_kota_pelanggan, nomor_telpon)
VALUES
('PE-0000001', 'Rudi Santoso', 'Raya Hehe', 'Duri Kepak', 'Jakarta Utara', '08141893401'),
('PE-0000002', 'Christopher Asadi', 'Cimande 2', 'Bujur Sangkar', 'Tangerang Selatan', '08313521343'),
('PE-0000003', 'Faisal Limawan', 'Dadap Villa 3', 'Prancis', 'Kabupaten Tangerang', '08124753253'),
('PE-0000004', 'Agus Setiawan', 'Sukamurah', 'Sukasari', 'Jakarta Barat', '08123487245'),
('PE-0000005', 'Joko Susilo', 'Haji Andrey', 'Singosaro', 'Depok', '08479817344'),
('PE-0000006', 'Slamet Riyadi', 'Komeng sejahtera', 'Pulosari', 'Bogor Selatan', '08927458324'),
('PE-0000007', 'Siti Rahayu', 'Komeng Rahayu', 'Pulosari', 'Bogor Selatan', '08128785332'),
('PE-0000008', 'Maya Dewi', 'Pemuda 21', 'Undarpano', 'Depok', '08139858152'),
('PE-0000009', 'Imam Subadrio', 'Pantai Indah Kapuk', 'PIK 2', 'Jakarta Utara', '08139754324'),
('PE-0000010', 'Ani Cahaya', 'Anyilir IV', 'Dragonbay', 'Kabupaten Tangerang', '08128646835'),
('PE-0000011', 'Burde Irawan', 'Kebangsaan 12', 'Pondega', 'Jakarta Utara', '08192412414');


INSERT INTO tipe_pengiriman (id_tipe_pengiriman, desk_tipe_pengiriman)
VALUES
('TP-001', 'Reguler'),
('TP-002', 'Instan');


INSERT INTO tipe_paket (id_tipe_paket, desk_tipe_paket)
VALUES
('TT-003', 'Besar'),
('TT-001', 'Kecil'),
('TT-002', 'Sedang');


INSERT INTO jenis_pembayaran (id_jenis_pembayaran, desk_jenis_pembayaran)
VALUES
('JP-001', 'Tunai'),
('JP-002', 'Non-tunai');


INSERT INTO jenis_paket (id_jenis_paket, desk_jenis_paket)
VALUES
('JT-001', 'Kokoh'),
('JT-002', 'Rapuh');


INSERT INTO posisi_paket (id_posisi_terakhir_paket, posisi_terakhir)
VALUES
('PS-002', 'WH Jakarta'),
('PS-003', 'WH Depok'),
('PS-005', 'WH Bogor'),
('PS-006', 'SAMPAI'),
('PS-001', 'WH Tangerang'),
('PS-004', 'WH Bekasi');


INSERT INTO isi_paket (id_isi_paket, desk_isi_paket)
VALUES
('IP-00000000000000001', 'Susu'),
('IP-00000000000000002', 'Minyak'),
('IP-00000000000000003', 'Gula'),
('IP-00000000000000004', 'Kue Kering'),
('IP-00000000000000005', 'Baju'),
('IP-00000000000000006', 'Laptop'),
('IP-00000000000000007', 'Komputer'),
('IP-00000000000000008', 'Gitar'),
('IP-00000000000000009', 'Alat mandi'),
('IP-00000000000000010', 'Keyboard gaming'),
('IP-00000000000000011', 'Handphone'),
('IP-00000000000000012', 'Casing HP'),
('IP-00000000000000013', 'Botol Minum'),
('IP-00000000000000014', 'Tas sekolah'),
('IP-00000000000000015', 'Alat tulis'),
('IP-00000000000000016', 'Hoodie'),
('IP-00000000000000017', 'Parfume Dior sauvage'),
('IP-00000000000000018', 'Kaset Film 20pcs'),
('IP-00000000000000019', 'Printer'),
('IP-00000000000000020', 'Buku'),
('IP-00000000000000021', 'Majalah');


INSERT INTO supir (plat_nomor_kendaraan, nama_supir, daerah_supir, jenis_kendaraan)
VALUES
('B 9012 GHI', 'Rendy Purwanto', 'Jakarta', 'Truk'),
('B 9900 BCD', 'Bobby Indrawan', 'Depok', 'Truk'),
('F 9012 HIJ', 'C. Ronaldi', 'Bogor', 'Truk'),
('F 3456 KLM', 'Ibrahim Diaz', 'Bogor', 'Van'),
('B 1234 ABC', 'Dodi Supratman', 'Tangerang', 'Truk'),
('B 7890 MNO', 'Sergio Termos', 'Tangerang', 'Van'),
('B 5678 DEF', 'Lionel Pessi', 'Tangerang', 'Truk'),
('B 5566 VWX', 'Marcello', 'Jakarta', 'Truk'),
('B 5678 EFG', 'Kylina Bappe', 'Jakarta', 'Van'),
('B 5678 NOP', 'Cipto Harsono', 'Jakarta Barat', 'Truk'),
('B 9900 CDF', 'Eko Purwanto', 'Jakarta Selatan', 'Motor'),
('B 9012 NOP', 'Rizki Fadilah', 'Beji', 'Truk'),
('B 9012 QRS', 'Dadang Suryadi', 'Jakarta Timur', 'Truk'),
('B 5566 WXY', 'Candra Wijaya', 'Tangerang Selatan', 'Motor'),
('B 7788 EFG', 'Yoga Pratama', 'Tangerang Selatan', 'Truk'),
('B 3456 QRS', 'Sandi Pratama', 'Bojongsari', 'Truk'),
('B 1234 CDE', 'Zinedane Zidane', 'Depok', 'Motor'),
('F 7890 QRS', 'Joko Susanto', 'Bogor Selatan', 'Truk'),
('B 3456 JKL', 'Lelek Pondega', 'Depok', 'Truk'),
('B 9900 HIJ', 'Zainal Arifin', 'Jakarta Utara', 'Truk'),
('B 7788 ZAB', 'Dedi Setiawan', 'Jakarta Utara', 'Motor'),
('F 3344 WXY', 'Luki Hartanto', 'Bogor Barat', 'Truk'),
('F 5566 ZAB', 'Mahendra Wijaya', 'Bogor Tengah', 'Motor'),
('B 5678 HIJ', 'Gani Rachman', 'Jakarta Timur', 'Truk'),
('B 1122 PQR', 'Budi Siregar', 'Bekasi', 'Truk'),
('B 7890 NOP', 'Jese Morinto', 'Bekasi', 'Motor'),
('F 7788 YZA', 'Timen Suratno', 'Bogor', 'Truk'),
('F 3456 NOP', 'Indra Kurniawan', 'Bogor Utara', 'Motor');

INSERT INTO servis (id_servis, total_berat_paket, id_tipe_pengiriman, id_tipe_paket, biaya, id_jenis_pembayaran, jam_pembayaran, id_jenis_paket)
VALUES
('SR-0000001', 7.2, 'TP-001', 'TT-003', 37000, 'JP-001', '07:00:00', 'JT-001'),
('SR-0000002', 16.5, 'TP-002', 'TT-003', 74000, 'JP-002', '08:00:00', 'JT-002'),
('SR-0000003', 3.0, 'TP-002', 'TT-001', 44000, 'JP-001', '09:00:00', 'JT-002'),
('SR-0000004', 0.5, 'TP-002', 'TT-001', 34000, 'JP-001', '10:00:00', 'JT-001'),
('SR-0000005', 0.5, 'TP-002', 'TT-001', 40000, 'JP-001', '11:00:00', 'JT-002'),
('SR-0000006', 0.3, 'TP-001', 'TT-001', 17000, 'JP-002', '12:00:00', 'JT-002'),
('SR-0000007', 0.8, 'TP-001', 'TT-001', 20000, 'JP-002', '13:00:00', 'JT-001'),
('SR-0000008', 0.25, 'TP-002', 'TT-001', 40000, 'JP-002', '14:00:00', 'JT-001'),
('SR-0000009', 0.45, 'TP-002', 'TT-001', 40000, 'JP-001', '15:00:00', 'JT-002'),
('SR-0000010', 2.0, 'TP-002', 'TT-001', 40000, 'JP-002', '16:00:00', 'JT-002'),
('SR-0000011', 4.0, 'TP-001', 'TT-002', 27000, 'JP-001', '17:00:00', 'JT-002'),
('SR-0000012', 6.0, 'TP-001', 'TT-002', 37000, 'JP-001', '18:00:00', 'JT-001');


INSERT INTO resi (nomor_resi, id_pelanggan, nama_penerima, alamat_jalan_penerima, nomor_rumah_penerima, alamat_kecamatan_penerima, alamat_kota_penerima, nomor_telpon_penerima, tanggal_permintaan_pengiriman, id_servis)
VALUES
('RS-0000001', 'PE-0000001', 'Ananda Putri', 'Sukaria', 'No. 1', 'Silingsari', 'Bogor Selatan', '089378880863', '2024-08-05', 'SR-0000001'),
('RS-0000002', 'PE-0000002', 'Bambang Suryadi', 'Rs Bun', 'No. 2', 'Teluknaga', 'Kabupaten Tangerang', '086483647799', '2024-08-05', 'SR-0000002'),
('RS-0000003', 'PE-0000003', 'Citra Dewi', 'Ruko 1000', 'No. 3', 'Pentek', 'Jakarta Barat', '081209876545', '2024-08-05', 'SR-0000003'),
('RS-0000004', 'PE-0000004', 'Dika Pratama', 'Bobonsari', 'No. 4', 'Cakung Barat', 'Jakarta Pusat', '082234098678', '2024-10-05', 'SR-0000004'),
('RS-0000005', 'PE-0000005', 'Endah Kartika', 'Multifungsi', 'No. 5', 'Gading Marten', 'Tangerang Selatan', '089274856358', '2024-10-05', 'SR-0000005'),
('RS-0000006', 'PE-0000006', 'Fahmi Santoso', 'Rintik Hujan', 'No. 6', 'Sedih Euy', 'Depok', '081637465846', '2024-10-05', 'SR-0000006'),
('RS-0000007', 'PE-0000007', 'Galih Wibowo', 'Banteng 4', 'No. 7', 'Komando', 'Jakarta Selatan', '081824658612', '2024-11-05', 'SR-0000007'),
('RS-0000008', 'PE-0000008', 'Hana Indah', 'Sukamulya 1', 'No. 8', 'Jatiuwung', 'Bogor Utara', '081723849723', '2024-11-05', 'SR-0000008'),
('RS-0000009', 'PE-0000009', 'Irfan Nugroho', 'Franklin 11', 'No. 9', 'Repdal', 'Bekasi Timur', '083467467777', '2024-12-05', 'SR-0000009'),
('RS-0000010', 'PE-0000010', 'Kartika Sari', 'Bellingham 8', 'No. 10', 'Camavinga', 'Jakarta Timur', '081111777778', '2024-12-05', 'SR-0000010'),
('RS-0000011', 'PE-0000004', 'Christopher Colombus', 'Sukaria 4', 'No. 11', 'Silingsari', 'Bogor Selatan', '087165405237', '2024-05-13', 'SR-0000011'),
('RS-0000012', 'PE-0000011', 'Lita Sulastri', 'Sukaria 2', 'No. 12', 'Silingsari', 'Bogor Selatan', '081746815302', '2024-05-13', 'SR-0000012');



INSERT INTO transit (nomor_resi, id_isi_paket, plat_nomor_kendaraan, tanggal_jam_pengiriman, id_posisi_terakhir_paket) VALUES
('RS-0000001', 'IP-00000000000000001', 'B 9012 GHI', '2024-05-08 08:00:00', 'PS-002'),
('RS-0000001', 'IP-00000000000000002', 'B 9012 GHI', '2024-05-08 08:00:00', 'PS-002'),
('RS-0000001', 'IP-00000000000000003', 'B 9012 GHI', '2024-05-08 08:00:00', 'PS-002'),
('RS-0000001', 'IP-00000000000000004', 'B 9012 GHI', '2024-05-08 08:00:00', 'PS-002'),
('RS-0000001', 'IP-00000000000000005', 'B 9012 GHI', '2024-05-08 08:00:00', 'PS-002'),
('RS-0000002', 'IP-00000000000000006', 'B 1234 ABC', '2024-05-08 09:00:00', 'PS-001'),
('RS-0000002', 'IP-00000000000000007', 'B 1234 ABC', '2024-05-08 09:00:00', 'PS-001'),
('RS-0000003', 'IP-00000000000000008', 'B 5678 DEF', '2024-05-08 10:00:00', 'PS-001'),
('RS-0000003', 'IP-00000000000000008', 'B 5566 VWX', '2024-05-09 09:00:00', 'PS-002'),
('RS-0000002', 'IP-00000000000000006', 'B 7890 MNO', '2024-05-09 11:00:00', 'PS-006'),
('RS-0000002', 'IP-00000000000000007', 'B 7890 MNO', '2024-05-09 11:00:00', 'PS-006'),
('RS-0000001', 'IP-00000000000000001', 'B 9900 BCD', '2024-05-09 13:00:00', 'PS-003'),
('RS-0000001', 'IP-00000000000000002', 'B 9900 BCD', '2024-05-09 13:00:00', 'PS-003'),
('RS-0000001', 'IP-00000000000000003', 'B 9900 BCD', '2024-05-09 13:00:00', 'PS-003'),
('RS-0000001', 'IP-00000000000000004', 'B 9900 BCD', '2024-05-09 13:00:00', 'PS-003'),
('RS-0000001', 'IP-00000000000000005', 'B 9900 BCD', '2024-05-09 13:00:00', 'PS-003'),
('RS-0000003', 'IP-00000000000000008', 'B 5678 EFG', '2024-05-09 14:00:00', 'PS-006'),
('RS-0000004', 'IP-00000000000000009', 'B 5678 NOP', '2024-05-10 11:00:00', 'PS-002'),
('RS-0000005', 'IP-00000000000000010', 'B 9012 NOP', '2024-05-10 12:00:00', 'PS-003'),
('RS-0000006', 'IP-00000000000000011', 'F 3456 KLM', '2024-05-10 13:00:00', 'PS-005'),
('RS-0000006', 'IP-00000000000000012', 'F 3456 KLM', '2024-05-10 13:00:00', 'PS-005'),
('RS-0000005', 'IP-00000000000000010', 'B 9012 QRS', '2024-05-10 17:00:00', 'PS-002'),
('RS-0000001', 'IP-00000000000000001', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-005'),
('RS-0000001', 'IP-00000000000000002', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-005'),
('RS-0000001', 'IP-00000000000000003', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-005'),
('RS-0000001', 'IP-00000000000000004', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-005'),
('RS-0000001', 'IP-00000000000000005', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-005'),
('RS-0000005', 'IP-00000000000000010', 'B 5566 WXY', '2024-05-11 07:00:00', 'PS-001'),
('RS-0000001', 'IP-00000000000000001', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-006'),
('RS-0000001', 'IP-00000000000000002', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-006'),
('RS-0000001', 'IP-00000000000000003', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-006'),
('RS-0000001', 'IP-00000000000000004', 'F 9012 HIJ', '2024-05-11 07:00:00', 'PS-006'),
('RS-0000001', 'IP-00000000000000005', 'F 3456 KLM', '2024-05-11 12:00:00', 'PS-006'),
('RS-0000004', 'IP-00000000000000009', 'B 9900 CDF', '2024-05-11 14:00:00', 'PS-006'),
('RS-0000007', 'IP-00000000000000013', 'F 7890 QRS', '2024-05-11 14:00:00', 'PS-005'),
('RS-0000007', 'IP-00000000000000014', 'F 7890 QRS', '2024-05-11 14:00:00', 'PS-005'),
('RS-0000007', 'IP-00000000000000015', 'F 7890 QRS', '2024-05-11 14:00:00', 'PS-005'),
('RS-0000005', 'IP-00000000000000010', 'B 7788 EFG', '2024-05-11 15:00:00', 'PS-006'),
('RS-0000008', 'IP-00000000000000016', 'B 9900 BCD', '2024-05-11 15:00:00', 'PS-003'),
('RS-0000007', 'IP-00000000000000013', 'B 3456 JKL', '2024-05-11 21:00:00', 'PS-003'),
('RS-0000007', 'IP-00000000000000014', 'B 3456 JKL', '2024-05-11 21:00:00', 'PS-003'),
('RS-0000007', 'IP-00000000000000015', 'B 3456 JKL', '2024-05-11 21:00:00', 'PS-003'),
('RS-0000008', 'IP-00000000000000016', 'F 3344 WXY', '2024-05-11 23:00:00', 'PS-005'),
('RS-0000008', 'IP-00000000000000016', 'F 5566 ZAB', '2024-05-12 11:00:00', 'PS-006'),
('RS-0000006', 'IP-00000000000000011', 'B 3456 QRS', '2024-05-12 15:00:00', 'PS-003'),
('RS-0000006', 'IP-00000000000000012', 'B 3456 QRS', '2024-05-12 15:00:00', 'PS-003'),
('RS-0000009', 'IP-00000000000000017', 'B 5678 HIJ', '2024-05-12 16:00:00', 'PS-002'),
('RS-0000010', 'IP-00000000000000018', 'B 1234 ABC', '2024-05-12 17:00:00', 'PS-001'),
('RS-0000006', 'IP-00000000000000011', 'B 1234 CDE', '2024-05-13 07:00:00', 'PS-006'),
('RS-0000006', 'IP-00000000000000012', 'B 1234 CDE', '2024-05-13 07:00:00', 'PS-006'),
('RS-0000009', 'IP-00000000000000017', 'B 1122 PQR', '2024-05-13 07:00:00', 'PS-004'),
('RS-0000010', 'IP-00000000000000018', 'B 9012 GHI', '2024-05-13 08:00:00', 'PS-002'),
('RS-0000009', 'IP-00000000000000017', 'B 7890 NOP', '2024-05-13 14:00:00', 'PS-006'),
('RS-0000010', 'IP-00000000000000018', 'B 5566 VWX', '2024-05-13 18:00:00', 'PS-006'),
('RS-0000011', 'IP-00000000000000019', 'B 5678 EFG', '2024-05-13 20:00:00', 'PS-002'),
('RS-0000012', 'IP-00000000000000020', 'B 5678 EFG', '2024-05-13 20:00:00', 'PS-002'),
('RS-0000012', 'IP-00000000000000021', 'B 5678 EFG', '2024-05-13 20:00:00', 'PS-002'),
('RS-0000007', 'IP-00000000000000013', 'B 9900 HIJ', '2024-05-14 09:00:00', 'PS-002'),
('RS-0000007', 'IP-00000000000000014', 'B 9900 HIJ', '2024-05-14 09:00:00', 'PS-002'),
('RS-0000007', 'IP-00000000000000015', 'B 9900 HIJ', '2024-05-14 09:00:00', 'PS-002'),
('RS-0000007', 'IP-00000000000000013', 'B 7788 ZAB', '2024-05-14 18:00:00', 'PS-006'),
('RS-0000007', 'IP-00000000000000014', 'B 7788 ZAB', '2024-05-14 18:00:00', 'PS-006'),
('RS-0000007', 'IP-00000000000000015', 'B 7788 ZAB', '2024-05-14 18:00:00', 'PS-006'),
('RS-0000011', 'IP-00000000000000019', 'B 3456 JKL', '2024-05-14 18:00:00', 'PS-003'),
('RS-0000012', 'IP-00000000000000020', 'B 3456 JKL', '2024-05-14 07:00:00', 'PS-003'),
('RS-0000012', 'IP-00000000000000021', 'B 3456 JKL', '2024-05-15 07:00:00', 'PS-003'),
('RS-0000011', 'IP-00000000000000019', 'F 7788 YZA', '2024-05-16 07:45:00', 'PS-005'),
('RS-0000012', 'IP-00000000000000020', 'F 7788 YZA', '2024-05-16 07:45:00', 'PS-005'),
('RS-0000012', 'IP-00000000000000021', 'F 7788 YZA', '2024-05-16 07:45:00', 'PS-005'),
('RS-0000011', 'IP-00000000000000019', 'F 3456 NOP', '2024-05-16 16:30:00', 'PS-006'),
('RS-0000012', 'IP-00000000000000020', 'F 3456 NOP', '2024-05-16 16:45:00', 'PS-006'),
('RS-0000012', 'IP-00000000000000021', 'F 3456 NOP', '2024-05-16 16:45:00', 'PS-006');