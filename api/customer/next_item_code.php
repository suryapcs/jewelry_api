<?php
// api/customer/next_item_code.php
// GET /api/customer/next_item_code

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$lastCode = $pdo->query(
    'SELECT code FROM customer_items ORDER BY created_at DESC LIMIT 1'
)->fetchColumn();

if ($lastCode && preg_match('/^A(\d+)$/', $lastCode, $m)) {
    $nextCode = 'A' . ((int)$m[1] + 1);
} else {
    $nextCode = 'A101';
}

sendResponse(['nextCode' => $nextCode]);
