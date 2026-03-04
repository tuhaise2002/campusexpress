<?php
include "auth.php";
require_login();

// after login, continue
$vendor_phone = "256752268730"; // example (no +)
$message = "Hello, I want to order food.";
$wa = "https://wa.me/$vendor_phone?text=" . urlencode($message);

header("Location: $wa");
exit;