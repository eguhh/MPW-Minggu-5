<?php
// Pengaturan umum aplikasi
date_default_timezone_set("Asia/Jakarta");

define("APP_NAME", "Admin Portal");

// Identitas pembuat (tampil di footer)
define("DEV_NAME",  "Teguh Angen Nugraha");
define("DEV_NIM",   "I43251017");
define("DEV_KELAS", "Bisnis Digital - Golongan B");

// Keamanan login
define("MAX_ATTEMPTS",   5);    // maksimal percobaan gagal
define("LOCK_MINUTES",   5);    // lama akun dikunci (menit)
define("SESSION_TIMEOUT", 900); // sesi habis jika tidak aktif (detik) = 15 menit
