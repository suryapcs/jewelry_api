<?php
// api/stats/loan_by_item.php
// GET /api/stats/loan_by_item

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$rows = $pdo->query(
    'SELECT item, COALESCE(SUM(loanAmount),0) AS total FROM customer_items GROUP BY item'
)->fetchAll();

$result = [];
foreach ($rows as $r) {
    $result[$r['item']] = (float) $r['total'];
}
sendResponse($result);
