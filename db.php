<?php
$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$database = getenv("DB_NAME") ?: "campus-express";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>