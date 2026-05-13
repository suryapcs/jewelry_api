<?php
// api/interests/by_customer.php
// GET /api/interests/by_customer?customerId=<cId>

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
if (!$customerId) sendError('customerId is required', 400);

$stmt = $pdo->prepare(
    'SELECT * FROM interest_details WHERE customerId = ? ORDER BY year ASC, month ASC'
);
$stmt->execute([$customerId]);

sendResponse($stmt->fetchAll());
