<?php
// api/customer/existing.php
// GET /api/customer/existing → customers with at least one item currentAvailableValue=1

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

// Customers who have at least one closed item
$customerIds = $pdo->query(
    'SELECT DISTINCT customer_id FROM customer_items WHERE currentAvailableValue = 1'
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($customerIds)) {
    sendResponse([]);
}

$placeholders = implode(',', array_fill(0, count($customerIds), '?'));

$stmt = $pdo->prepare(
    "SELECT * FROM customers WHERE id IN ($placeholders) ORDER BY created_at DESC"
);
$stmt->execute($customerIds);
$customers = $stmt->fetchAll();

$result = [];
foreach ($customers as $c) {
    $stmt = $pdo->prepare(
        'SELECT * FROM customer_items WHERE customer_id = ? AND currentAvailableValue = 1'
    );
    $stmt->execute([$c['id']]);
    $items = $stmt->fetchAll();

    $result[] = [
        '_id'          => (string)$c['id'],   // MongoDB-compat alias
        'id'           => $c['id'],
        'customerId'   => $c['customerId'],
        'personalInfo' => [
            'name'          => $c['name'],
            'phone_number'  => $c['phone_number'],
            'aadhar_number' => $c['aadhar_number'],
            'address'       => $c['address'],
            'customerImage' => $c['customerImage'] ?? null,
        ],
        'items'      => $items,
        'created_at' => $c['created_at'],
        'updated_at' => $c['updated_at'],
    ];
}

sendResponse($result);
