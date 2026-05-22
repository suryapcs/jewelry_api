<?php
// api/customer/next_item_code.php
// GET /api/customer/next-item-code/:customerId or /api/customer/next-item-code (global fallback)

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';

error_log("next_item_code.php called with customerId: " . $customerId);

if ($customerId) {
    // Per-customer code generation
    $stmt = $pdo->prepare(
        'SELECT id FROM customers WHERE LOWER(customerId) = LOWER(?)'
    );
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        error_log("Customer not found: " . $customerId);
        sendError('Customer not found', 404);
    }
    
    error_log("Found customer ID: " . $customer['id']);
    
    // Get the highest numeric code for this customer
    $stmt = $pdo->prepare(
        'SELECT code FROM customer_items WHERE customer_id = ? ORDER BY CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC LIMIT 1'
    );
    $stmt->execute([$customer['id']]);
    $lastCode = $stmt->fetchColumn();
    
    error_log("Last code for customer: " . ($lastCode ?? 'none'));
    
    if ($lastCode && preg_match('/^A(\d+)$/', $lastCode, $m)) {
        $nextNumber = (int)$m[1] + 1;
    } else {
        $nextNumber = 1;
    }
    
    $nextCode = 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
} else {
    // Global fallback (if no customerId provided)
    $lastCode = $pdo->query(
        'SELECT code FROM customer_items ORDER BY CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC LIMIT 1'
    )->fetchColumn();

    if ($lastCode && preg_match('/^A(\d+)$/', $lastCode, $m)) {
        $nextNumber = (int)$m[1] + 1;
    } else {
        $nextNumber = 1;
    }
    
    $nextCode = 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

error_log("Generated next code: " . $nextCode);
sendResponse(['nextCode' => $nextCode]);
