# Sistem Manajemen Atlet (PHP + MySQL)

Aplikasi web manajemen atlet: multi-role (Admin, Pelatih, Atlet), CRUD atlet/prestasi/tes kebugaran,
grafik Chart.js, laporan, upload foto. Tanpa framework. PHP 8.1+, PDO, Bootstrap 5.

## Instalasi (XAMPP / Windows)
1. Install XAMPP (PHP 8.1 atau lebih baru). Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder `athlete-management` ke `C:/xampp/htdocs/athlete-management/`.
3. Buka `http://localhost/phpmyadmin` > tab **Import** > pilih `database/athlete_management.sql` > **Go**.
   (File itu sudah membuat database `athlete_management` beserta tabel dan data awal.)
4. Cek `config/database.php` (default: host `localhost`, user `root`, password kosong).
5. Jika nama folder bukan `athlete-management`, ubah `BASE_URL` di `config/app.php`.
6. Buka `http://localhost/athlete-management/`.
7. Perlu internet saat dipakai (Bootstrap, Bootstrap Icons, Chart.js dimuat dari CDN jsDelivr).

## Akun awal (segera ganti password)
| Role | Email | Password |
|---|---|---|
| Admin | admin@athlete.local | Admin#2026 |
| Pelatih | pelatih@athlete.local | Pelatih#2026 |
| Atlet | atlet1@athlete.local (s.d. atlet3) | Atlet#2026 |

## Alur menambah pelatih dan atlet baru
1. Login Admin > **Pengguna** > Tambah > role Pelatih.
2. **Data Atlet** > Tambah Atlet (isi data + foto).
3. **Pengguna** > Tambah > role Atlet > pilih data atlet di "Hubungkan ke data atlet".
   (Atau atlet daftar sendiri di halaman Register, lalu Admin menghubungkan akunnya lewat Pengguna > Edit.)

## Peta file
| File | Fungsi |
|---|---|
| `config/app.php` | BASE_URL, path upload, timeout sesi, mode debug |
| `config/database.php` | Koneksi PDO (`db()`) |
| `middleware/auth.php` | Session aman, CSRF, flash, helper, upload foto, paginasi, `requireLogin()` |
| `middleware/role.php` | RBAC: `requireRole()`, `requireAdmin()`, `requireCoach()`, `requireAthlete()`, `requireStaff()` |
| `auth/login.php, register.php, logout.php` | Autentikasi |
| `components/*` | header, sidebar (menu per role), navbar, footer, modal hapus, kartu profil, grafik |
| `modules/*` | Logika & tampilan bersama Admin/Pelatih (atlet, prestasi, tes, pengguna, laporan, dashboard) |
| `admin/*`, `coach/*` | Halaman tipis: cek role lalu memuat modul. Pelatih tidak punya `athletes/delete.php` dan `users/` |
| `admin/settings.php` | Pengaturan sistem + daftar cabang (khusus Admin) |
| `athlete/*` | Halaman atlet; selalu memakai `athlete_id` dari SESSION, bukan dari URL |
| `uploads/athletes/` | Foto atlet (nama acak, eksekusi script diblokir) |

Ingin mengubah tampilan? Edit `components/` dan `assets/css/style.css`.
Ingin mengubah logika Admin dan Pelatih sekaligus? Edit file di `modules/`.

## Catatan fitur
- **Export PDF**: tombol PDF membuka dialog cetak browser, pilih "Simpan sebagai PDF".
- **Export Excel**: file `.xls` (tabel HTML). Excel bisa menampilkan peringatan format; pilih **Yes/Open**.
- **Hak hapus**: hapus atlet = Admin saja. Hapus prestasi dan tes = Admin dan Pelatih.
- Menghapus atlet ikut menghapus prestasi dan tesnya (CASCADE); akun terkait tidak dihapus, hanya terputus.

## Checklist pengujian keamanan dan role (Tahap 7-8)
**Admin**: bisa membuka semua menu; tambah/ubah/hapus pengguna; ubah role; hapus atlet; buka Pengaturan.
**Pelatih**: login lalu buka `/admin/dashboard.php` > harus diarahkan ke halaman 403.
 `/coach/athletes/delete.php` tidak ada; tombol hapus atlet tidak tampil; tidak ada menu Pengguna.
**Atlet**: login atlet1 > buka `/admin/...` atau `/coach/...` > 403. Halaman atlet tidak menerima `?id=`; ubah `?id=2` di URL tidak berpengaruh.
**Lainnya**:
- SQL Injection: isi `' OR 1=1 --` di kolom email/pencarian > tidak ada efek (prepared statement).
- XSS: simpan nama `<script>alert(1)</script>` > tampil sebagai teks (semua output lewat `e()`).
- CSRF: kirim form hapus dari halaman lain tanpa token > ditolak (HTTP 419).
- Upload: unggah `.php` yang diganti nama `.jpg` > ditolak (cek MIME + isi gambar).
- Session: nonaktifkan akun di Pengguna > akun itu langsung terlempar pada request berikutnya.
- Brute force: 5x password salah > login dikunci 10 menit (berbasis session).

## Pemecahan masalah
- **Halaman putih**: set `APP_DEBUG` jadi `true` di `config/app.php` (hanya lokal), atau lihat `C:/xampp/php/logs/php_error_log`.
- **CSS/menu tidak muncul**: `BASE_URL` salah, atau tidak ada internet untuk CDN.
- **Error 500 dari .htaccess**: pastikan `AllowOverride All` di Apache (default XAMPP), atau hapus baris `Options -Indexes`.
- **Upload gagal**: cek `upload_max_filesize` di `php.ini` dan ekstensi `fileinfo` aktif (default XAMPP aktif).
