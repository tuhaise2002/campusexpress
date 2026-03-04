<?php
session_start();
include "db.php";

// where to go after login
$next = isset($_GET["next"]) ? $_GET["next"] : "index.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $next = $_POST["next"];

    // create 6-digit code
    $code = strval(random_int(100000, 999999));

    // expires in 10 minutes
    $expiresAt = (new DateTime("+10 minutes"))->format("Y-m-d H:i:s");

    // store code
    $stmt = $conn->prepare("INSERT INTO email_logins (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $code, $expiresAt);
    $stmt->execute();

    // ✅ SEND EMAIL (simple version)
    $subject = "Your login code";
    $message = "Your login code is: $code (expires in 10 minutes)";
    $headers = "From: no-reply@campus-express.test";

    // mail() may fail on XAMPP without SMTP setup.
    // If it fails, we’ll show the code on-screen for testing.
    $sent = @mail($email, $subject, $message, $headers);

    $_SESSION["pending_email"] = $email;
    $_SESSION["next_url"] = $next;
    $_SESSION["debug_code"] = $sent ? "" : $code; // show code if email not sending

    header("Location: verify-code.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Email Login</title></head>
<body>
<h2>Login with Email</h2>

<form method="POST">
  <label>Email:</label><br>
  <input type="email" name="email" required><br><br>
  <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
  <button type="submit">Send Code</button>
</form>

</body>
</html>