# Admin Login & Logout (Modifikasi Minggu 5)

Workshop Pemrograman Web - Bisnis Digital, Politeknik Negeri Jember
PHP (PDO) + MySQL/MariaDB + PHP Session

## Cara menjalankan (Laragon)

1. Ekstrak folder `admin-login` ke `C:\laragon\www\`
2. Start **Apache** & **MySQL** di Laragon
3. Import `database.sql` (phpMyAdmin / HeidiSQL) -> otomatis membuat database `db_admin` + 2 tabel
4. Kalau user MySQL kamu bukan `root` tanpa password, ubah di `config/database.php`
5. Buka `http://localhost/admin-login/`
6. Karena belum ada admin, klik **"Buat akun superadmin pertama"** lalu isi form
   (menggantikan file sementara `create_admin.php` di modul)
7. Login memakai akun tadi

## Struktur project

```
admin-login/
├── config/
│   ├── app.php          <- nama app, identitas, batas percobaan login, timeout sesi
│   ├── database.php     <- koneksi PDO
│   └── functions.php    <- helper: session, CSRF, log, cek lock, validasi
├── auth/
│   ├── login.php
│   ├── register.php     <- buat akun pertama / tambah admin
│   └── logout.php
├── dashboard/
│   ├── index.php        <- statistik, riwayat login, kelola admin
│   └── password.php     <- ganti password
├── includes/            <- navbar.php, footer.php
├── assets/css/style.css
├── assets/js/login.js
├── database.sql
└── index.php
```

## Database

| Tabel        | Fungsi                                                                 |
|--------------|------------------------------------------------------------------------|
| `admins`     | akun admin: name, email, password (hash), **role**, **last_login**     |
| `login_logs` | **riwayat** login berhasil/gagal/diblokir/logout + IP + user agent      |

## Modifikasi dari modul

**Fitur baru (memakai database)**
- Halaman `register.php`: pembuatan akun lewat form, menggantikan `create_admin.php`
- Role `superadmin` dan `admin` (kolom `role`)
- Tabel `login_logs`: semua aktivitas login/logout tercatat di database
- Batas percobaan login: 5x gagal -> dikunci 5 menit (dihitung dari `login_logs`, direset saat berhasil login)
- Kolom `last_login`: dashboard menampilkan "Login sebelumnya"
- Dashboard: statistik (total admin, login berhasil/gagal hari ini), tabel aktivitas terbaru,
  dan **Kelola Admin** (tambah & hapus, khusus superadmin)
- Halaman ganti password (verifikasi password lama, hash baru)

**Keamanan tambahan**
- Token CSRF di semua form (login, register, logout, hapus admin, ganti password)
- Logout lewat POST + CSRF (bukan link biasa)
- Sesi otomatis berakhir setelah 15 menit tidak aktif
- Cookie session `HttpOnly` + `SameSite=Lax`
- Sesi dicek ke database di setiap request (akun yang dihapus otomatis ter-logout)
- Prepared statement asli (`ATTR_EMULATE_PREPARES = false`)
- Validasi password: min. 8 karakter, huruf + angka; `password_needs_rehash()` saat login

**Tampilan**
- Tema baru (indigo/ungu), navbar dengan menu, kartu statistik, tabel, badge status
- Indikator kekuatan password + cek konfirmasi password langsung (JavaScript)
- Konfirmasi sebelum menghapus admin
- Identitas pembuat di footer (ubah di `config/app.php`)

## Yang dipertahankan dari modul
`password_hash()` / `password_verify()`, PDO + prepared statement, `session_regenerate_id(true)`,
proteksi dashboard lewat `$_SESSION['admin_id']`, `htmlspecialchars()` (dibungkus fungsi `e()`),
toggle show/hide password, pembersihan session + cookie + `session_destroy()` saat logout.
