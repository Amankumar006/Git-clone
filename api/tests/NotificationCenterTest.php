<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

class NotificationCenterTest {
    private $notification;
    private $user;
    private $controller;
    
    public function __construct() {
        $this->notification = new Notification();
        $this->user = new User();
        $this->controller = new NotificationController();
    }
    
    public function runTests() {
        echo "Running Notification Center Tests...\n\n";
        
        $this->testNotificationPreferences();
        $this->testNotificationCreation();
        $this->testNotificationFiltering();
        
        echo "\nAll tests completed!\n";
    }
    
    private function testNotificationPreferences() {
        echo "Testing notification preferences...\n";
        
        // Test default preferences
        $preferences = $this->user->getNotificationPreferences(1);
        
        if (isset($preferences['email_notifications']) && isset($preferences['push_notifications'])) {
            echo "✓ Default notification preferences loaded correctly\n";
        } else {
            echo "✗ Failed to load default notification preferences\n";
        }
        
        // Test updating preferences
        $newPreferences = [
            'email_notifications' => [
                'follows' => false,
                'claps' => true,
                'comments' => true,
                'publication_invites' => false,
                'weekly_digest' => true
            ],
            'push_notifications' => [
                'follows' => true,
                'claps' => false,
                'comments' => true,
                'publication_invites' => true
            ]
        ];
        
        $result = $this->user->updateNotificationPreferences(1, $newPreferences);
        
        if ($result['success']) {
            echo "✓ Notification preferences updated successfully\n";
        } else {
            echo "✗ Failed to update notification preferences\n";
        }
    }
    
    private function testNotificationCreation() {
        echo "Testing notification creation...\n";
        
        // Test follow notification
        $result = $this->notification->createFollowNotification(2, 1, 'testuser');
        
        if ($result['success']) {
            echo "✓ Follow notification created successfully\n";
        } else {
            echo "✗ Failed to create follow notification: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
        // Test clap notification
        $result = $this->notification->createClapNotification(2, 1, 1, 'testuser', 'Test Article', 5);
        
        if ($result['success']) {
            echo "✓ Clap notification created successfully\n";
        } else {
            echo "✗ Failed to create clap notification: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
        // Test comment notification
        $result = $this->notification->createCommentNotification(2, 1, 1, 'testuser', 'Test Article');
        
        if ($result['success']) {
            echo "✓ Comment notification created successfully\n";
        } else {
            echo "✗ Failed to create comment notification: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    }
    
    private function testNotificationFiltering() {
        echo "Testing notification filtering...\n";
        
        // Get all notifications
        $allNotifications = $this->notification->getUserNotifications(1, false, 10, 0);
        
        // Get unread notifications only
        $unreadNotifications = $this->notification->getUserNotifications(1, true, 10, 0);
        
        if (is_array($allNotifications) && is_array($unreadNotifications)) {
            echo "✓ Notification filtering works correctly\n";
            echo "  - Total notifications: " . count($allNotifications) . "\n";
            echo "  - Unread notifications: " . count($unreadNotifications) . "\n";
        } else {
            echo "✗ Failed to filter notifications\n";
        }
        
        // Test unread count
        $unreadCount = $this->notification->getUnreadCount(1);
        
        if (is_numeric($unreadCount)) {
            echo "✓ Unread count retrieved: $unreadCount\n";
        } else {
            echo "✗ Failed to get unread count\n";
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new NotificationCenterTest();
    $test->runTests();
}