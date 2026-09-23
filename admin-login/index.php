<?php
require_once "config/functions.php";

// Sudah login -> dashboard, belum -> halaman login
if (!empty($_SESSION["admin_id"])) {
    redirect("dashboard/index.php");
}

redirect("auth/login.php");
