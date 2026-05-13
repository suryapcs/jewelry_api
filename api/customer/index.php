<?php
// api/customer/index.php
// GET  /api/customer         → getAllCustomers (active loans, currentAvailableValue=0)
// POST /api/customer         → addCustomer

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];

// ─── VALID ITEM TYPES ────────────────────────────────────────────────────────
$validItemTypes = [
    'Chain','Dollar Chain','Earring','Ring','Ear Matti','Dollar',
    'Necklace','Bracelet','Stone Earring','Titanic Earring',
    'Baby Ring','Mookuthi'
];

// ─────────────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
// GET /api/customer → all active customers (items with currentAvailableValue=0)

    $customers = $pdo->query(
        'SELECT * FROM customers ORDER BY created_at DESC'
    )->fetchAll();

    $result = [];
    foreach ($customers as $c) {
        $stmt = $pdo->prepare(
            'SELECT * FROM customer_items WHERE customer_id = ? AND currentAvailableValue = 0'
        );
        $stmt->execute([$c['id']]);
        $items = $stmt->fetchAll();

        if (count($items) > 0) {
            $c['items'] = $items;
            $result[]   = formatCustomer($c);
        }
    }

    sendResponse($result);

// ─────────────────────────────────────────────────────────────────────────────
} elseif ($method === 'POST') {
// POST /api/customer → add customer (or append item to existing)

    $body = json_decode(file_get_contents('php://input'), true);

    $name          = trim($body['name']          ?? '');
    $phone         = trim($body['phone_number']   ?? '');
    $aadhar        = trim($body['aadhar_number']  ?? '');
    $address       = trim($body['address']        ?? '');
    $code          = trim($body['code']           ?? '');
    $item          = trim($body['item']           ?? '');
    $weight        = (float) ($body['weight']        ?? 0);
    $pricePerWeight = (float) ($body['pricePerWeight'] ?? 0);
    $loanAmount    = (float) ($body['loanAmount']    ?? 0);
    $interest      = (float) ($body['interest']      ?? 0);

    if (!in_array($item, $validItemTypes, true)) {
        sendError('Invalid item type', 400);
    }

    // Check if customer exists (by phone + aadhar)
    $stmt = $pdo->prepare(
        'SELECT * FROM customers WHERE phone_number = ? AND aadhar_number = ?'
    );
    $stmt->execute([$phone, $aadhar]);
    $customer = $stmt->fetch();

    if ($customer) {
        // Existing → append item
        addItemToCustomer($pdo, $customer['id'], $code, $item, $weight, $pricePerWeight, $loanAmount, $interest);

        // Return full customer
        $stmt = $pdo->prepare('SELECT * FROM customer_items WHERE customer_id = ?');
        $stmt->execute([$customer['id']]);
        $customer['items'] = $stmt->fetchAll();

        sendResponse([
            'message'  => 'Existing customer found, item added successfully',
            'customer' => formatCustomer($customer),
        ]);
    } else {
        // New customer
        $customerId = 'CUST-' . time() . rand(100, 999);

        $stmt = $pdo->prepare(
            'INSERT INTO customers (customerId, name, phone_number, aadhar_number, address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$customerId, $name, $phone, $aadhar, $address]);
        $newId = (int) $pdo->lastInsertId();

        addItemToCustomer($pdo, $newId, $code, $item, $weight, $pricePerWeight, $loanAmount, $interest);

        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$newId]);
        $newCustomer = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT * FROM customer_items WHERE customer_id = ?');
        $stmt->execute([$newId]);
        $newCustomer['items'] = $stmt->fetchAll();

        sendResponse([
            'message'  => 'New customer created successfully',
            'customer' => formatCustomer($newCustomer),
        ], 201);
    }

} else {
    sendError('Method not allowed', 405);
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────
function addItemToCustomer(PDO $pdo, int $customerId, string $code, string $item,
    float $weight, float $pricePerWeight, float $loanAmount, float $interest): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO customer_items
         (customer_id, code, item, weight, pricePerWeight, loanAmount, currentLoanAmount, interest)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $customerId, $code, $item, $weight, $pricePerWeight,
        $loanAmount, $loanAmount, $interest
    ]);
}

function formatCustomer(array $c): array
{
    return [
        '_id'                   => (string)$c['id'],   // MongoDB-compat alias for frontend
        'id'                    => $c['id'],
        'customerId'            => $c['customerId'],
        'personalInfo'          => [
            'name'           => $c['name'],
            'phone_number'   => $c['phone_number'],
            'aadhar_number'  => $c['aadhar_number'],
            'address'        => $c['address'],
            'customerImage'  => $c['customerImage'] ?? null,
        ],
        'currentAvailableValue' => (int)($c['currentAvailableValue'] ?? 0),
        'items'                 => $c['items'] ?? [],
        'created_at'            => $c['created_at'],
        'updated_at'            => $c['updated_at'],
    ];
}
