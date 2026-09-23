<?php
require_once "../config/database.php";
require_once "../config/functions.php";

// Cek apakah admin sudah login
require_login($pdo);

$adminId    = (int)$_SESSION["admin_id"];
$adminName  = $_SESSION["admin_name"];
$adminEmail = $_SESSION["admin_email"];
$adminRole  = $_SESSION["admin_role"];
$isSuper    = $adminRole === "superadmin";
$flash      = flash_from_query();
$error      = "";

// -----------------------------------------------------
// Hapus admin (khusus superadmin, lewat POST + CSRF)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_admin") {
    $targetId = (int)($_POST["id"] ?? 0);

    if (!$isSuper) {
        redirect("index.php?msg=forbidden");
    } elseif (!csrf_valid()) {
        $error = "Sesi form tidak valid. Muat ulang halaman lalu coba lagi.";
    } elseif ($targetId === $adminId) {
        $error = "Anda tidak bisa menghapus akun yang sedang dipakai.";
    } else {
        $del = $pdo->prepare("DELETE FROM admins WHERE id = :id");
        $del->execute(["id" => $targetId]);
        redirect("index.php?msg=admin_deleted");
    }
}

// -----------------------------------------------------
// Data untuk dashboard
// Superadmin melihat semua data, admin biasa hanya miliknya
// -----------------------------------------------------
$where  = $isSuper ? "1=1" : "l.admin_id = :aid";
$params = $isSuper ? [] : ["aid" => $adminId];

$totalAdmin = admins_count($pdo);

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(l.status = 'success' AND DATE(l.created_at) = CURDATE()), 0) AS ok_today,
        COALESCE(SUM(l.status = 'failed'  AND DATE(l.created_at) = CURDATE()), 0) AS fail_today
     FROM login_logs l
     WHERE $where"
);
$stmt->execute($params);
$stat = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT l.*, a.name AS admin_name
     FROM login_logs l
     LEFT JOIN admins a ON a.id = l.admin_id
     WHERE $where
     ORDER BY l.id DESC
     LIMIT 10"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$admins = [];
if ($isSuper) {
    $admins = $pdo->query(
        "SELECT id, name, email, role, last_login, created_at FROM admins ORDER BY id"
    )->fetchAll();
}

$statusLabel = [
    "success" => "Berhasil",
    "failed"  => "Gagal",
    "locked"  => "Diblokir",
    "logout"  => "Logout",
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <?php $active = "dashboard"; include "../includes/navbar.php"; ?>

    <main class="dashboard-content">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- SELAMAT DATANG -->
        <div class="dashboard-card">
            <h1>Selamat Datang, <?= e($adminName) ?>!</h1>
            <p>Anda berhasil login ke dalam sistem administrator.</p>
            <div class="info-list">
                <p><strong>Email:</strong> <?= e($adminEmail) ?></p>
                <p><strong>Role:</strong> <?= e(ucfirst($adminRole)) ?></p>
                <p><strong>Login sebelumnya:</strong> <?= e(fmt_dt($_SESSION["prev_login"] ?? null)) ?></p>
            </div>
        </div>

        <!-- STATISTIK -->
        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-label">Total Admin</span>
                <span class="stat-value"><?= $totalAdmin ?></span>
            </div>
            <div class="stat-card stat-ok">
                <span class="stat-label">Login Berhasil Hari Ini</span>
                <span class="stat-value"><?= (int)$stat["ok_today"] ?></span>
            </div>
            <div class="stat-card stat-fail">
                <span class="stat-label">Login Gagal Hari Ini</span>
                <span class="stat-value"><?= (int)$stat["fail_today"] ?></span>
            </div>
        </div>

        <!-- RIWAYAT LOGIN -->
        <div class="dashboard-card">
            <h2 class="card-title">
                Aktivitas Login Terbaru
                <small><?= $isSuper ? "(semua admin)" : "(akun Anda)" ?></small>
            </h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$logs): ?>
                        <tr><td colspan="4" class="empty">Belum ada aktivitas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e(fmt_dt($log["created_at"])) ?></td>
                            <td><?= e($log["email"]) ?></td>
                            <td>
                                <span class="badge badge-<?= e($log["status"]) ?>">
                                    <?= e($statusLabel[$log["status"]] ?? $log["status"]) ?>
                                </span>
                            </td>
                            <td><?= e($log["ip_address"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KELOLA ADMIN (superadmin) -->
        <?php if ($isSuper): ?>
        <div class="dashboard-card">
            <h2 class="card-title">
                Kelola Admin
                <a href="../auth/register.php" class="btn-small">+ Tambah Admin</a>
            </h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Login Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><?= e($a["name"]) ?></td>
                            <td><?= e($a["email"]) ?></td>
                            <td><span class="badge badge-<?= e($a["role"]) ?>"><?= e($a["role"]) ?></span></td>
                            <td><?= e(fmt_dt($a["last_login"])) ?></td>
                            <td>
                                <?php if ((int)$a["id"] === $adminId): ?>
                                    <span class="muted">Akun Anda</span>
                                <?php else: ?>
                                    <form method="POST" action="" data-confirm="Hapus admin <?= e($a["name"]) ?>?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="id" value="<?= (int)$a["id"] ?>">
                                        <button type="submit" class="btn-danger-small">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <?php include "../includes/footer.php"; ?>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
