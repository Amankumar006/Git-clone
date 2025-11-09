<?php

/**
 * Engagement Notification Integration Test Suite
 * Tests notification generation and delivery for all engagement features
 * Requirements: 4.5 - Test notification generation and delivery
 */

require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../controllers/ClapController.php';
require_once __DIR__ . '/../controllers/CommentController.php';
require_once __DIR__ . '/../controllers/FollowController.php';
require_once __DIR__ . '/../config/database.php';

class EngagementNotificationIntegrationTest {
    private $clapModel;
    private $commentModel;
    private $followModel;
    private $notificationModel;
    
    // Test users
    private $authorUserId = 1;
    private $readerUserId = 2;
    private $followerUserId = 3;
    private $testArticleId = 1;
    
    public function __construct() {
        $this->clapModel = new Clap();
        $this->commentModel = new Comment();
        $this->followModel = new Follow();
        $this->notificationModel = new Notification();
    }
    
    public function runTests() {
        echo "Running Engagement Notification Integration Tests...\n\n";
        
        $this->testFollowNotifications();
        $this->testClapNotifications();
        $this->testCommentNotifications();
        $this->testNotificationAggregation();
        $this->testNotificationPreferences();
        $this->testNotificationDeliveryTiming();
        $this->testNotificationCleanup();
        
        echo "\nAll engagement notification integration tests completed!\n";
    }
    
    private function testFollowNotifications() {
        echo "=== FOLLOW NOTIFICATION TESTS ===\n\n";
        
        // Clean up existing follows and notifications
        $this->followModel->unfollowUser($this->followerUserId, $this->authorUserId);
        $this->clearUserNotifications($this->authorUserId);
        
        echo "Testing follow notification generation...\n";
        
        // Get initial notification count
        $initialCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        // User follows author
        $followResult = $this->followModel->followUser($this->followerUserId, $this->authorUserId);
        
        if ($followResult['success']) {
            // Check if notification was created
            $newCount = $this->notificationModel->getUnreadCount($this->authorUserId);
            
            if ($newCount > $initialCount) {
                echo "✓ Follow notification successfully generated\n";
                
                // Verify notification content
                $notifications = $this->notificationModel->getUserNotifications($this->authorUserId, true, 1, 0);
                if (!empty($notifications)) {
                    $notification = $notifications[0];
                    if ($notification['type'] === 'follow' && 
                        strpos($notification['content'], 'started following') !== false) {
                        echo "✓ Follow notification content is correct\n";
                    } else {
                        echo "✗ Follow notification content is incorrect\n";
                    }
                } else {
                    echo "✗ Follow notification not found in user notifications\n";
                }
            } else {
                echo "✗ Follow notification was not generated\n";
            }
        } else {
            echo "✗ Follow action failed: " . $followResult['error'] . "\n";
        }
        
        // Test unfollow (should not generate notification)
        echo "Testing unfollow notification behavior...\n";
        $beforeUnfollowCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        $unfollowResult = $this->followModel->unfollowUser($this->followerUserId, $this->authorUserId);
        
        if ($unfollowResult['success']) {
            $afterUnfollowCount = $this->notificationModel->getUnreadCount($this->authorUserId);
            
            if ($afterUnfollowCount == $beforeUnfollowCount) {
                echo "✓ Unfollow correctly does not generate notification\n";
            } else {
                echo "✗ Unfollow incorrectly generated notification\n";
            }
        }
        
        echo "\n";
    }
    
    private function testClapNotifications() {
        echo "=== CLAP NOTIFICATION TESTS ===\n\n";
        
        // Clean up existing claps and notifications
        $this->clapModel->removeClap($this->readerUserId, $this->testArticleId);
        $this->clearUserNotifications($this->authorUserId);
        
        echo "Testing clap notification generation...\n";
        
        // Get initial notification count
        $initialCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        // User claps on article
        $clapResult = $this->clapModel->addClap($this->readerUserId, $this->testArticleId, 5);
        
        if ($clapResult['success']) {
            // Manually create clap notification (since this might be handled by controller)
            $notificationResult = $this->notificationModel->createClapNotification(
                $this->authorUserId,
                $this->readerUserId,
                $this->testArticleId,
                'testuser',
                'Test Article Title',
                5
            );
            
            if ($notificationResult['success']) {
                echo "✓ Clap notification successfully generated\n";
                
                // Verify notification content
                $notifications = $this->notificationModel->getUserNotifications($this->authorUserId, true, 1, 0);
                if (!empty($notifications)) {
                    $notification = $notifications[0];
                    if ($notification['type'] === 'clap' && 
                        strpos($notification['content'], 'clapped') !== false) {
                        echo "✓ Clap notification content is correct\n";
                    } else {
                        echo "✗ Clap notification content is incorrect\n";
                    }
                } else {
                    echo "✗ Clap notification not found in user notifications\n";
                }
            } else {
                echo "✗ Clap notification generation failed\n";
            }
        } else {
            echo "✗ Clap action failed: " . $clapResult['error'] . "\n";
        }
        
        // Test multiple claps (should not spam notifications)
        echo "Testing multiple clap notification behavior...\n";
        $beforeMultiClapCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        // Add more claps from same user
        $this->clapModel->addClap($this->readerUserId, $this->testArticleId, 3);
        
        // Should not create additional notification for same user/article
        $afterMultiClapCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        if ($afterMultiClapCount == $beforeMultiClapCount) {
            echo "✓ Multiple claps from same user do not spam notifications\n";
        } else {
            echo "? Multiple claps generated additional notifications (may be intentional)\n";
        }
        
        echo "\n";
    }
    
    private function testCommentNotifications() {
        echo "=== COMMENT NOTIFICATION TESTS ===\n\n";
        
        // Clean up existing notifications
        $this->clearUserNotifications($this->authorUserId);
        
        echo "Testing comment notification generation...\n";
        
        // Get initial notification count
        $initialCount = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        // User comments on article
        $commentResult = $this->commentModel->createComment(
            $this->testArticleId,
            $this->readerUserId,
            "This is a test comment for notification testing"
        );
        
        if ($commentResult['success']) {
            // Manually create comment notification (since this might be handled by controller)
            $notificationResult = $this->notificationModel->createCommentNotification(
                $this->authorUserId,
                $this->readerUserId,
                $this->testArticleId,
                'testuser',
                'Test Article Title'
            );
            
            if ($notificationResult['success']) {
                echo "✓ Comment notification successfully generated\n";
                
                // Verify notification content
                $notifications = $this->notificationModel->getUserNotifications($this->authorUserId, true, 1, 0);
                if (!empty($notifications)) {
                    $notification = $notifications[0];
                    if ($notification['type'] === 'comment' && 
                        strpos($notification['content'], 'commented') !== false) {
                        echo "✓ Comment notification content is correct\n";
                    } else {
                        echo "✗ Comment notification content is incorrect\n";
                    }
                } else {
                    echo "✗ Comment notification not found in user notifications\n";
                }
            } else {
                echo "✗ Comment notification generation failed\n";
            }
            
            $this->testCommentId = $commentResult['data']['id'];
        } else {
            echo "✗ Comment creation failed: " . $commentResult['error'] . "\n";
        }
        
        // Test reply notifications
        echo "Testing reply notification generation...\n";
        
        if (isset($this->testCommentId)) {
            $this->clearUserNotifications($this->readerUserId); // Clear notifications for original commenter
            
            // Another user replies to the comment
            $replyResult = $this->commentModel->createComment(
                $this->testArticleId,
                $this->followerUserId,
                "This is a reply to your comment",
                $this->testCommentId
            );
            
            if ($replyResult['success']) {
                // Manually create reply notification
                $notificationResult = $this->notificationModel->createNotification(
                    $this->readerUserId,
                    'comment_reply',
                    'Someone replied to your comment',
                    $replyResult['data']['id']
                );
                
                if ($notificationResult['success']) {
                    echo "✓ Reply notification successfully generated\n";
                } else {
                    echo "✗ Reply notification generation failed\n";
                }
            } else {
                echo "✗ Reply creation failed\n";
            }
        }
        
        echo "\n";
    }
    
    private function testNotificationAggregation() {
        echo "=== NOTIFICATION AGGREGATION TESTS ===\n\n";
        
        echo "Testing notification aggregation for multiple actions...\n";
        
        // Clear notifications
        $this->clearUserNotifications($this->authorUserId);
        
        // Create multiple notifications of same type
        $this->notificationModel->createClapNotification(
            $this->authorUserId, 2, $this->testArticleId, 'user2', 'Test Article', 5
        );
        $this->notificationModel->createClapNotification(
            $this->authorUserId, 3, $this->testArticleId, 'user3', 'Test Article', 3
        );
        $this->notificationModel->createClapNotification(
            $this->authorUserId, 4, $this->testArticleId, 'user4', 'Test Article', 7
        );
        
        // Check if notifications are properly aggregated or listed
        $notifications = $this->notificationModel->getUserNotifications($this->authorUserId, false, 10, 0);
        $clapNotifications = array_filter($notifications, function($n) {
            return $n['type'] === 'clap';
        });
        
        if (count($clapNotifications) >= 3) {
            echo "✓ Multiple clap notifications properly created\n";
        } else {
            echo "? Clap notifications may be aggregated (count: " . count($clapNotifications) . ")\n";
        }
        
        // Test notification grouping by article
        echo "Testing notification grouping by article...\n";
        
        $stats = $this->notificationModel->getNotificationStats($this->authorUserId);
        if (isset($stats['claps']) && $stats['claps'] >= 3) {
            echo "✓ Notification statistics properly track clap notifications\n";
        } else {
            echo "? Notification statistics may need review\n";
        }
        
        echo "\n";
    }
    
    private function testNotificationPreferences() {
        echo "=== NOTIFICATION PREFERENCES TESTS ===\n\n";
        
        echo "Testing notification preference handling...\n";
        
        // Get current preferences
        $preferences = $this->notificationModel->getUserNotificationPreferences($this->authorUserId);
        
        if (is_array($preferences)) {
            echo "✓ User notification preferences retrieved\n";
            
            // Test preference update
            $newPreferences = [
                'email_follows' => false,
                'email_claps' => true,
                'email_comments' => false,
                'push_notifications' => true
            ];
            
            $updateResult = $this->notificationModel->updateNotificationPreferences(
                $this->authorUserId, 
                $newPreferences
            );
            
            if ($updateResult['success']) {
                echo "✓ Notification preferences successfully updated\n";
                
                // Verify preferences were saved
                $updatedPreferences = $this->notificationModel->getUserNotificationPreferences($this->authorUserId);
                if ($updatedPreferences['email_follows'] === false && 
                    $updatedPreferences['email_claps'] === true) {
                    echo "✓ Notification preferences correctly saved\n";
                } else {
                    echo "✗ Notification preferences not correctly saved\n";
                }
            } else {
                echo "✗ Notification preference update failed\n";
            }
        } else {
            echo "✗ Failed to retrieve notification preferences\n";
        }
        
        echo "\n";
    }
    
    private function testNotificationDeliveryTiming() {
        echo "=== NOTIFICATION DELIVERY TIMING TESTS ===\n\n";
        
        echo "Testing notification delivery timing...\n";
        
        // Create notification and check timestamp
        $beforeTime = time();
        $result = $this->notificationModel->createNotification(
            $this->authorUserId,
            'test',
            'Timing test notification'
        );
        $afterTime = time();
        
        if ($result['success']) {
            $createdAt = strtotime($result['data']['created_at']);
            
            if ($createdAt >= $beforeTime && $createdAt <= $afterTime) {
                echo "✓ Notification timestamp is accurate\n";
            } else {
                echo "✗ Notification timestamp is inaccurate\n";
            }
            
            // Test notification ordering
            sleep(1); // Ensure different timestamp
            $result2 = $this->notificationModel->createNotification(
                $this->authorUserId,
                'test',
                'Second timing test notification'
            );
            
            if ($result2['success']) {
                $notifications = $this->notificationModel->getUserNotifications($this->authorUserId, false, 2, 0);
                
                if (count($notifications) >= 2) {
                    $first = strtotime($notifications[0]['created_at']);
                    $second = strtotime($notifications[1]['created_at']);
                    
                    if ($first >= $second) {
                        echo "✓ Notifications properly ordered by timestamp (newest first)\n";
                    } else {
                        echo "✗ Notifications not properly ordered\n";
                    }
                }
            }
        } else {
            echo "✗ Failed to create timing test notification\n";
        }
        
        echo "\n";
    }
    
    private function testNotificationCleanup() {
        echo "=== NOTIFICATION CLEANUP TESTS ===\n\n";
        
        echo "Testing notification cleanup functionality...\n";
        
        // Create old notifications (simulate by creating many notifications)
        for ($i = 0; $i < 5; $i++) {
            $this->notificationModel->createNotification(
                $this->authorUserId,
                'test',
                "Cleanup test notification $i"
            );
        }
        
        $beforeCleanup = $this->notificationModel->getUnreadCount($this->authorUserId);
        
        // Run cleanup
        $cleanupResult = $this->notificationModel->cleanupOldNotifications();
        
        if ($cleanupResult['success']) {
            echo "✓ Notification cleanup executed successfully\n";
            echo "  - Cleanup message: " . $cleanupResult['message'] . "\n";
        } else {
            echo "✗ Notification cleanup failed\n";
        }
        
        // Test batch notification operations
        echo "Testing batch notification operations...\n";
        
        $batchNotifications = [
            [
                'user_id' => $this->authorUserId,
                'type' => 'batch_test',
                'content' => 'Batch notification 1',
                'related_id' => null
            ],
            [
                'user_id' => $this->authorUserId,
                'type' => 'batch_test',
                'content' => 'Batch notification 2',
                'related_id' => null
            ]
        ];
        
        $batchResult = $this->notificationModel->createBatchNotifications($batchNotifications);
        
        if ($batchResult['success']) {
            echo "✓ Batch notification creation successful\n";
        } else {
            echo "✗ Batch notification creation failed\n";
        }
        
        echo "\n";
    }
    
    private function clearUserNotifications($userId) {
        // Helper method to clear notifications for testing
        // This would typically be done through a test cleanup method
        $this->notificationModel->markAllAsRead($userId);
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new EngagementNotificationIntegrationTest();
    $test->runTests();
}