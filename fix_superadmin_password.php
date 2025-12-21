<?php
/**
 * Fix Super Admin Password
 * Run this script once to update the super admin password hash
 */

require_once __DIR__ . '/src/config/db.php';

$pdo = getDB();

// Generate new password hash for 'superadmin123'
$newHash = password_hash('superadmin123', PASSWORD_ARGON2ID);

// Update the super admin password
$stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE email = :email AND role = :role');
$stmt->execute([
    'hash' => $newHash,
    'email' => 'superadmin@bloomvine.com',
    'role' => 'super_admin'
]);

$rowsAffected = $stmt->rowCount();

if ($rowsAffected > 0) {
    echo "✅ Password updated successfully! ($rowsAffected row(s) affected)\n";
    echo "New password hash: $newHash\n";
    echo "\nYou can now login with:\n";
    echo "Email: superadmin@bloomvine.com\n";
    echo "Password: superadmin123\n";
} else {
    echo "❌ No rows updated. Check if super admin user exists.\n";
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE email = :email');
    $stmt->execute(['email' => 'superadmin@bloomvine.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found but role is: " . $user['role'] . "\n";
    } else {
        echo "User not found. You may need to run the migration first.\n";
    }
}

