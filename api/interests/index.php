<?php
// api/interests/index.php
// GET  /api/interests  → getAllInterests (enriched)
// POST /api/interests  → addInterest

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];

// ─────────────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    $interests = $pdo->query('SELECT * FROM interest_details ORDER BY created_at DESC')->fetchAll();

    $result = [];
    foreach ($interests as $i) {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE customerId = ?');
        $stmt->execute([$i['customerId']]);
        $c = $stmt->fetch();

        $itemName  = '';
        $itemLoan  = 0;
        if ($c) {
            $stmt = $pdo->prepare(
                'SELECT item, loanAmount FROM customer_items WHERE customer_id = ? AND code = ?'
            );
            $stmt->execute([$c['id'], $i['itemCode']]);
            $itemRow = $stmt->fetch();
            if ($itemRow) {
                $itemName = $itemRow['item'];
                $itemLoan = (float) $itemRow['loanAmount'];
            }
        }

        $result[] = [
            'id'              => $i['id'],
            'customerId'      => $i['customerId'],
            'customerName'    => $c ? $c['name'] : 'Unknown',
            'itemCode'        => $i['itemCode'],
            'itemName'        => $itemName,
            'paidAmount'      => (float) $i['paidAmount'],
            'paidDate'        => $i['paidDate'],
            'monthlyInterest' => (float) $i['monthlyInterest'],
            'totalLoanAmount' => $itemLoan,
            'created_at'      => $i['created_at'],
        ];
    }

    sendResponse($result);

// ─────────────────────────────────────────────────────────────────────────────
} elseif ($method === 'POST') {

    $body       = json_decode(file_get_contents('php://input'), true);
    $customerId = trim($body['customerId'] ?? '');
    $itemCode   = trim($body['itemCode']   ?? '');
    $month      = (int)($body['month']     ?? 0);
    $year       = (int)($body['year']      ?? 0);
    $paidAmount = (float)($body['paidAmount'] ?? 0);

    if (!$customerId || !$itemCode || !$month || !$year) {
        sendError('customerId, itemCode, month, year are required', 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE customerId = ?');
    $stmt->execute([$customerId]);
    $c = $stmt->fetch();
    if (!$c) sendError('Customer not found', 404);

    $stmt = $pdo->prepare(
        'SELECT loanAmount, interest FROM customer_items WHERE customer_id = ? AND code = ?'
    );
    $stmt->execute([$c['id'], $itemCode]);
    $itemRow = $stmt->fetch();
    if (!$itemRow) sendError('Item not found for this customer', 404);

    $loanAmount   = (float) $itemRow['loanAmount'];
    $interestRate = (float) $itemRow['interest'];

    if (!$loanAmount || !$interestRate) {
        sendError('Invalid loanAmount or interest value on the item', 400);
    }

    $monthlyInterest = ($loanAmount * $interestRate) / 100;

    $stmt = $pdo->prepare(
        'INSERT INTO interest_details
         (customerId, itemCode, month, year, paidDate, monthlyInterest, paidAmount)
         VALUES (?, ?, ?, ?, NOW(), ?, ?)'
    );
    $stmt->execute([$customerId, $itemCode, $month, $year, $monthlyInterest, $paidAmount]);
    $newId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM interest_details WHERE id = ?');
    $stmt->execute([$newId]);

    sendResponse($stmt->fetch(), 201);

} else {
    sendError('Method not allowed', 405);
}
