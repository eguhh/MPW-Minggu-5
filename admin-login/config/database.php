<?php
// Koneksi database (PDO) - sesuaikan jika kredensial MySQL berbeda
$host     = "localhost";
$dbname   = "db_admin";
$username = "root";
$password = "";          // Laragon: default kosong

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // prepared statement asli
        ]
    );

    // Samakan zona waktu database dengan PHP (WIB)
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
