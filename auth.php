<?php
session_start();

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        $current = $_SERVER["REQUEST_URI"]; // the page they wanted
        header("Location: email-login.php?next=" . urlencode($current));
        exit;
    }
}