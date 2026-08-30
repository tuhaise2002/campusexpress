<?php
include "db.php";
if (!isset($_SESSION["vendor_id"])) { header("Location: login.php"); exit; }
verify_post();
$vendorId = (int)$_SESSION["vendor_id"];
$name = trim($_POST["food_name"] ?? "");
$price = trim($_POST["price"] ?? "");
$category = trim($_POST["category"] ?? "");
$description = trim($_POST["description"] ?? "");
$status = $_POST["status"] ?? "";
$categories = ["Food", "Drinks", "Snacks", "Groceries", "Stationery"];
if ($name === "" || strlen($name) > 100) exit("Enter a valid item name.");
if (!is_numeric($price) || (float)$price <= 0) exit("Enter a valid numeric price.");
if (!in_array($category, $categories, true)) exit("Invalid category.");
if ($description === "" || strlen($description) > 500) exit("Enter a description of up to 500 characters.");
if (!in_array($status, ["Available", "Sold Out"], true)) exit("Invalid status.");
try { $image = store_uploaded_image($_FILES["image"] ?? [], true); } catch (RuntimeException $e) { exit(e($e->getMessage())); }
$stmt = $conn->prepare("INSERT INTO menu_items (vendor_id, food_name, price, category, image, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isdssss", $vendorId, $name, $price, $category, $image, $description, $status);
$stmt->execute();
header("Location: dashboard.php");
exit;
?>
