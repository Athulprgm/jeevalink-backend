<?php

/**
 * Jeevalink Backend API Diagnostic Script
 * Validates autoloading, environment setup, database configurations, and JWT systems.
 */

// 1. Autoload validation
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Config\JWT;
use App\Helpers\Validator;

echo "=== Jeevalink Backend Diagnostics ===\n";

// 2. Load environment variables
echo "Loading .env configuration... ";
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "[OK] Environment configuration loaded.\n";
} catch (Exception $e) {
    echo "[WARNING] Environment failed to load: " . $e->getMessage() . "\n";
}

// 3. Database connection check
echo "Checking database connection... ";
try {
    $db = Database::getConnection();
    echo "[OK] Successfully connected to database: " . ($_ENV['DB_NAME'] ?? 'jeevalink') . "\n";
} catch (Exception $e) {
    echo "[FAILED] Connection error: " . $e->getMessage() . "\n";
}

// 4. JWT mechanism diagnostic
echo "Testing JWT Token generation... ";
try {
    $userId = 999;
    $role = 'donor';
    $token = JWT::generateToken($userId, $role);
    echo "[OK] Token generated: " . substr($token, 0, 15) . "...\n";

    echo "Testing JWT Token verification... ";
    $decoded = JWT::validateToken($token);
    if ($decoded && $decoded->sub === $userId && $decoded->role === $role) {
        echo "[OK] Token verified. Payload claims match.\n";
    } else {
        echo "[FAILED] Verification payload payload does not match.\n";
    }
} catch (Exception $e) {
    echo "[FAILED] JWT execution error: " . $e->getMessage() . "\n";
}

// 5. Validator diagnostic
echo "Testing input validations... ";
$testData = [
    'email' => 'bad-email',
    'mobile' => '+919999999999',
    'blood_group' => 'O+'
];
$validator = new Validator($testData);
$validator->required(['email', 'mobile'])
          ->email('email')
          ->mobile('mobile')
          ->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);

if (!$validator->passes()) {
    $errors = $validator->getErrors();
    if (isset($errors['email'])) {
        echo "[OK] Validator successfully caught bad email format.\n";
    } else {
        echo "[FAILED] Validator did not detect invalid email format.\n";
    }
} else {
    echo "[FAILED] Validator falsely passed bad data input.\n";
}

echo "=== Diagnostics Completed ===\n";
