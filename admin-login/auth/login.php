<?php
require_once "../config/database.php";
require_once "../config/functions.php";

// Jika sudah login
if (!empty($_SESSION["admin_id"])) {
    redirect("../dashboard/index.php");
}

$error    = "";
$email    = "";
$flash    = flash_from_query();
$hasAdmin = admins_count($pdo) > 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!csrf_valid()) {
        $error = "Sesi form tidak valid. Muat ulang halaman lalu coba lagi.";
    } elseif ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi.";
    } else {
        [$failed, $lockLeft] = login_attempts($pdo, $email);

        if ($lockLeft > 0) {
            // Akun sedang dikunci sementara
            log_activity($pdo, null, $email, "locked");
            $error = "Terlalu banyak percobaan gagal. Coba lagi dalam "
                   . ceil($lockLeft / 60) . " menit.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute(["email" => $email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin["password"])) {
                // Perbarui hash jika algoritma default PHP berubah
                if (password_needs_rehash($admin["password"], PASSWORD_DEFAULT)) {
                    $upd = $pdo->prepare("UPDATE admins SET password = :p WHERE id = :id");
                    $upd->execute([
                        "p"  => password_hash($password, PASSWORD_DEFAULT),
                        "id" => $admin["id"],
                    ]);
                }

                // Regenerate session ID untuk keamanan (anti session fixation)
                session_regenerate_id(true);

                $_SESSION["admin_id"]      = $admin["id"];
                $_SESSION["admin_name"]    = $admin["name"];
                $_SESSION["admin_email"]   = $admin["email"];
                $_SESSION["admin_role"]    = $admin["role"];
                $_SESSION["prev_login"]    = $admin["last_login"];
                $_SESSION["last_activity"] = time();
                unset($_SESSION["csrf"]);

                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id")
                    ->execute(["id" => $admin["id"]]);

                log_activity($pdo, (int)$admin["id"], $email, "success");

                redirect("../dashboard/index.php");
            }

            // Gagal
            log_activity($pdo, $admin ? (int)$admin["id"] : null, $email, "failed");
            [$failed, $lockLeft] = login_attempts($pdo, $email);

            if ($lockLeft > 0) {
                $error = "Terlalu banyak percobaan gagal. Akun dikunci selama "
                       . LOCK_MINUTES . " menit.";
            } else {
                $error = "Email atau password salah. Sisa percobaan: "
                       . (MAX_ATTEMPTS - $failed) . ".";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">

<div class="login-container">
    <div class="login-card">

        <div class="login-header">
            <div class="admin-icon">🔐</div>
            <h1><?= e(APP_NAME) ?></h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="on">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="Masukkan email"
                       value="<?= e($email) ?>"
                       autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan password"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-password"
                            data-target="password"
                            aria-label="Tampilkan password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <?php if (!$hasAdmin): ?>
            <p class="auth-link">
                Belum ada akun admin?
                <a href="register.php">Buat akun superadmin pertama</a>
            </p>
        <?php endif; ?>

    </div>

    <p class="auth-footer">
        <?= e(DEV_NAME) ?> &middot; <?= e(DEV_NIM) ?> &middot; <?= e(DEV_KELAS) ?>
    </p>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
