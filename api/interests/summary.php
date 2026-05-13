<?php
// api/interests/summary.php
// GET /api/interests/summary?customerId=<cId>&itemCode=<code>
// Mirrors the Node.js getCustomerInterestSummary with carry-forward logic

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
$itemCode   = $_GET['itemCode']   ?? '';

if (!$customerId || !$itemCode) sendError('customerId and itemCode are required', 400);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE customerId = ?');
$stmt->execute([$customerId]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$stmt = $pdo->prepare('SELECT * FROM customer_items WHERE customer_id = ? AND code = ?');
$stmt->execute([$c['id'], $itemCode]);
$item = $stmt->fetch();
if (!$item) sendError('Item not found', 404);

$originalLoan = (float) $item['loanAmount'];
$interestRate = (float) $item['interest'];
$currentLoan  = $originalLoan;

// All payments sorted chronologically
$stmt = $pdo->prepare(
    'SELECT * FROM interest_details WHERE customerId = ? AND itemCode = ? ORDER BY paidDate ASC'
);
$stmt->execute([$customerId, $itemCode]);
$payments = $stmt->fetchAll();

// Determine start month: earliest of item.created_at and first payment
$itemCreated    = new DateTime($item['created_at']);
$startDT        = clone $itemCreated;

if (!empty($payments)) {
    $firstPay = new DateTime($payments[0]['paidDate']);
    if ($firstPay < $startDT) $startDT = $firstPay;
}
$startDT->modify('first day of this month');

$now    = new DateTime();
$grouped = [];
$carryPending = 0.0;

while ($startDT <= $now) {
    $year  = (int) $startDT->format('Y');
    $month = (int) $startDT->format('n');

    $baseInterest = ($currentLoan * $interestRate) / 100;
    $expected     = $baseInterest + $carryPending;

    $monthPayments = array_filter($payments, function($p) use ($year, $month) {
        $d = new DateTime($p['paidDate']);
        return (int)$d->format('Y') === $year && (int)$d->format('n') === $month;
    });

    $totalPaid = array_sum(array_column($monthPayments, 'paidAmount'));

    $pending = 0.0;
    $extra   = 0.0;

    if ($totalPaid < $expected) {
        $pending      = $expected - $totalPaid;
        $carryPending = $pending;
    } elseif ($totalPaid > $expected) {
        $extra        = $totalPaid - $expected;
        $carryPending = 0;
        $currentLoan -= $extra;
        if ($currentLoan < 0) $currentLoan = 0;
    } else {
        $carryPending = 0;
    }

    $grouped[] = [
        'month'       => $month,
        'year'        => $year,
        'expected'    => number_format($expected, 2, '.', ''),
        'totalPaid'   => number_format($totalPaid, 2, '.', ''),
        'pending'     => number_format($pending,   2, '.', ''),
        'extra'       => number_format($extra,     2, '.', ''),
        'currentLoan' => number_format($currentLoan, 2, '.', ''),
        'payments'    => array_values(array_map(fn($p) => [
            'paidAmount' => $p['paidAmount'],
            'paidDate'   => $p['paidDate'],
        ], $monthPayments)),
    ];

    $startDT->modify('+1 month');
}

// Add any payments outside the loop's date range
foreach ($payments as $p) {
    $d = new DateTime($p['paidDate']);
    $py = (int)$d->format('Y');
    $pm = (int)$d->format('n');
    $exists = false;
    foreach ($grouped as $g) {
        if ($g['year'] === $py && $g['month'] === $pm) { $exists = true; break; }
    }
    if (!$exists) {
        $grouped[] = [
            'month'       => $pm,
            'year'        => $py,
            'expected'    => '0.00',
            'totalPaid'   => number_format((float)$p['paidAmount'], 2, '.', ''),
            'pending'     => '0.00',
            'extra'       => '0.00',
            'currentLoan' => number_format($currentLoan, 2, '.', ''),
            'payments'    => [['paidAmount' => $p['paidAmount'], 'paidDate' => $p['paidDate']]],
        ];
    }
}

// Sort chronologically
usort($grouped, fn($a, $b) => strtotime("$a[year]-$a[month]-01") <=> strtotime("$b[year]-$b[month]-01"));

$currentLoanWithInterest = $currentLoan + $carryPending;

// Update currentLoanAmount in DB
$pdo->prepare('UPDATE customer_items SET currentLoanAmount = ? WHERE id = ?')
    ->execute([$currentLoanWithInterest, $item['id']]);

sendResponse([
    'customerId'         => $customerId,
    'itemCode'           => $itemCode,
    'interestRate'       => $interestRate,
    'totalLoanAmount'    => $originalLoan,
    'currentLoanAmount'  => $currentLoanWithInterest,
    'months'             => $grouped,
]);
