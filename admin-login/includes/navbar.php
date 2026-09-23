<?php
// Variabel: $active = "dashboard" | "password"
$active = $active ?? "dashboard";
?>
<nav class="navbar">
    <div class="navbar-left">
        <h2>🔐 <?= e(APP_NAME) ?></h2>
        <div class="nav-links">
            <a href="index.php" class="<?= $active === "dashboard" ? "active" : "" ?>">Dashboard</a>
            <a href="password.php" class="<?= $active === "password" ? "active" : "" ?>">Ganti Password</a>
        </div>
    </div>

    <div class="navbar-right">
        <span class="nav-user">
            <?= e($_SESSION["admin_name"]) ?>
            <span class="badge badge-<?= e($_SESSION["admin_role"]) ?>"><?= e($_SESSION["admin_role"]) ?></span>
        </span>

        <form method="POST" action="../auth/logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</nav>
