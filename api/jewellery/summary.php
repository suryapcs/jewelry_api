<?php
// api/jewellery/summary.php
// GET /api/jewellery/summary

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

$result = [];

foreach ($validItemTypes as $itemType) {
    $stmt = $pdo->prepare(
        'SELECT ci.*, c.customerId
         FROM customer_items ci
         JOIN customers c ON c.id = ci.customer_id
         WHERE ci.item = ? AND ci.currentAvailableValue = 0'
    );
    $stmt->execute([$itemType]);
    $items = $stmt->fetchAll();

    if (empty($items)) continue;

    $totalCount        = 0;
    $totalWeight       = 0.0;
    $totalGoldValue    = 0.0;
    $totalLoanAmount   = 0.0;
    $totalInterestPaid = 0.0;

    foreach ($items as $i) {
        $totalCount++;
        $totalWeight     += (float) $i['weight'];
        $totalGoldValue  += (float) $i['weight'] * (float) $i['pricePerWeight'];
        $totalLoanAmount += (float) $i['loanAmount'];

        $paid = $pdo->prepare(
            'SELECT COALESCE(SUM(paidAmount),0) FROM interest_details WHERE customerId = ? AND itemCode = ?'
        );
        $paid->execute([$i['customerId'], $i['code']]);
        $totalInterestPaid += (float) $paid->fetchColumn();
    }

    $result[] = [
        'itemType'          => $itemType,
        'jewelleryNames'    => [$itemType],
        'totalCount'        => $totalCount,
        'totalWeight'       => $totalWeight,
        'totalGoldValue'    => $totalGoldValue,
        'totalLoanAmount'   => $totalLoanAmount,
        'totalInterestPaid' => $totalInterestPaid,
    ];
}

sendResponse(['data' => $result]);
