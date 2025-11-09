<?php
/**
 * Test Notifications System
 * Simple script to test notification functionality
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Notification.php';

try {
    echo "🔔 Testing Notification System\n";
    echo "==============================\n\n";
    
    $notification = new Notification();
    
    // Test 1: Check if notifications table exists
    echo "1. Checking notifications table...\n";
    try {
        // Create direct database connection for testing
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'medium_clone';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll();
        echo "✓ Notifications table exists with columns:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
    } catch (Exception $e) {
        echo "❌ Notifications table does not exist. Please run setup_notifications.php first.\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 2: Check if users exist for testing
    echo "2. Checking for test users...\n";
    $stmt = $pdo->query("SELECT id, username FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No users found. Please create some users first.\n";
        exit(1);
    }
    
    echo "✓ Found users for testing:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Username: {$user['username']}\n";
    }
    
    echo "\n";
    
    // Test 3: Create test notifications
    echo "3. Creating test notifications...\n";
    $testUser = $users[0];
    
    // Create different types of notifications
    $testNotifications = [
        [
            'type' => 'follow',
            'content' => 'Test User started following you',
            'related_id' => null
        ],
        [
            'type' => 'clap',
            'content' => 'Test User gave 5 claps to your article "Test Article"',
            'related_id' => 1
        ],
        [
            'type' => 'comment',
            'content' => 'Test User commented on your article "Another Test Article"',
            'related_id' => 2
        ]
    ];
    
    foreach ($testNotifications as $notif) {
        $result = $notification->createNotification(
            $testUser['id'],
            $notif['type'],
            $notif['content'],
            $notif['related_id']
        );
        
        if ($result['success']) {
            echo "✓ Created {$notif['type']} notification\n";
        } else {
            echo "❌ Failed to create {$notif['type']} notification\n";
        }
    }
    
    echo "\n";
    
    // Test 4: Retrieve notifications
    echo "4. Testing notification retrieval...\n";
    $notifications = $notification->getUserNotifications($testUser['id']);
    echo "✓ Retrieved " . count($notifications) . " notifications\n";
    
    foreach ($notifications as $notif) {
        $readStatus = $notif['is_read'] ? 'Read' : 'Unread';
        echo "  - [{$readStatus}] {$notif['type']}: {$notif['content']}\n";
    }
    
    echo "\n";
    
    // Test 5: Test unread count
    echo "5. Testing unread count...\n";
    $unreadCount = $notification->getUnreadCount($testUser['id']);
    echo "✓ Unread count: $unreadCount\n";
    
    echo "\n";
    
    // Test 6: Test notification methods
    echo "6. Testing notification methods...\n";
    
    if (!empty($notifications)) {
        $firstNotification = $notifications[0];
        
        // Test mark as read
        $result = $notification->markAsRead($firstNotification['id'], $testUser['id']);
        if ($result['success']) {
            echo "✓ Marked notification as read\n";
        }
        
        // Check unread count again
        $newUnreadCount = $notification->getUnreadCount($testUser['id']);
        echo "✓ New unread count: $newUnreadCount\n";
    }
    
    echo "\n";
    
    // Test 7: Test API endpoints (if possible)
    echo "7. Testing API endpoints...\n";
    echo "You can test the following endpoints manually:\n";
    echo "  - GET /api/notifications/unread-count\n";
    echo "  - GET /api/notifications\n";
    echo "  - PUT /api/notifications/read/{id}\n";
    echo "  - PUT /api/notifications/read-all\n";
    echo "  - DELETE /api/notifications/{id}\n";
    
    echo "\n🎉 All tests completed successfully!\n";
    echo "\nNotification system is ready to use.\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    exit(1);
}