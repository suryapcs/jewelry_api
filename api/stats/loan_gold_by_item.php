<?php
// api/stats/loan_gold_by_item.php
// GET /api/stats/loan_gold_by_item

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$validItemTypes = [
    'Chain','Dollar Chain','Earring','Ring','Ear Matti','Dollar',
    'Necklace','Bracelet','Stone Earring','Titanic Earring',
    'Baby Ring','Mookuthi'
];

$rows = $pdo->query(
    'SELECT item,
            COALESCE(SUM(loanAmount),0) AS totalLoanAmount,
            COALESCE(SUM(weight),0)     AS totalGoldWeight
     FROM customer_items
     GROUP BY item'
)->fetchAll();

$map = [];
foreach ($rows as $r) {
    $map[$r['item']] = [
        'totalLoanAmount' => (float) $r['totalLoanAmount'],
        'totalGoldWeight' => (float) $r['totalGoldWeight'],
    ];
}

$result = [];
foreach ($validItemTypes as $t) {
    $result[] = [
        'name'       => $t,
        'loanAmount' => $map[$t]['totalLoanAmount'] ?? 0,
        'goldWeight' => $map[$t]['totalGoldWeight'] ?? 0,
    ];
}

sendResponse($result);
