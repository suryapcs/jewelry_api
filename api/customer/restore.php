<?php
// api/customer/restore.php
// PUT /api/customer/restore?id=<id>  → restore customer (currentAvailableValue = 0)

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') sendError('Method not allowed', 405);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) sendError('Customer ID is required', 400);

$stmt = $pdo->prepare('UPDATE customers SET currentAvailableValue = 0 WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) sendError('Customer not found', 404);

sendResponse(['message' => 'Customer restored successfully']);
