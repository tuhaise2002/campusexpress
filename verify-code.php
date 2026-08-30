<?php
session_start();
include "db.php";

// Check if there's a pending email login
if (!isset($_SESSION["pending_email"])) {
    header("Location: email-login.php");
    exit;
}

$email = $_SESSION["pending_email"];
$next = $_SESSION["next_url"] ?? "index.php";
$debug_code = $_SESSION["debug_code"] ?? "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted_code = trim($_POST["code"]);

    // Verify code
    $stmt = $conn->prepare("SELECT id FROM email_logins WHERE email = ? AND code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $submitted_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Code is valid - create or get user
        $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_user->bind_param("s", $email);
        $check_user->execute();
        $user_result = $check_user->get_result();

        if ($user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();
            $user_id = $user["id"];
            // Update last login
            $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = $user_id")->execute();
        } else {
            // Create new user
            $new_user = $conn->prepare("INSERT INTO users (email) VALUES (?)");
            $new_user->bind_param("s", $email);
            $new_user->execute();
            $user_id = $new_user->insert_id;
        }

        // Set session
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_email"] = $email;

        // Clean up used code
        $conn->prepare("DELETE FROM email_logins WHERE email = ?")->execute([$email]);

        // Clear session vars
        unset($_SESSION["pending_email"], $_SESSION["next_url"], $_SESSION["debug_code"]);

        // Redirect to intended page
        header("Location: " . $next);
        exit;
    } else {
        $error = "Invalid or expired code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - Campus Express</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .verify-container {
            max-width: 400px;
            margin: 120px auto 2rem;
            padding: 2rem;
            background: white;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(17,24,39,0.08);
        }
        .verify-container h2 {
            text-align: center;
            margin-bottom: 1rem;
        }
        .verify-container p {
            color: #4b5563;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .verify-container .email {
            font-weight: 700;
            color: #c2410c;
        }
        .code-input {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .code-input:focus {
            border-color: #fb923c;
            outline: none;
        }
        .verify-btn {
            width: 100%;
            background: #c2410c;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .verify-btn:hover {
            background: #9a3412;
        }
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .debug-info {
            background: #fefce8;
            color: #854d0e;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
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
    <div class="verify-container">
        <h2>Enter Verification Code</h2>
        <p>We sent a 6-digit code to <span class="email"><?php echo htmlspecialchars($email); ?></span></p>

        <?php if (!empty($debug_code)): ?>
            <div class="debug-info">
                <strong>Test Mode:</strong> Your code is: <?php echo $debug_code; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="code" class="code-input" maxlength="6" pattern="[0-9]{6}" required 
                   placeholder="000000" autocomplete="one-time-code" inputmode="numeric">
            <button type="submit" class="verify-btn">Verify & Login</button>
        </form>
        <a href="email-login.php" class="back-link">← Use a different email</a>
    </div>
</body>
</html>
