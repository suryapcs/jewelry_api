<?php
// api/customer/dashboard_stats.php
// GET /api/customer/dashboard_stats

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$totalCustomers    = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$existingCustomers = (int) $pdo->query('SELECT COUNT(*) FROM customers WHERE currentAvailableValue = 1')->fetchColumn();
$currentCustomers  = (int) $pdo->query('SELECT COUNT(*) FROM customers WHERE currentAvailableValue = 0')->fetchColumn();

$row = $pdo->query(
    'SELECT COALESCE(SUM(loanAmount),0) AS totalLoan,
            COUNT(*) AS totalItems,
            COALESCE(SUM(weight),0) AS totalWeight
     FROM customer_items'
)->fetch();

$totalLoanAmount = (float) $row['totalLoan'];
$totalItems      = (int)   $row['totalItems'];
$totalWeight     = (float) $row['totalWeight'];

$totalInterestPaid = (float) $pdo->query(
    'SELECT COALESCE(SUM(paidAmount),0) FROM interest_details'
)->fetchColumn();

sendResponse([
    'totalCustomers'    => $totalCustomers,
    'existingCustomers' => $existingCustomers,
    'currentCustomers'  => $currentCustomers,
    'totalLoanAmount'   => $totalLoanAmount,
    'totalItems'        => $totalItems,
    'totalWeight'       => $totalWeight,
    'totalInterestPaid' => $totalInterestPaid,
]);
