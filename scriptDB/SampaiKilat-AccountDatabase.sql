create database if not exists sampaikilat_account;
use sampaikilat_account;

create table staff
(
    id_staff char(8) not null,
    nama_staff varchar(50) not null,
    alamat_jalan_staff varchar(30) not null,
    alamat_kecamatan_staff varchar(30) not null,
    alamat_kota_staff varchar(30) not null,
    gaji_bulanan float not null,
    primary key (id_staff),
    CONSTRAINT staff_id_format CHECK (id_staff REGEXP '^SF-[0-9]{5}+$')

);

create table role_akun
(
    id_role char(6) not null,
    nama_role char(5) not null,
    primary key (id_role),
    CONSTRAINT role_id_format check (id_role REGEXP '^RL-[0-9]{3}+$')
);

create table akun_staff
(
    id_staff char(8) not null,
    username_staff varchar(30) not null,
    password_staff varchar(255) not null,
    id_role char(6) not null,
    foreign key (id_staff) references staff (id_staff) on update cascade on delete cascade,
    foreign key (id_role) references role_akun (id_role) on update cascade on delete cascade
);


INSERT INTO staff (id_staff, nama_staff, alamat_jalan_staff, alamat_kecamatan_staff, alamat_kota_staff, gaji_bulanan)
VALUES 
('SF-00001', 'Andi Pratama', 'Merpati No. 12', 'Kelapa Gading', 'Jakarta Utara', 7000000.00),
('SF-00002', 'Budi Santoso', 'Kenari No. 5', 'Cempaka Putih', 'Jakarta Pusat', 6500000.00),
('SF-00003', 'Citra Dewi', 'Mangga Besar No. 7', 'Tanjung Duren', 'Jakarta Barat', 8000000.00),
('SF-00004', 'Dedi Suhendra', 'Melati No. 3', 'Setiabudi', 'Jakarta Selatan', 7200000.00),
('SF-00005', 'Eka Kurniawan', 'Durian No. 10', 'Tebet', 'Jakarta Selatan', 7500000.00);

INSERT INTO role_akun (id_role, nama_role)
VALUES 
('RL-001', 'Admin'),
('RL-002', 'Staff');

INSERT INTO akun_staff (id_staff, username_staff, password_staff, id_role)
VALUES 
('SF-00001', 'andi.pratama', '$2y$12$bqppxdjS7tj.d/DF5tnozuvc8a/t7AMgtUJGzclyyo6vv7zMVzza6', 'RL-001'),
('SF-00002', 'budi.santoso', '$2y$12$VyjXkT0LBWYRl08yAjv1BeA1AR3g9TA8EFJ9Hp8L.qImwoFprLLpi', 'RL-002'),
('SF-00003', 'citra.dewi', '$2y$12$Ac6Mz1UZH8M5Qk6.cFEcqOWhww58dq7iLd1SQqjYg3qxpVsnqtBni', 'RL-002'),
('SF-00004', 'dedi.suhendra', '$2y$12$GFeDToG6qgM/Deu7XrxPV.Z/oNQmgmNpXNlj9fMIAfb6M1b7F/Ktu', 'RL-002'),
('SF-00005', 'eka.kurniawan', '$2y$12$/1yB65ejXfQ4Kihm0m81GeMBiZZuy/ZbE9TBD3SxkNvMXjKZ5YoXO', 'RL-002');



