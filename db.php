<?php
$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "campusexpress";

function app_env() { return strtolower(getenv("APP_ENV") ?: "local"); }
function is_production() { return app_env() === "production"; }
function app_key() {
    $key = getenv("APP_KEY") ?: "local-development-key-change-me";
    if (is_production() && strlen($key) < 32) {
        error_log("APP_KEY must contain at least 32 characters in production.");
        http_response_code(500);
        exit("The service is not configured correctly.");
    }
    return $key;
}
function request_is_https() {
    return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
}

if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    if (is_production() && request_is_https()) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+00:00'");
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit("The service is temporarily unavailable.");
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name("campusexpress_session");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "httponly" => true,
        "secure" => request_is_https(),
        "samesite" => "Lax"
    ]);
    session_start();
}

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); }
function csrf_token() {
    if (empty($_SESSION["csrf_token"])) $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf_token"];
}
function csrf_field() { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_post() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); header("Allow: POST"); exit("Method not allowed."); }
    if (!hash_equals(csrf_token(), (string)($_POST["csrf_token"] ?? ""))) { http_response_code(403); exit("Invalid or expired form. Refresh and try again."); }
}
function client_identifier() {
    $ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";
    return hash_hmac("sha256", $ip, app_key());
}
function auth_is_limited($action, $maxAttempts = 5, $windowMinutes = 15) {
    global $conn;
    $identifier = client_identifier();
    $stmt = $conn->prepare("SELECT COUNT(*) AS attempts FROM auth_attempts WHERE identifier_hash = ? AND action_name = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)");
    $stmt->bind_param("ssi", $identifier, $action, $windowMinutes);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()["attempts"] >= $maxAttempts;
}
function record_auth_failure($action) {
    global $conn;
    $identifier = client_identifier();
    $stmt = $conn->prepare("INSERT INTO auth_attempts (identifier_hash, action_name) VALUES (?, ?)");
    $stmt->bind_param("ss", $identifier, $action);
    $stmt->execute();
}
function clear_auth_failures($action) {
    global $conn;
    $identifier = client_identifier();
    $stmt = $conn->prepare("DELETE FROM auth_attempts WHERE identifier_hash = ? AND action_name = ?");
    $stmt->bind_param("ss", $identifier, $action);
    $stmt->execute();
}
function otp_hash($otp) { return hash_hmac("sha256", (string)$otp, app_key()); }
function remove_uploaded_image($path) {
    if (!preg_match('#^uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $path)) return;
    $full = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($full)) unlink($full);
}
function store_uploaded_image($file, $required = true) {
    $error = (int)($file["error"] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE && !$required) return null;
    if ($error !== UPLOAD_ERR_OK) throw new RuntimeException("The image upload failed.");
    if ((int)$file["size"] > 5 * 1024 * 1024) throw new RuntimeException("The image must be 5 MB or smaller.");
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
    $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    if (!isset($extensions[$mime])) throw new RuntimeException("Only JPG, PNG, and WEBP images are allowed.");
    $directory = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException("Upload directory unavailable.");
    $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
    if (!move_uploaded_file($file["tmp_name"], $directory . DIRECTORY_SEPARATOR . $filename)) throw new RuntimeException("The image could not be saved.");
    return "uploads/" . $filename;
}
?>