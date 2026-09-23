<?php
require_once "../config/database.php";
require_once "../config/functions.php";

/*
 * Aturan akses halaman ini:
 * - Jika tabel admins masih kosong  -> siapa pun boleh membuat akun pertama (superadmin)
 * - Jika sudah ada admin            -> hanya superadmin yang sudah login
 * Halaman ini menggantikan file sementara create_admin.php pada modul.
 */
$isFirst = admins_count($pdo) === 0;

if (!$isFirst) {
    require_login($pdo);

    if ($_SESSION["admin_role"] !== "superadmin") {
        redirect("../dashboard/index.php?msg=forbidden");
    }
}

$errors = [];
$name   = "";
$email  = "";
$role   = "admin";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["password_confirm"] ?? "";
    $role     = $isFirst ? "superadmin" : ($_POST["role"] ?? "admin");

    if (!in_array($role, ["admin", "superadmin"], true)) {
        $role = "admin";
    }

    if (!csrf_valid()) {
        $errors[] = "Sesi form tidak valid. Muat ulang halaman lalu coba lagi.";
    } else {
        if (str_len($name) < 3 || str_len($name) > 100) {
            $errors[] = "Nama harus 3 - 100 karakter.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $errors[] = "Format email tidak valid.";
        }

        if ($msg = password_error($password)) {
            $errors[] = $msg;
        }

        if ($password !== $confirm) {
            $errors[] = "Konfirmasi password tidak sama.";
        }

        if (!$errors) {
            $cek = $pdo->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
            $cek->execute(["email" => $email]);

            if ($cek->fetch()) {
                $errors[] = "Email sudah terdaftar.";
            }
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO admins (name, email, password, role)
                 VALUES (:name, :email, :password, :role)"
            );
            $stmt->execute([
                "name"     => $name,
                "email"    => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role"     => $role,
            ]);

            redirect($isFirst
                ? "login.php?msg=registered"
                : "../dashboard/index.php?msg=admin_added");
        } catch (PDOException $ex) {
            $errors[] = ($ex->getCode() === "23000")
                ? "Email sudah terdaftar."
                : "Terjadi kesalahan pada database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isFirst ? "Buat Akun Pertama" : "Tambah Admin" ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">

<div class="login-container">
    <div class="login-card">

        <div class="login-header">
            <div class="admin-icon">🛡️</div>
            <h1><?= $isFirst ? "Buat Akun Pertama" : "Tambah Admin Baru" ?></h1>
            <p>
                <?= $isFirst
                    ? "Akun ini otomatis menjadi superadmin"
                    : "Password akan disimpan dalam bentuk hash" ?>
            </p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div>&bull; <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name"
                       placeholder="Masukkan nama"
                       value="<?= e($name) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="Masukkan email"
                       value="<?= e($email) ?>" required>
            </div>

            <?php if (!$isFirst): ?>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="admin" <?= $role === "admin" ? "selected" : "" ?>>Admin</option>
                        <option value="superadmin" <?= $role === "superadmin" ? "selected" : "" ?>>Superadmin</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="Minimal 8 karakter, huruf + angka"
                           autocomplete="new-password" required>
                    <button type="button" class="toggle-password"
                            data-target="password"
                            aria-label="Tampilkan password">👁</button>
                </div>
                <div class="strength" id="strength" hidden>
                    <div class="strength-bar"><span id="strengthFill"></span></div>
                    <small id="strengthText"></small>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm"
                           placeholder="Ulangi password"
                           autocomplete="new-password" required>
                    <button type="button" class="toggle-password"
                            data-target="password_confirm"
                            aria-label="Tampilkan password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <?= $isFirst ? "Buat Akun" : "Simpan Admin" ?>
            </button>
        </form>

        <p class="auth-link">
            <?php if ($isFirst): ?>
                Sudah punya akun? <a href="login.php">Kembali ke login</a>
            <?php else: ?>
                <a href="../dashboard/index.php">&larr; Kembali ke dashboard</a>
            <?php endif; ?>
        </p>

    </div>

    <p class="auth-footer">
        <?= e(DEV_NAME) ?> &middot; <?= e(DEV_NIM) ?> &middot; <?= e(DEV_KELAS) ?>
    </p>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
