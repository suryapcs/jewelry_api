<?php
// api/customer/restore_item.php
// PUT /api/customer/restore_item?customerId=<cId>&itemCode=<code>

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
$itemCode   = $_GET['itemCode']   ?? '';

if (!$customerId || !$itemCode) sendError('customerId and itemCode are required', 400);

// Get internal customer id
$stmt = $pdo->prepare('SELECT id FROM customers WHERE customerId = ?');
$stmt->execute([$customerId]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$stmt = $pdo->prepare(
    'UPDATE customer_items SET currentAvailableValue = 0 WHERE customer_id = ? AND code = ?'
);
$stmt->execute([$c['id'], $itemCode]);

if ($stmt->rowCount() === 0) sendError('Item not found', 404);

sendResponse(['message' => 'Item restored successfully']);
