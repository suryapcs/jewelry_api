<?php
// api/customer/detail.php
// GET    /api/customer/detail?id=<id>            → getCustomerById
// PUT    /api/customer/detail?id=<id>            → updateCustomerFull
// DELETE /api/customer/detail?id=<id>            → softDelete (currentAvailableValue=1)

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) sendError('Customer ID is required', 400);

// ─────────────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) sendError('Customer not found', 404);

    // Only items where currentAvailableValue = 0
    $stmt = $pdo->prepare(
        'SELECT * FROM customer_items WHERE customer_id = ? AND currentAvailableValue = 0'
    );
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    sendResponse([
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
    ]);

// ─────────────────────────────────────────────────────────────────────────────
} elseif ($method === 'PUT') {

    $body             = json_decode(file_get_contents('php://input'), true);
    $personalInfo     = $body['personalInfo']     ?? [];
    $itemData         = $body['itemData']         ?? [];
    $selectedItemCode = $body['selectedItemCode'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) sendError('Customer not found', 404);

    // Update personal info
    if (!empty($personalInfo)) {
        $fields = [];
        $vals   = [];
        $map    = ['name','phone_number','aadhar_number','address','customerImage'];
        foreach ($map as $f) {
            if (isset($personalInfo[$f])) {
                $fields[] = "`$f` = ?";
                $vals[]   = $personalInfo[$f];
            }
        }
        if ($fields) {
            $vals[] = $id;
            $pdo->prepare('UPDATE customers SET ' . implode(', ', $fields) . ' WHERE id = ?')
                ->execute($vals);
        }
    }

    // Update item
    if (!empty($itemData) && $selectedItemCode) {
        $stmt = $pdo->prepare(
            'SELECT id FROM customer_items WHERE customer_id = ? AND code = ?'
        );
        $stmt->execute([$id, $selectedItemCode]);
        $itemRow = $stmt->fetch();
        if (!$itemRow) sendError('Item not found', 404);

        $fields = [];
        $vals   = [];
        $allowed = ['item','weight','pricePerWeight','loanAmount','currentLoanAmount','interest'];
        foreach ($allowed as $f) {
            if (isset($itemData[$f])) {
                $fields[] = "`$f` = ?";
                $vals[]   = $itemData[$f];
            }
        }
        if ($fields) {
            $vals[] = $itemRow['id'];
            $pdo->prepare('UPDATE customer_items SET ' . implode(', ', $fields) . ' WHERE id = ?')
                ->execute($vals);
        }
    }

    sendResponse(['message' => 'Customer and item updated successfully']);

// ─────────────────────────────────────────────────────────────────────────────
} elseif ($method === 'DELETE') {

    $stmt = $pdo->prepare('UPDATE customers SET currentAvailableValue = 1 WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) sendError('Customer not found', 404);

    sendResponse(['message' => 'Customer marked as deleted (soft delete)']);

} else {
    sendError('Method not allowed', 405);
}
