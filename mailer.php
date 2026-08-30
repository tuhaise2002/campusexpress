<?php
function deliver_otp_email($email, $otp) {
    if (!is_production() && getenv("APP_SHOW_OTP") !== "0") return true;

    $apiKey = getenv("RESEND_API_KEY") ?: "";
    $from = getenv("MAIL_FROM") ?: "";
    if ($apiKey === "" || $from === "") {
        error_log("RESEND_API_KEY and MAIL_FROM are required for OTP delivery.");
        return false;
    }

    $payload = json_encode([
        "from" => $from,
        "to" => [$email],
        "subject" => "Your CampusExpress login code",
        "html" => '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px"><h2>CampusExpress login</h2><p>Your one-time login code is:</p><p style="font-size:32px;font-weight:800;letter-spacing:8px">' . e($otp) . '</p><p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p></div>',
        "text" => "Your CampusExpress login code is {$otp}. It expires in 10 minutes."
    ], JSON_UNESCAPED_SLASHES);

    $ch = curl_init("https://api.resend.com/emails");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $apiKey, "Content-Type: application/json", "Idempotency-Key: otp-" . bin2hex(random_bytes(16))],
        CURLOPT_POSTFIELDS => $payload
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $status < 200 || $status >= 300) {
        error_log("OTP email delivery failed: HTTP {$status} {$error}");
        return false;
    }
    return true;
}
?>