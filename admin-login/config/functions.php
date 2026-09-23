<?php
require_once __DIR__ . "/app.php";

// -----------------------------------------------------
// Session dengan cookie yang lebih aman
// -----------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "httponly" => true,
        "samesite" => "Lax",
        "secure"   => !empty($_SERVER["HTTPS"]),
    ]);
    session_start();
}

// -----------------------------------------------------
// Helper umum
// -----------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

// Panjang string (aman walau ekstensi mbstring tidak aktif)
function str_len(string $value): int
{
    return function_exists("mb_strlen") ? mb_strlen($value, "UTF-8") : strlen($value);
}

function fmt_dt(?string $datetime): string
{
    return $datetime ? date("d/m/Y H:i", strtotime($datetime)) : "-";
}

function flash_from_query(): ?array
{
    $map = [
        "logout"        => ["success", "Anda berhasil logout."],
        "timeout"       => ["warning", "Sesi berakhir karena tidak ada aktivitas. Silakan login lagi."],
        "registered"    => ["success", "Akun superadmin berhasil dibuat. Silakan login."],
        "admin_added"   => ["success", "Admin baru berhasil ditambahkan."],
        "admin_deleted" => ["success", "Admin berhasil dihapus."],
        "pw_changed"    => ["success", "Password berhasil diubah."],
        "forbidden"     => ["error",   "Anda tidak punya akses ke halaman tersebut."],
    ];

    $key = $_GET["msg"] ?? "";
    return $map[$key] ?? null;
}

// -----------------------------------------------------
// CSRF
// -----------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST["csrf_token"], $_SESSION["csrf"])
        && hash_equals($_SESSION["csrf"], $_POST["csrf_token"]);
}

// -----------------------------------------------------
// Session & autentikasi
// -----------------------------------------------------
function destroy_session(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Dipanggil di setiap halaman yang butuh login.
 * - belum login            -> ke halaman login
 * - sesi idle kelamaan     -> logout otomatis
 * - akun sudah dihapus     -> logout otomatis
 */
function require_login(PDO $pdo): void
{
    if (empty($_SESSION["admin_id"])) {
        redirect("../auth/login.php");
    }

    if (isset($_SESSION["last_activity"])
        && (time() - $_SESSION["last_activity"]) > SESSION_TIMEOUT) {
        destroy_session();
        redirect("../auth/login.php?msg=timeout");
    }

    // Pastikan akun masih ada di database & ambil role terbaru
    $stmt = $pdo->prepare("SELECT id, name, role FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $_SESSION["admin_id"]]);
    $admin = $stmt->fetch();

    if (!$admin) {
        destroy_session();
        redirect("../auth/login.php");
    }

    $_SESSION["admin_name"]    = $admin["name"];
    $_SESSION["admin_role"]    = $admin["role"];
    $_SESSION["last_activity"] = time();
}

function admins_count(PDO $pdo): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
}

// -----------------------------------------------------
// Log aktivitas & pembatasan percobaan login
// -----------------------------------------------------
function log_activity(PDO $pdo, ?int $adminId, string $email, string $status): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO login_logs (admin_id, email, status, ip_address, user_agent)
         VALUES (:admin_id, :email, :status, :ip, :ua)"
    );
    $stmt->execute([
        "admin_id" => $adminId,
        "email"    => substr($email, 0, 100),
        "status"   => $status,
        "ip"       => $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0",
        "ua"       => substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 255),
    ]);
}

/**
 * Cek percobaan gagal untuk sebuah email.
 * Mengembalikan [jumlah_gagal, sisa_detik_terkunci].
 * Hitungan direset otomatis setelah login berhasil.
 */
function login_attempts(PDO $pdo, string $email): array
{
    $sql = "SELECT created_at
            FROM login_logs
            WHERE email = :email1
              AND status = 'failed'
              AND created_at >= NOW() - INTERVAL " . (int)LOCK_MINUTES . " MINUTE
              AND created_at > COALESCE(
                    (SELECT MAX(created_at) FROM login_logs
                     WHERE email = :email2 AND status = 'success'),
                    '1970-01-02')
            ORDER BY created_at DESC
            LIMIT " . (int)MAX_ATTEMPTS;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["email1" => $email, "email2" => $email]);
    $rows = $stmt->fetchAll();

    $count = count($rows);
    if ($count < MAX_ATTEMPTS) {
        return [$count, 0];
    }

    // Terkunci sampai percobaan gagal tertua di jendela waktu kedaluwarsa
    $oldest = strtotime($rows[$count - 1]["created_at"]);
    $left   = $oldest + (LOCK_MINUTES * 60) - time();

    return [$count, max($left, 1)];
}

// -----------------------------------------------------
// Validasi password (dipakai register & ganti password)
// -----------------------------------------------------
function password_error(string $password): ?string
{
    if (strlen($password) < 8
        || !preg_match("/[A-Za-z]/", $password)
        || !preg_match("/\d/", $password)) {
        return "Password minimal 8 karakter dan mengandung huruf serta angka.";
    }
    return null;
}
