<?php

/**
 * Engagement Features Validation Test Suite
 * Focuses on validation, security, and edge cases for engagement features
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */

require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/database.php';

class EngagementValidationTest {
    private $clapModel;
    private $commentModel;
    private $bookmarkModel;
    private $followModel;
    private $notificationModel;
    
    public function __construct() {
        $this->clapModel = new Clap();
        $this->commentModel = new Comment();
        $this->bookmarkModel = new Bookmark();
        $this->followModel = new Follow();
        $this->notificationModel = new Notification();
    }
    
    public function runTests() {
        echo "Running Engagement Validation Tests...\n\n";
        
        $this->testClapValidation();
        $this->testCommentValidation();
        $this->testBookmarkValidation();
        $this->testFollowValidation();
        $this->testNotificationValidation();
        $this->testSecurityScenarios();
        $this->testPerformanceEdgeCases();
        
        echo "\nAll engagement validation tests completed!\n";
    }
    
    private function testClapValidation() {
        echo "=== CLAP VALIDATION TESTS ===\n\n";
        
        // Test SQL injection attempts
        echo "Testing clap SQL injection protection...\n";
        $result = $this->clapModel->addClap("1; DROP TABLE claps; --", 1, 1);
        if (!$result['success']) {
            echo "✓ SQL injection attempt blocked\n";
        } else {
            echo "✗ SQL injection vulnerability detected\n";
        }
        
        // Test extremely large clap counts
        echo "Testing extreme clap count handling...\n";
        $result = $this->clapModel->addClap(1, 1, PHP_INT_MAX);
        if ($result['success'] && $result['data']['count'] <= 50) {
            echo "✓ Extreme clap count properly limited\n";
        } else {
            echo "✗ Extreme clap count not handled properly\n";
        }
        
        // Test negative user/article IDs
        echo "Testing negative ID handling...\n";
        $result1 = $this->clapModel->addClap(-1, 1, 1);
        $result2 = $this->clapModel->addClap(1, -1, 1);
        if (!$result1['success'] && !$result2['success']) {
            echo "✓ Negative IDs properly rejected\n";
        } else {
            echo "✗ Negative IDs not properly validated\n";
        }
        
        // Test concurrent clap additions
        echo "Testing concurrent clap handling...\n";
        $this->clapModel->removeClap(1, 1);
        $result1 = $this->clapModel->addClap(1, 1, 25);
        $result2 = $this->clapModel->addClap(1, 1, 30);
        if ($result1['success'] && $result2['success']) {
            $finalCount = $this->clapModel->getUserClapCount(1, 1);
            if ($finalCount == 50) {
                echo "✓ Concurrent claps properly limited to 50\n";
            } else {
                echo "✗ Concurrent claps not properly handled: $finalCount\n";
            }
        }
        
        echo "\n";
    }
    
    private function testCommentValidation() {
        echo "=== COMMENT VALIDATION TESTS ===\n\n";
        
        // Test XSS prevention
        echo "Testing comment XSS protection...\n";
        $xssContent = "<script>alert('XSS')</script>Malicious comment";
        $result = $this->commentModel->createComment(1, 1, $xssContent);
        if ($result['success']) {
            $content = $result['data']['content'];
            if (strpos($content, '<script>') === false) {
                echo "✓ XSS content properly sanitized\n";
            } else {
                echo "✗ XSS vulnerability detected in comments\n";
            }
        }
        
        // Test extremely long comments
        echo "Testing comment length limits...\n";
        $longComment = str_repeat("A", 10000);
        $result = $this->commentModel->createComment(1, 1, $longComment);
        if (!$result['success'] || strlen($result['data']['content']) <= 5000) {
            echo "✓ Long comments properly handled\n";
        } else {
            echo "✗ Comment length not properly limited\n";
        }
        
        // Test null/empty content variations
        echo "Testing null/empty comment validation...\n";
        $emptyTests = [null, "", "   ", "\n\n\n", "\t\t"];
        $allRejected = true;
        
        foreach ($emptyTests as $content) {
            $result = $this->commentModel->createComment(1, 1, $content);
            if ($result['success']) {
                $allRejected = false;
                break;
            }
        }
        
        if ($allRejected) {
            echo "✓ Empty/null comments properly rejected\n";
        } else {
            echo "✗ Empty/null comment validation failed\n";
        }
        
        // Test comment nesting depth validation
        echo "Testing comment nesting depth validation...\n";
        $comment1 = $this->commentModel->createComment(1, 1, "Level 1 comment");
        if ($comment1['success']) {
            $comment2 = $this->commentModel->createComment(1, 1, "Level 2 comment", $comment1['data']['id']);
            if ($comment2['success']) {
                $comment3 = $this->commentModel->createComment(1, 1, "Level 3 comment", $comment2['data']['id']);
                if ($comment3['success']) {
                    $comment4 = $this->commentModel->createComment(1, 1, "Level 4 comment", $comment3['data']['id']);
                    if (!$comment4['success']) {
                        echo "✓ Comment nesting depth properly limited\n";
                    } else {
                        echo "✗ Comment nesting depth not properly limited\n";
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    private function testBookmarkValidation() {
        echo "=== BOOKMARK VALIDATION TESTS ===\n\n";
        
        // Test bookmark race conditions
        echo "Testing bookmark race condition handling...\n";
        $this->bookmarkModel->removeBookmark(1, 1);
        
        // Simulate concurrent bookmark attempts
        $result1 = $this->bookmarkModel->addBookmark(1, 1);
        $result2 = $this->bookmarkModel->addBookmark(1, 1);
        
        if ($result1['success'] && !$result2['success']) {
            echo "✓ Bookmark race condition properly handled\n";
        } else {
            echo "✗ Bookmark race condition not properly handled\n";
        }
        
        // Test bookmark with non-existent article
        echo "Testing bookmark with non-existent article...\n";
        $result = $this->bookmarkModel->addBookmark(1, 999999);
        if (!$result['success']) {
            echo "✓ Non-existent article bookmark properly rejected\n";
        } else {
            echo "✗ Non-existent article bookmark not properly validated\n";
        }
        
        // Test bookmark with non-existent user
        echo "Testing bookmark with non-existent user...\n";
        $result = $this->bookmarkModel->addBookmark(999999, 1);
        if (!$result['success']) {
            echo "✓ Non-existent user bookmark properly rejected\n";
        } else {
            echo "✗ Non-existent user bookmark not properly validated\n";
        }
        
        echo "\n";
    }
    
    private function testFollowValidation() {
        echo "=== FOLLOW VALIDATION TESTS ===\n\n";
        
        // Test follow with same user ID (self-follow)
        echo "Testing self-follow prevention...\n";
        $result = $this->followModel->followUser(1, 1);
        if (!$result['success']) {
            echo "✓ Self-follow properly prevented\n";
        } else {
            echo "✗ Self-follow not properly prevented\n";
        }
        
        // Test follow race conditions
        echo "Testing follow race condition handling...\n";
        $this->followModel->unfollowUser(1, 2);
        
        $result1 = $this->followModel->followUser(1, 2);
        $result2 = $this->followModel->followUser(1, 2);
        
        if ($result1['success'] && !$result2['success']) {
            echo "✓ Follow race condition properly handled\n";
        } else {
            echo "✗ Follow race condition not properly handled\n";
        }
        
        // Test follow with non-existent users
        echo "Testing follow with non-existent users...\n";
        $result1 = $this->followModel->followUser(999999, 1);
        $result2 = $this->followModel->followUser(1, 999999);
        
        if (!$result1['success'] && !$result2['success']) {
            echo "✓ Non-existent user follows properly rejected\n";
        } else {
            echo "✗ Non-existent user follows not properly validated\n";
        }
        
        // Test circular follow relationships
        echo "Testing circular follow relationships...\n";
        $this->followModel->followUser(1, 2);
        $this->followModel->followUser(2, 3);
        $result = $this->followModel->followUser(3, 1);
        
        // Circular follows should be allowed (not a technical constraint)
        if ($result['success']) {
            echo "✓ Circular follow relationships allowed (expected behavior)\n";
        } else {
            echo "? Circular follow relationships blocked (may be intentional)\n";
        }
        
        echo "\n";
    }
    
    private function testNotificationValidation() {
        echo "=== NOTIFICATION VALIDATION TESTS ===\n\n";
        
        // Test notification content sanitization
        echo "Testing notification content sanitization...\n";
        $maliciousContent = "<script>alert('XSS')</script>Notification content";
        $result = $this->notificationModel->createNotification(1, 'test', $maliciousContent);
        
        if ($result['success']) {
            $content = $result['data']['content'];
            if (strpos($content, '<script>') === false) {
                echo "✓ Notification content properly sanitized\n";
            } else {
                echo "✗ Notification XSS vulnerability detected\n";
            }
        }
        
        // Test notification type validation
        echo "Testing notification type validation...\n";
        $invalidTypes = ['invalid_type', '', null, 123, '<script>'];
        $allRejected = true;
        
        foreach ($invalidTypes as $type) {
            $result = $this->notificationModel->createNotification(1, $type, 'Test content');
            if ($result['success']) {
                $allRejected = false;
                break;
            }
        }
        
        if ($allRejected) {
            echo "✓ Invalid notification types properly rejected\n";
        } else {
            echo "✗ Invalid notification type validation failed\n";
        }
        
        // Test notification spam prevention
        echo "Testing notification spam prevention...\n";
        $spamCount = 0;
        for ($i = 0; $i < 100; $i++) {
            $result = $this->notificationModel->createNotification(1, 'test', "Spam notification $i");
            if ($result['success']) {
                $spamCount++;
            }
        }
        
        if ($spamCount < 100) {
            echo "✓ Notification spam prevention active (created $spamCount/100)\n";
        } else {
            echo "? No notification spam prevention detected (created $spamCount/100)\n";
        }
        
        echo "\n";
    }
    
    private function testSecurityScenarios() {
        echo "=== SECURITY SCENARIO TESTS ===\n\n";
        
        // Test authorization bypass attempts
        echo "Testing authorization bypass attempts...\n";
        
        // Try to delete another user's comment
        $comment = $this->commentModel->createComment(1, 1, "Test comment for security");
        if ($comment['success']) {
            $result = $this->commentModel->deleteComment($comment['data']['id'], 2); // Different user
            if (!$result['success']) {
                echo "✓ Comment deletion authorization properly enforced\n";
            } else {
                echo "✗ Comment deletion authorization bypass detected\n";
            }
        }
        
        // Try to mark another user's notification as read
        $notification = $this->notificationModel->createNotification(1, 'test', 'Security test');
        if ($notification['success']) {
            $result = $this->notificationModel->markAsRead($notification['data']['id'], 2); // Different user
            if (!$result['success']) {
                echo "✓ Notification authorization properly enforced\n";
            } else {
                echo "✗ Notification authorization bypass detected\n";
            }
        }
        
        // Test parameter tampering
        echo "Testing parameter tampering protection...\n";
        
        // Try to clap with manipulated parameters
        $result = $this->clapModel->addClap("1 OR 1=1", 1, 1);
        if (!$result['success']) {
            echo "✓ Parameter tampering properly blocked\n";
        } else {
            echo "✗ Parameter tampering vulnerability detected\n";
        }
        
        echo "\n";
    }
    
    private function testPerformanceEdgeCases() {
        echo "=== PERFORMANCE EDGE CASE TESTS ===\n\n";
        
        // Test large result set handling
        echo "Testing large result set handling...\n";
        
        // Get notifications with very large limit
        $notifications = $this->notificationModel->getUserNotifications(1, false, 10000, 0);
        if (is_array($notifications) && count($notifications) <= 1000) {
            echo "✓ Large result sets properly limited\n";
        } else {
            echo "? Large result set handling may need optimization\n";
        }
        
        // Test pagination edge cases
        echo "Testing pagination edge cases...\n";
        
        // Test negative offset
        $bookmarks = $this->bookmarkModel->getUserBookmarks(1, 10, -1);
        if (is_array($bookmarks)) {
            echo "✓ Negative pagination offset handled\n";
        } else {
            echo "✗ Negative pagination offset caused error\n";
        }
        
        // Test zero limit
        $bookmarks = $this->bookmarkModel->getUserBookmarks(1, 0, 0);
        if (is_array($bookmarks) && count($bookmarks) == 0) {
            echo "✓ Zero pagination limit handled\n";
        } else {
            echo "? Zero pagination limit handling may need review\n";
        }
        
        // Test very large offset
        $bookmarks = $this->bookmarkModel->getUserBookmarks(1, 10, 1000000);
        if (is_array($bookmarks)) {
            echo "✓ Large pagination offset handled\n";
        } else {
            echo "✗ Large pagination offset caused error\n";
        }
        
        echo "\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new EngagementValidationTest();
    $test->runTests();
}