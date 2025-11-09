<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

// Test data
$testData = [
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

try {
    $userModel = new User();
    
    echo "Testing user registration...\n";
    echo "Data: " . json_encode($testData) . "\n\n";
    
    $result = $userModel->register($testData);
    
    if ($result['success']) {
        echo "SUCCESS: User registered with ID: " . $result['user']['id'] . "\n";
        echo "Result: " . json_encode($result) . "\n";
    } else {
        echo "FAILED: Registration failed\n";
        echo "Errors: " . json_encode($result['errors'] ?? []) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>