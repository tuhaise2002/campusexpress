<?php
include "db.php";
if (!isset($_SESSION["vendor_id"])) { header("Location: login.php"); exit; }
verify_post();
$id = (int)($_POST["id"] ?? 0); $vendorId = (int)$_SESSION["vendor_id"];
$name = trim($_POST["food_name"] ?? ""); $price = trim($_POST["price"] ?? "");
$category = trim($_POST["category"] ?? ""); $description = trim($_POST["description"] ?? ""); $status = $_POST["status"] ?? "";
$categories = ["Food", "Drinks", "Snacks", "Groceries", "Stationery"];
if ($id < 1 || $name === "" || strlen($name) > 100) exit("Invalid item.");
if (!is_numeric($price) || (float)$price <= 0) exit("Enter a valid numeric price.");
if (!in_array($category, $categories, true) || !in_array($status, ["Available", "Sold Out"], true)) exit("Invalid category or status.");
if ($description === "" || strlen($description) > 500) exit("Enter a description of up to 500 characters.");
$find = $conn->prepare("SELECT image FROM menu_items WHERE id = ? AND vendor_id = ?"); $find->bind_param("ii", $id, $vendorId); $find->execute(); $item = $find->get_result()->fetch_assoc();
if (!$item) { http_response_code(404); exit("Item not found."); }
try { $newImage = store_uploaded_image($_FILES["image"] ?? [], false); } catch (RuntimeException $e) { exit(e($e->getMessage())); }
$image = $newImage ?: $item["image"];
$stmt = $conn->prepare("UPDATE menu_items SET food_name=?, price=?, category=?, image=?, description=?, status=? WHERE id=? AND vendor_id=?");
$stmt->bind_param("sdssssii", $name, $price, $category, $image, $description, $status, $id, $vendorId); $stmt->execute();
if ($newImage) remove_uploaded_image($item["image"]);
header("Location: dashboard.php"); exit;
?>
