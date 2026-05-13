<?php
// api/customer/upload_image.php
// POST /api/customer/upload_image?id=<customerId_numeric>
// Accepts multipart/form-data with fields: itemImage, customerImage

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) sendError('Customer ID is required', 400);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$uploadDir = __DIR__ . '/../../uploads/customers/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$updates = [];

if (!empty($_FILES['customerImage']['tmp_name'])) {
    $ext      = pathinfo($_FILES['customerImage']['name'], PATHINFO_EXTENSION);
    $filename = 'customerImage-' . time() . '-' . rand(100,999) . '.' . $ext;
    $dest     = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['customerImage']['tmp_name'], $dest)) {
        $updates['customerImage'] = 'uploads/customers/' . $filename;
    }
}

if (!empty($updates)) {
    $fields = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($updates)));
    $vals   = array_values($updates);
    $vals[] = $id;
    $pdo->prepare("UPDATE customers SET $fields WHERE id = ?")->execute($vals);
}

sendResponse(['message' => 'Images uploaded successfully']);
