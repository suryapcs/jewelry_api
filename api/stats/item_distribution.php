<?php
// api/stats/item_distribution.php
// GET /api/stats/item_distribution

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$rows = $pdo->query(
    'SELECT item, COUNT(*) AS cnt FROM customer_items GROUP BY item'
)->fetchAll();

$total = array_sum(array_column($rows, 'cnt'));

$result = [];
foreach ($rows as $r) {
    $pct = $total > 0 ? number_format(($r['cnt'] / $total) * 100, 1) : '0.0';
    $result[$r['item']] = $pct;
}

sendResponse($result);
