<?php
include "db.php";
header("Content-Type: application/json; charset=utf-8");
try {
    $conn->query("SELECT 1");
    echo json_encode(["status" => "ok"]);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(["status" => "unavailable"]);
}
?>