<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/database.php';

class NotificationSystemTest {
    private $notificationModel;
    private $testUserId1 = 1;
    private $testUserId2 = 2;
    private $testArticleId = 1;
    
    public function __construct() {
        $this->notificationModel = new Notification();
    }
    
    public function runTests() {
        echo "Running Notification System Tests...\n\n";
        
        $this->testCreateNotification();
        $this->testGetUserNotifications();
        $this->testMarkAsRead();
        $this->testDeleteNotification();
        $this->testNotificationTypes();
        $this->testNotificationStats();
        
        echo "\nAll notification system tests completed!\n";
    }
    
    private function testCreateNotification() {
        echo "Testing create notification functionality...\n";
        
        $result = $this->notificationModel->createNotification(
            $this->testUserId1,
            'follow',
            'Test user started following you',
            $this->testUserId2
        );
        
        if ($result['success']) {
            echo "✓ Successfully created notification\n";
            echo "  - Notification ID: " . $result['data']['id'] . "\n";
            echo "  - Type: " . $result['data']['type'] . "\n";
            echo "  - Content: " . $result['data']['content'] . "\n";
            
            $this->testNotificationId = $result['data']['id'];
        } else {
            echo "✗ Failed to create notification: " . $result['error'] . "\n";
        }
    }
    
    private function testGetUserNotifications() {
        echo "\nTesting get user notifications...\n";
        
        $notifications = $this->notificationModel->getUserNotifications($this->testUserId1, false, 10, 0);
        $unreadCount = $this->notificationModel->getUnreadCount($this->testUserId1);
        
        echo "✓ Retrieved user notifications\n";
        echo "  - Total notifications: " . count($notifications) . "\n";
        echo "  - Unread count: $unreadCount\n";
        
        // Test unread only
        $unreadNotifications = $this->notificationModel->getUserNotifications($this->testUserId1, true, 10, 0);
        echo "  - Unread notifications: " . count($unreadNotifications) . "\n";
    }
    
    private function testMarkAsRead() {
        echo "\nTesting mark as read functionality...\n";
        
        if (!isset($this->testNotificationId)) {
            echo "✗ Skipping mark as read test - no notification available\n";
            return;
        }
        
        $result = $this->notificationModel->markAsRead($this->testNotificationId, $this->testUserId1);
        
        if ($result['success']) {
            echo "✓ Successfully marked notification as read\n";
            echo "  - Message: " . $result['message'] . "\n";
            
            // Verify unread count decreased
            $newUnreadCount = $this->notificationModel->getUnreadCount($this->testUserId1);
            echo "  - New unread count: $newUnreadCount\n";
        } else {
            echo "✗ Failed to mark as read: " . $result['error'] . "\n";
        }
        
        // Test mark all as read
        $markAllResult = $this->notificationModel->markAllAsRead($this->testUserId1);
        if ($markAllResult['success']) {
            echo "✓ Successfully marked all notifications as read\n";
            echo "  - Message: " . $markAllResult['message'] . "\n";
        } else {
            echo "✗ Failed to mark all as read: " . $markAllResult['error'] . "\n";
        }
    }
    
    private function testDeleteNotification() {
        echo "\nTesting delete notification functionality...\n";
        
        if (!isset($this->testNotificationId)) {
            echo "✗ Skipping delete test - no notification available\n";
            return;
        }
        
        $result = $this->notificationModel->deleteNotification($this->testNotificationId, $this->testUserId1);
        
        if ($result['success']) {
            echo "✓ Successfully deleted notification\n";
            echo "  - Message: " . $result['message'] . "\n";
        } else {
            echo "✗ Failed to delete notification: " . $result['error'] . "\n";
        }
    }
    
    private function testNotificationTypes() {
        echo "\nTesting different notification types...\n";
        
        // Test follow notification
        $followResult = $this->notificationModel->createFollowNotification(
            $this->testUserId2,
            $this->testUserId1,
            'testuser2'
        );
        
        if ($followResult['success']) {
            echo "✓ Created follow notification\n";
        } else {
            echo "✗ Failed to create follow notification\n";
        }
        
        // Test clap notification
        $clapResult = $this->notificationModel->createClapNotification(
            $this->testUserId2,
            $this->testUserId1,
            $this->testArticleId,
            'testuser2',
            'Test Article Title',
            5
        );
        
        if ($clapResult['success']) {
            echo "✓ Created clap notification\n";
        } else {
            echo "✗ Failed to create clap notification\n";
        }
        
        // Test comment notification
        $commentResult = $this->notificationModel->createCommentNotification(
            $this->testUserId2,
            $this->testUserId1,
            $this->testArticleId,
            'testuser2',
            'Test Article Title'
        );
        
        if ($commentResult['success']) {
            echo "✓ Created comment notification\n";
        } else {
            echo "✗ Failed to create comment notification\n";
        }
        
        // Test publication invite notification
        $inviteResult = $this->notificationModel->createPublicationInviteNotification(
            $this->testUserId2,
            $this->testUserId1,
            1,
            'testuser2',
            'Test Publication',
            'writer'
        );
        
        if ($inviteResult['success']) {
            echo "✓ Created publication invite notification\n";
        } else {
            echo "✗ Failed to create publication invite notification\n";
        }
    }
    
    private function testNotificationStats() {
        echo "\nTesting notification statistics...\n";
        
        $stats = $this->notificationModel->getNotificationStats($this->testUserId1);
        
        echo "✓ Retrieved notification statistics\n";
        echo "  - Total: " . $stats['total'] . "\n";
        echo "  - Unread: " . $stats['unread'] . "\n";
        echo "  - Follows: " . $stats['follows'] . "\n";
        echo "  - Claps: " . $stats['claps'] . "\n";
        echo "  - Comments: " . $stats['comments'] . "\n";
        echo "  - Invites: " . $stats['invites'] . "\n";
        
        // Test cleanup
        $cleanupResult = $this->notificationModel->cleanupOldNotifications();
        if ($cleanupResult['success']) {
            echo "✓ Cleanup test completed\n";
            echo "  - Message: " . $cleanupResult['message'] . "\n";
        } else {
            echo "✗ Cleanup test failed: " . $cleanupResult['error'] . "\n";
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new NotificationSystemTest();
    $test->runTests();
}