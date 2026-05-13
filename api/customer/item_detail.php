<?php
// api/customer/item_detail.php
// GET /api/customer/item_detail?customerId=<cId>&itemCode=<code>

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

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
if (!$item) sendError('Item not found for this customer', 404);

sendResponse([
    'personalInfo' => [
        'name'          => $c['name'],
        'phone_number'  => $c['phone_number'],
        'aadhar_number' => $c['aadhar_number'],
        'address'       => $c['address'],
        'customerImage' => $c['customerImage'] ?? null,
    ],
    'itemDetails' => $item,
]);
