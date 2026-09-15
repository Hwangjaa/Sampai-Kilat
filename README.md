# Sampai Kilat — Sistem Informasi Pengiriman Paket

**Sampai Kilat** adalah website jasa kurir pengiriman paket yang dibangun
sebagai tugas mata kuliah **Secure Programming**. Project ini dikerjakan secara
berkelompok (lihat daftar anggota di bagian bawah) dan berfokus pada dua hal:

1. **Fungsionalitas** — alur bisnis pengiriman paket yang utuh: lacak resi,
   input pengiriman, update status, dan dashboard staff.
2. **Secure coding** — penerapan prinsip keamanan aplikasi web pada sisi
   server (PHP) maupun konfigurasi (MySQL, session, header HTTP).

---

## Cerita Website

Sampai Kilat adalah simulasi perusahaan kurir lokal dengan tagline
*"Sampai Tujuan, Secepat Kilat!"*. Website-nya terbagi dua sisi:

### Sisi Publik (pelanggan)

- **Homepage** — profil layanan: pengiriman global (udara & darat), COD,
  customer service 24 jam.
- **Cek Resi** — pelanggan memasukkan nomor resi berformat `RS-0000000`,
  lalu melihat status terakhir paket beserta riwayat perjalanan
  (gedung gudang mana saja yang sudah dilewati).
- **Cek Tarif**, **Cek Lokasi**, **About Us**, **Help** — halaman
  pendukung informasi layanan.
- **Pendaftaran Pelanggan** — form registrasi data pelanggan pengirim.

### Sisi Internal (admin & staff gudang)

Dilindungi login. Menu:

- **Dashboard** — tabel seluruh pengiriman: nomor resi, isi paket, kurir
  (supir), waktu kirim, dan posisi terakhir paket.
- **Create Pengiriman** — form bertahap (3 langkah): data pengirim →
  data penerima → layanan & konfirmasi. Akhirnya menghasilkan nomor resi
  baru di database.
- **Update Pengiriman** — staff memperbarui posisi paket saat paket tiba
  di gudang berikutnya (WH Tangerang → WH Jakarta → ... → SAMPAI).
- **Delete** — hanya untuk role Admin (`RL-001`); menghapus data transit.

### Database

Dua database terpisah sesuai prinsip pemisahan data:

| Database                  | Isi                                                        |
|---------------------------|------------------------------------------------------------|
| `sampaikilat_account`     | Staff, role, dan akun login                                |
| `sampaikilat_operational` | Resi, pelanggan, paket, transit, supir, posisi gudang      |

Skrip lengkap ada di `scriptDB/`, termasuk data seed untuk uji coba.

---

## Secure Programming: Apa Saja yang Diterapkan

Semua temuan dari security audit internal sudah ditangani. Ringkasan
praktik keamanan yang menarik di project ini:

### 1. SQL Injection — Fully Parameterized Query

Seluruh query database memakai *prepared statements* (`mysqli` +
`bind_param`), tidak ada string concatenasi input user ke SQL. Contoh:

```php
$stmt = $conn->prepare('SELECT username_staff, password_staff, id_role
                        FROM akun_staff WHERE username_staff = ? LIMIT 1');
$stmt->bind_param('s', $username);
```

### 2. Cross-Site Scripting (XSS) — Output Escaping

Setiap data yang keluar dari database dan dirender ke HTML di-escape
dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`, termasuk nomor resi,
nama pelanggan, alamat, dan username staff di dashboard.

### 3. Cross-Site Request Forgery (CSRF)

Form yang sensitif (login, create, update, delete, registrasi pelanggan)
memakai **synchronizer token pattern**:

- Token acak 32-byte (`random_bytes(32)`) disimpan di session.
- Dikirim sebagai hidden input `<input type="hidden" name="csrf_token">`.
- Diverifikasi server dengan `hash_equals()` (timing-safe comparison).
- Request tanpa/with invalid token ditolak dengan HTTP `419`.

### 4. Session Hardening

`controller/login/bootstrap.php`:

- Cookie `HttpOnly` + `Secure` (otomatis aktif saat HTTPS) + `SameSite=Lax`.
- **Session regeneration** setelah login sukses
  (`session_regenerate_id(true)`) — mencegah *session fixation*.
- Logout benar-benar menghancurkan session: kosongkan `$_SESSION`,
  hapus cookie, `session_destroy()`.

### 5. Access Control Berbasis Role (RBAC)

- Helper `require_login()` memaksa setiap halaman internal cek session;
  tanpa login langsung HTTP `401`.
- Role Admin (`RL-001`) vs Staff (`RL-002`) dicek dengan
  `hash_equals()`. Tombol Delete hanya dirender untuk Admin, dan
  `deleteData.php` tetap memvalidasi ulang role-nya di sisi server
  (bukan hanya menyembunyikan tombol).
- Halaman statis internal dipindah ke `.php` supaya tidak bisa diakses
  tanpa melewati pemeriksaan session.

### 6. HTTP Security Headers

Dikirim dari `bootstrap.php` di setiap request:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; ... frame-ancestors 'none'
```

CSP membatasi source script/style/image ke domain sendiri dan
melarang framing → mitigasi clickjacking, MIME-sniffing, dan
kebocoran referrer.

### 7. Input Validation & Defense in Depth

- Format nomor resi divalidasi dengan regex `^RS-[0-9]{7}$` di sisi
  server (bukan hanya atribut `pattern` HTML).
- Status update memakai **whitelist** lokasi gudang yang diizinkan —
  user tidak bisa mengirim nilai status bebas ke database.
- Nomor telepon dan tanggal divalidasi regex sebelum masuk query.
- Query nomor resi baru memakai transaksi + `SELECT ... FOR UPDATE`
  untuk menghindari race condition / duplikasi resi saat request
  bersamaan.
- Error database tidak pernah dibocorkan ke browser; detail dicatat ke
  `error_log()` dan user hanya melihat pesan generik + HTTP `503`.

### 8. Secrets Tidak Di-Hardcode

Kredensial database dibaca dari **environment variables**
(`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_ACCOUNT`, `DB_OPERATIONAL`),
bukan ditanam di source code. Folder `controller/` dan `scriptDB/`
tidak boleh di-expose langsung oleh web server.

### Keterbatasan yang Disengaja (Known Limitations)

Untuk transparansi akademik, project ini masih memiliki:

- **Password hashing legacy MD5 bertingkat**
  (`md5(md5(md5($pass) . 'SampaiKilat'))`) — mengikuti schema
  `CHAR(32)` yang sudah tersedia dari template tugas. Implementasi
  modern seharusnya `password_hash()` / `password_verify()` dengan
  kolom `VARCHAR(255)`. Fungsi `hash_equals()` tetap dipakai agar
  perbandingan hash *timing-safe*.
- Akun seed memakai password lemah (`password123`, dll) — hanya untuk
  demo, bukan production.
- Belum ada rate limiting pada endpoint login (hanya delay
  `usleep(250ms)` untuk memperlambat brute force).

---

## Requirement

- **PHP 8.0+** (memakai `declare(strict_types=1)`, arrow-compatible,
  `mixed` type hint di `e()`)
- **MySQL / MariaDB 8+** (memakai CHECK constraint + REGEXP)
- Web server: **Apache** (XAMPP/Laragon) atau **PHP built-in server**
- Browser modern

Tidak ada dependency Composer — murni PHP native + MySQL.

---

## Cara Menjalankan

### 1. Siapkan Database

Import kedua script SQL ke MySQL (urutan bebas karena database terpisah):

```bash
mysql -u root -p < scriptDB/SampaiKilat-AccountDatabase.sql
mysql -u root -p < scriptDB/SampaiKilat-OperationalDatabase.sql
```

### 2. Set Environment Variables

```bash
export DB_HOST="127.0.0.1"
export DB_USER="root"
export DB_PASS="password_anda"
export DB_ACCOUNT="sampaikilat_account"
export DB_OPERATIONAL="sampaikilat_operational"
```

> Kalau tidak diset, aplikasi memakai default lokal `127.0.0.1` /
> `root` / kosong — cocok untuk XAMPP default, tapi **wajib diset**
> untuk deployment sungguhan.

### 3. Jalankan Web Server

**Opsi A — XAMPP / Laragon:**
letakkan folder project di `htdocs/` (XAMPP) atau folder web root
Laragon, lalu start Apache + MySQL. Buka
`http://localhost/Sampai Kilat/homepage.html`.

**Opsi B — PHP built-in server (untuk uji cepat):**

```bash
cd "Sampai Kilat"
php -S localhost:8000
```

Lalu buka `http://localhost:8000/homepage.html`.

### 4. Login sebagai Staff

Buka halaman login dari navbar. Akun seed:

| Username       | Password      | Role  |
|----------------|---------------|-------|
| `andi.pratama` | `password123` | Admin |
| `budi.santoso` | `securepass`  | Staff |
| `citra.dewi`   | `mypassword`  | Staff |

### 5. Coba Alur Utama

1. Login → dashboard menampilkan tabel pengiriman.
2. Klik **Create** → isi 3 langkah → resi baru muncul di dashboard.
3. Klik **Update** → pilih resi → ubah status ke gudang berikutnya.
4. Logout → buka **Cek Resi** di homepage → masukkan resi tadi →
   status terlihat oleh publik.

---

## Struktur Project

```
Sampai Kilat/
|-- homepage.html                  # Landing page publik
|-- assets/                        # Gambar & video
|-- css/                           # Stylesheet per halaman
|-- src/
|   |-- cekresi/                   # Cek resi (publik)
|   |-- cektarif/ ceklokasi/       # Info tarif & lokasi
|   |-- login/                     # Halaman login staff
|   |-- homepageAS/                # Dashboard admin/staff
|   |-- createDelivery/            # Create pengiriman (3 langkah)
|   |-- updatingDelivery/          # Update status pengiriman
|   `-- mendaftarPelanggan/        # Registrasi pelanggan
|-- controller/login/
|   |-- bootstrap.php              # Session, CSRF, headers, helper auth
|   |-- koneksiDB.php              # Koneksi DB akun (env var)
|   |-- koneksiDB2.php             # Koneksi DB operasional (env var)
|   |-- logincontroller.php        # Proses login
|   |-- logout.php                 # Logout + destroy session
|   |-- createcontroller*.php      # Proses create pengiriman
|   |-- updateDelivery.php         # Proses update status
|   |-- deleteData.php             # Hapus transit (admin only)
|   `-- submitPelanggan.php        # Registrasi pelanggan
`-- scriptDB/                      # DDL + seed data MySQL
```

---

## Lisensi & Konteks

Project ini dibuat untuk keperluan akademik mata kuliah **Secure Programming**
di BINUS University. Kode bebas dipakai sebagai referensi belajar.
Nama brand "Sampai Kilat" fiktif, dipakai untuk keperluan tugas.

---

## Anggota Kelompok

Yang berpartisipasi dalam project ini:

| Nama | NIM |
|------|-----|
| Raymond Ivander | 2602059550 |
| Muhamad Salman Hakim | 2602076443 |
| Vutanto Hendy Wijaya | 2602063535 |
| Rafael Satriaprima Yudianto | 2602052153 |
| Darren Aditya | 2602076153 |
