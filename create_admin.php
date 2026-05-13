<?php
// create_admin.php
// Run once from CLI: php create_admin.php
// Creates the default admin account

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

$email     = 'pcs@gmail.com';
$password  = '123456';
$firstName = 'PCS';
$lastName  = 'Admin';

// Check if already exists
$stmt = $pdo->prepare('SELECT id FROM admins WHERE Email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "⚠️  Admin with email '$email' already exists.\n";
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$stmt = $pdo->prepare(
    'INSERT INTO admins (FirstName, LastName, Email, Password, Role) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$firstName, $lastName, $email, $hashed, 'admin']);

echo "✅ Admin created successfully!\n";
echo "   Name    : $firstName $lastName\n";
echo "   Email   : $email\n";
echo "   Password: $password  (stored as bcrypt hash)\n";
echo "   Role    : admin\n";
