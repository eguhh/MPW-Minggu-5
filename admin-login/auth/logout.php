<?php
require_once "../config/database.php";
require_once "../config/functions.php";

// Logout hanya diproses lewat POST + token CSRF
// (mencegah logout paksa lewat link/gambar dari situs lain)
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valid() || empty($_SESSION["admin_id"])) {
    redirect(empty($_SESSION["admin_id"]) ? "login.php" : "../dashboard/index.php");
}

// Catat aktivitas logout ke database
log_activity(
    $pdo,
    (int)$_SESSION["admin_id"],
    $_SESSION["admin_email"] ?? "",
    "logout"
);

// Kosongkan session, hapus cookie, lalu hancurkan session
destroy_session();

// Kembali ke halaman login
redirect("login.php?msg=logout");
