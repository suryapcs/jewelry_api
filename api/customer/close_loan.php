<?php
// api/customer/close_loan.php
// POST /api/customer/close_loan?customerId=<cId>&itemCode=<code>

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
$itemCode   = $_GET['itemCode']   ?? '';

if (!$customerId || !$itemCode) sendError('customerId and itemCode are required', 400);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE customerId = ?');
$stmt->execute([$customerId]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$stmt = $pdo->prepare(
    'SELECT * FROM customer_items WHERE customer_id = ? AND code = ?'
);
$stmt->execute([$c['id'], $itemCode]);
$item = $stmt->fetch();
if (!$item) sendError('Item not found', 404);

// Mark item as closed
$pdo->prepare(
    'UPDATE customer_items SET currentLoanAmount = 0, currentAvailableValue = 1 WHERE id = ?'
)->execute([$item['id']]);

sendResponse([
    'message'               => 'Loan closed successfully',
    'customerId'            => $customerId,
    'itemCode'              => $itemCode,
    'currentAvailableValue' => 1,
]);
