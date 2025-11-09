<?php
/**
 * Notifications Table Setup Script
 * Creates the notifications table and sets up the notification system
 */

require_once __DIR__ . '/config/config.php';

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'medium_clone';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Create notifications table
    $createNotificationsTable = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('follow', 'clap', 'comment', 'publication_invite') NOT NULL,
            content TEXT NOT NULL,
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createNotificationsTable);
    echo "✓ Notifications table created successfully.\n";
    
    // Add notification_preferences column to users table if it doesn't exist
    try {
        // First check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'notification_preferences'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            $addNotificationPreferences = "
                ALTER TABLE users 
                ADD COLUMN notification_preferences JSON DEFAULT NULL
            ";
            $pdo->exec($addNotificationPreferences);
            echo "✓ Added notification_preferences column to users table.\n";
        } else {
            echo "✓ notification_preferences column already exists.\n";
        }
    } catch (PDOException $e) {
        echo "⚠ Could not add notification_preferences column: " . $e->getMessage() . "\n";
        echo "  This is optional and won't affect basic notification functionality.\n";
    }
    
    // Create some sample notifications for testing (optional)
    echo "\nWould you like to create sample notifications for testing? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
        // Get first user ID for testing
        $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $user = $stmt->fetch();
        
        if ($user) {
            $userId = $user['id'];
            
            // Create sample notifications
            $sampleNotifications = [
                [
                    'type' => 'follow',
                    'content' => 'John Doe started following you',
                    'related_id' => null
                ],
                [
                    'type' => 'clap',
                    'content' => 'Jane Smith gave 5 claps to your article "Getting Started with PHP"',
                    'related_id' => 1
                ],
                [
                    'type' => 'comment',
                    'content' => 'Mike Johnson commented on your article "Web Development Tips"',
                    'related_id' => 2
                ]
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($sampleNotifications as $notification) {
                $stmt->execute([
                    $userId,
                    $notification['type'],
                    $notification['content'],
                    $notification['related_id']
                ]);
            }
            
            echo "✓ Created sample notifications for testing.\n";
        } else {
            echo "⚠ No users found. Please create a user first to test notifications.\n";
        }
    }
    
    echo "\n🎉 Notification system setup completed successfully!\n";
    echo "\nAvailable endpoints:\n";
    echo "- GET /api/notifications - Get user notifications\n";
    echo "- GET /api/notifications/unread-count - Get unread count\n";
    echo "- PUT /api/notifications/read/{id} - Mark notification as read\n";
    echo "- PUT /api/notifications/read-all - Mark all as read\n";
    echo "- DELETE /api/notifications/{id} - Delete notification\n";
    echo "- GET /api/notifications/stats - Get notification statistics\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}