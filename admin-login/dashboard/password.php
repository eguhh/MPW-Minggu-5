<?php
require_once "../config/database.php";
require_once "../config/functions.php";

require_login($pdo);

$adminId = (int)$_SESSION["admin_id"];
$errors  = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current = $_POST["current_password"] ?? "";
    $new     = $_POST["new_password"] ?? "";
    $confirm = $_POST["new_password_confirm"] ?? "";

    if (!csrf_valid()) {
        $errors[] = "Sesi form tidak valid. Muat ulang halaman lalu coba lagi.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $adminId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row["password"])) {
            $errors[] = "Password saat ini salah.";
        }

        if ($msg = password_error($new)) {
            $errors[] = $msg;
        }

        if ($new !== $confirm) {
            $errors[] = "Konfirmasi password baru tidak sama.";
        }

        if (!$errors && $current === $new) {
            $errors[] = "Password baru tidak boleh sama dengan password lama.";
        }
    }

    if (!$errors) {
        $upd = $pdo->prepare("UPDATE admins SET password = :p WHERE id = :id");
        $upd->execute([
            "p"  => password_hash($new, PASSWORD_DEFAULT),
            "id" => $adminId,
        ]);

        session_regenerate_id(true);
        redirect("index.php?msg=pw_changed");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <?php $active = "password"; include "../includes/navbar.php"; ?>

    <main class="dashboard-content narrow">
        <div class="dashboard-card">
            <h1>Ganti Password</h1>
            <p>Gunakan password baru yang kuat dan belum pernah dipakai.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error" style="margin-top:20px">
                    <?php foreach ($errors as $err): ?>
                        <div>&bull; <?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off" style="margin-top:24px">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <div class="password-wrapper">
                        <input type="password" id="current_password" name="current_password"
                               autocomplete="current-password" required>
                        <button type="button" class="toggle-password"
                                data-target="current_password"
                                aria-label="Tampilkan password">👁</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="new_password"
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
                    <label for="password_confirm">Konfirmasi Password Baru</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm" name="new_password_confirm"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-password"
                                data-target="password_confirm"
                                aria-label="Tampilkan password">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Simpan Password</button>
            </form>
        </div>
    </main>

    <?php include "../includes/footer.php"; ?>
</div>

<script src="../assets/js/login.js"></script>
</body>
</html>
