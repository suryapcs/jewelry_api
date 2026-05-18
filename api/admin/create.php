<?php
// api/admin/create.php
// POST /api/admin/create
// Body: { Name, Email|Phone, Password }

require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$name     = trim($body['Name']     ?? $body['name']     ?? '');
$email    = trim($body['Email']    ?? $body['email']    ?? '');
$phone    = trim($body['Phone']    ?? $body['phone']    ?? '');
$password = $body['Password']      ?? $body['password'] ?? '';

// ── Validation ──────────────────────────────────────────────
if (strlen($name) < 2 || strlen($name) > 100) {
    sendError('"Name" must be 2–100 characters', 400);
}

if (empty($email) && empty($phone)) {
    sendError('Please provide either Email or Phone', 400);
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Invalid email address', 400);
}

if (!empty($phone) && !preg_match('/^\+?[0-9]{7,15}$/', preg_replace('/[\s\-]/', '', $phone))) {
    sendError('Invalid phone number (7–15 digits)', 400);
}

if (strlen($password) < 6) {
    sendError('"Password" must be at least 6 characters', 400);
}

// ── Detect schema version ────────────────────────────────────
// Check if the "Name" column exists (new schema) or only FirstName/LastName (old schema)
$cols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
$hasNameCol  = in_array('Name',  $cols);
$hasPhoneCol = in_array('Phone', $cols);

// ── Duplicate check ─────────────────────────────────────────
if (!empty($email)) {
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE Email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError('An admin with this email already exists', 400);
    }
}

if ($hasPhoneCol && !empty($phone)) {
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE Phone = ?');
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendError('An admin with this phone already exists', 400);
    }
}

// ── Hash password ────────────────────────────────────────────
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// Split name into first / last for old schema compatibility
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0];
$lastName  = $nameParts[1] ?? '';

// ── Insert (adaptive to schema) ──────────────────────────────
if ($hasNameCol && $hasPhoneCol) {
    // New schema
    $stmt = $pdo->prepare(
        'INSERT INTO admins (Name, FirstName, LastName, Email, Phone, Password, Role)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name, $firstName, $lastName,
        $email ?: null,
        $phone ?: null,
        $hashed, 'admin',
    ]);
} else {
    // Old schema (no Name/Phone columns yet)
    $stmt = $pdo->prepare(
        'INSERT INTO admins (FirstName, LastName, Email, Password, Role)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$firstName, $lastName, $email ?: '', $hashed, 'admin']);
}

$newId = $pdo->lastInsertId();

// ── Optional welcome email ───────────────────────────────────
$emailUser = getenv('EMAIL_USER');
$emailPass = getenv('EMAIL_PASS');
if ($emailUser && $emailPass && !empty($email)) {
    $subject = 'Welcome to the Jwell Admin Panel';
    $message = "Hello $name,\n\nYour admin account has been created successfully.\nEmail: $email\n\nPlease login and change your password.\n";
    $headers = "From: $emailUser\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($email, $subject, $message, $headers);
}

sendResponse([
    'message' => 'Admin registered successfully',
    'admin'   => [
        'id'    => (int) $newId,
        'name'  => $name,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'role'  => 'admin',
    ],
], 201);
