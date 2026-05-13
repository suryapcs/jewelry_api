<?php
// api/jewellery/add.php
// POST /api/jewellery/add?customerId=<cId>

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
if (!$customerId) sendError('customerId is required', 400);

$validItemTypes = [
    'Chain','Dollar Chain','Earring','Ring','Ear Matti','Dollar',
    'Necklace','Bracelet','Stone Earring','Titanic Earring',
    'Baby Ring','Mookuthi'
];

$body = json_decode(file_get_contents('php://input'), true);
$code          = trim($body['code']           ?? '');
$item          = trim($body['item']           ?? '');
$weight        = (float)($body['weight']        ?? 0);
$pricePerWeight = (float)($body['pricePerWeight'] ?? 0);
$loanAmount    = (float)($body['loanAmount']    ?? 0);
$interest      = (float)($body['interest']      ?? 0);

if (!in_array($item, $validItemTypes, true)) sendError('Invalid item type', 400);

// Case-insensitive customer lookup
$stmt = $pdo->prepare('SELECT * FROM customers WHERE LOWER(customerId) = LOWER(?)');
$stmt->execute([$customerId]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$stmt = $pdo->prepare(
    'INSERT INTO customer_items
     (customer_id, code, item, weight, pricePerWeight, loanAmount, currentLoanAmount, interest)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$c['id'], $code, $item, $weight, $pricePerWeight, $loanAmount, $loanAmount, $interest]);

sendResponse(['message' => 'Jewellery added successfully'], 201);
