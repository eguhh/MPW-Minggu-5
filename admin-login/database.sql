-- =====================================================
-- Database : db_admin
-- Tugas    : Workshop Pemrograman Web - Sistem Log-in & Log-out
-- Cara pakai: import file ini lewat phpMyAdmin / HeidiSQL (Laragon)
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_admin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_admin;

-- -----------------------------------------------------
-- Tabel admins (akun yang boleh login)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,                 -- hash bcrypt, bukan teks asli
    role       ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Tabel login_logs (riwayat aktivitas login/logout)
-- Dipakai juga untuk pembatasan percobaan login gagal
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS login_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT NULL,
    email      VARCHAR(100) NOT NULL,
    status     ENUM('success','failed','locked','logout') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_status_time (email, status, created_at),
    CONSTRAINT fk_logs_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE SET NULL
);
