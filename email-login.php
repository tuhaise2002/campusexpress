<?php
session_start();
include "db.php";

$next = isset($_GET["next"]) ? $_GET["next"] : "index.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $next = $_POST["next"];

    $code = strval(random_int(100000, 999999));
    $expiresAt = (new DateTime("+10 minutes"))->format("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO email_logins (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $code, $expiresAt);
    $stmt->execute();

    $subject = "Your Campus Express login code";
    $message = "Your login code is: $code\n\nThis code expires in 10 minutes.\n\nIf you didn't request this, please ignore this email.";
    $headers = "From: Campus Express <no-reply@campus-express.test>";

    $sent = @mail($email, $subject, $message, $headers);

    $_SESSION["pending_email"] = $email;
    $_SESSION["next_url"] = $next;
    $_SESSION["debug_code"] = $sent ? "" : $code;

    header("Location: verify-code.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Express</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 120px auto 2rem;
            padding: 2rem;
            background: white;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(17,24,39,0.08);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .login-container p {
            color: #4b5563;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            font-size: 1.05rem;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            outline: none;
        }
        .form-group input:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 4px rgba(251,146,60,.25);
        }
        .login-btn {
            width: 100%;
            background: #c2410c;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .login-btn:hover {
            background: #9a3412;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #6b7280;
        }
        .back-link a {
            color: #c2410c;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login to Campus Express</h2>
        <p>Enter your email to receive a login code</p>

        <form method="POST">
            <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com">
            </div>
            <button type="submit" class="login-btn">Send Verification Code</button>
        </form>
        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
