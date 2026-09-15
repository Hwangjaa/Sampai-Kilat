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
    password_staff char(32) not null,
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
('SF-00001', 'andi.pratama', MD5(MD5(concat(MD5('password123'), 'SampaiKilat'))), 'RL-001'),
('SF-00002', 'budi.santoso', MD5(MD5(concat(MD5('securepass'), 'SampaiKilat'))), 'RL-002'),
('SF-00003', 'citra.dewi', MD5(MD5(concat(MD5('mypassword'), 'SampaiKilat'))), 'RL-002'),
('SF-00004', 'dedi.suhendra', MD5(MD5(concat(MD5('password321'), 'SampaiKilat'))), 'RL-002'),
('SF-00005', 'eka.kurniawan', MD5(MD5(concat(MD5('tebetrocks'), 'SampaiKilat'))), 'RL-002');



