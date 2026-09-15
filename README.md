# Sampai Kilat

Website kurir paket PHP + MySQL.

## Jalankan lokal

1. Aktifkan Apache/PHP/MySQL.
2. Import `scriptDB/SampaiKilat-AccountDatabase.sql`.
3. Import `scriptDB/SampaiKilat-OperationalDatabase.sql`.
4. Ubah kredensial DB lewat environment sebelum deployment. Default lokal saat ini ada di `controller/login/koneksiDB.php` dan `koneksiDB2.php`.
5. Buka `homepage.html`.

## Catatan security

- Password legacy masih memakai hash MD5 bertingkat karena schema lama menyimpan `CHAR(32)`. Ganti ke `password_hash()` + kolom `VARCHAR(255)` sebelum production.
- Gunakan HTTPS. Jangan expose folder `controller/`, `scriptDB/`, atau file backup melalui web server.
- PHP CLI tidak tersedia di environment audit ini; lint PHP harus dijalankan pada host PHP.
