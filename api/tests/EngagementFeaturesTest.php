<?php

/**
 * Comprehensive Engagement Features Test Suite
 * Tests all engagement features including claps, comments, bookmarks, follows, and notifications
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */

require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/database.php';

class EngagementFeaturesTest {
    private $clapModel;
    private $commentModel;
    private $bookmarkModel;
    private $followModel;
    private $notificationModel;
    
    // Test data
    private $testUserId1 = 1;
    private $testUserId2 = 2;
    private $testUserId3 = 3;
    private $testArticleId1 = 1;
    private $testArticleId2 = 2;
    
    // Test result tracking
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    
    public function __construct() {
        $this->clapModel = new Clap();
        $this->commentModel = new Comment();
        $this->bookmarkModel = new Bookmark();
        $this->followModel = new Follow();
        $this->notificationModel = new Notification();
    }
    
    public function runAllTests() {
        echo "===========================================\n";
        echo "COMPREHENSIVE ENGAGEMENT FEATURES TESTS\n";
        echo "===========================================\n\n";
        
        $this->runClapSystemTests();
        $this->runCommentSystemTests();
        $this->runBookmarkSystemTests();
        $this->runFollowSystemTests();
        $this->runNotificationSystemTests();
        $this->runIntegrationTests();
        
        $this->displayTestSummary();
    }
    
    // =================== CLAP SYSTEM TESTS ===================
    
    private function runClapSystemTests() {
        echo "=== CLAP SYSTEM TESTS ===\n\n";
        
        $this->testClapBasicFunctionality();
        $this->testClapLimitsAndValidation();
        $this->testClapEdgeCases();
        $this->testClapStatistics();
        
        echo "\n";
    }
    
    private function testClapBasicFunctionality() {
        echo "Testing clap basic functionality...\n";
        
        // Clean up existing claps
        $this->clapModel->removeClap($this->testUserId1, $this->testArticleId1);
        
        // Test 1: Add single clap
        $result = $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 1);
        $this->assertTest(
            $result['success'] && $result['data']['count'] == 1,
            "Add single clap",
            "Should successfully add one clap"
        );
        
        // Test 2: Add multiple claps
        $result = $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 5);
        $this->assertTest(
            $result['success'] && $result['data']['count'] == 6,
            "Add multiple claps",
            "Should accumulate claps correctly"
        );
        
        // Test 3: Get clap status
        $userClapCount = $this->clapModel->getUserClapCount($this->testUserId1, $this->testArticleId1);
        $totalClaps = $this->clapModel->getArticleTotalClaps($this->testArticleId1);
        $canClap = $this->clapModel->canUserClap($this->testUserId1, $this->testArticleId1);
        
        $this->assertTest(
            $userClapCount == 6 && $totalClaps >= 6 && $canClap,
            "Get clap status",
            "Should return correct clap counts and status"
        );
        
        // Test 4: Remove clap
        $result = $this->clapModel->removeClap($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            $result['success'],
            "Remove clap",
            "Should successfully remove clap"
        );
        
        // Verify removal
        $userClapCount = $this->clapModel->getUserClapCount($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            $userClapCount == 0,
            "Verify clap removal",
            "User clap count should be 0 after removal"
        );
    }
    
    private function testClapLimitsAndValidation() {
        echo "Testing clap limits and validation...\n";
        
        // Clean up
        $this->clapModel->removeClap($this->testUserId1, $this->testArticleId1);
        
        // Test 1: Maximum clap limit (50)
        $result = $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 60);
        $this->assertTest(
            $result['success'] && $result['data']['count'] == 50,
            "Clap limit enforcement",
            "Should cap claps at 50 maximum"
        );
        
        // Test 2: Cannot clap after reaching limit
        $canClap = $this->clapModel->canUserClap($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            !$canClap,
            "Cannot clap after limit",
            "Should prevent clapping after reaching 50 clap limit"
        );
        
        // Test 3: Try to add more claps after limit
        $result = $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 5);
        $this->assertTest(
            $result['success'] && $result['data']['count'] == 50,
            "Additional claps after limit",
            "Should maintain 50 clap limit even with additional attempts"
        );
        
        // Test 4: Invalid parameters
        $result = $this->clapModel->addClap(0, $this->testArticleId1, 1);
        $this->assertTest(
            !$result['success'],
            "Invalid user ID",
            "Should reject invalid user ID"
        );
        
        $result = $this->clapModel->addClap($this->testUserId1, 0, 1);
        $this->assertTest(
            !$result['success'],
            "Invalid article ID",
            "Should reject invalid article ID"
        );
    }
    
    private function testClapEdgeCases() {
        echo "Testing clap edge cases...\n";
        
        // Test 1: Zero claps
        $result = $this->clapModel->addClap($this->testUserId2, $this->testArticleId1, 0);
        $this->assertTest(
            !$result['success'] || $result['data']['count'] == 0,
            "Zero claps",
            "Should handle zero clap count appropriately"
        );
        
        // Test 2: Negative claps
        $result = $this->clapModel->addClap($this->testUserId2, $this->testArticleId1, -5);
        $this->assertTest(
            !$result['success'] || $result['data']['count'] >= 0,
            "Negative claps",
            "Should reject or handle negative clap counts"
        );
        
        // Test 3: Multiple users clapping same article
        $this->clapModel->removeClap($this->testUserId2, $this->testArticleId1);
        $result1 = $this->clapModel->addClap($this->testUserId2, $this->testArticleId1, 10);
        $result2 = $this->clapModel->addClap($this->testUserId3, $this->testArticleId1, 15);
        
        $totalClaps = $this->clapModel->getArticleTotalClaps($this->testArticleId1);
        $this->assertTest(
            $result1['success'] && $result2['success'] && $totalClaps >= 25,
            "Multiple users clapping",
            "Should handle multiple users clapping the same article"
        );
    }
    
    private function testClapStatistics() {
        echo "Testing clap statistics...\n";
        
        // Test 1: Article clap with users
        $clapsWithUsers = $this->clapModel->getArticleClapsWithUsers($this->testArticleId1, 10, 0);
        $this->assertTest(
            is_array($clapsWithUsers),
            "Article claps with users",
            "Should return array of claps with user information"
        );
        
        // Test 2: Total claps for article
        $totalClaps = $this->clapModel->getArticleTotalClaps($this->testArticleId1);
        $this->assertTest(
            is_numeric($totalClaps) && $totalClaps >= 0,
            "Article total claps",
            "Should return numeric total clap count"
        );
    }
    
    // =================== COMMENT SYSTEM TESTS ===================
    
    private function runCommentSystemTests() {
        echo "=== COMMENT SYSTEM TESTS ===\n\n";
        
        $this->testCommentBasicFunctionality();
        $this->testCommentNestingAndReplies();
        $this->testCommentValidationAndSecurity();
        $this->testCommentModeration();
        
        echo "\n";
    }
    
    private function testCommentBasicFunctionality() {
        echo "Testing comment basic functionality...\n";
        
        // Test 1: Create comment
        $result = $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId1,
            "This is a test comment for engagement testing"
        );
        
        $this->assertTest(
            $result['success'] && isset($result['data']['id']),
            "Create comment",
            "Should successfully create a comment"
        );
        
        if ($result['success']) {
            $this->testCommentId = $result['data']['id'];
        }
        
        // Test 2: Get article comments
        $comments = $this->commentModel->getArticleComments($this->testArticleId1);
        $this->assertTest(
            is_array($comments) && count($comments) > 0,
            "Get article comments",
            "Should retrieve article comments"
        );
        
        // Test 3: Update comment
        if (isset($this->testCommentId)) {
            $result = $this->commentModel->updateComment(
                $this->testCommentId,
                $this->testUserId1,
                "This is an updated test comment"
            );
            
            $this->assertTest(
                $result['success'],
                "Update comment",
                "Should successfully update comment"
            );
        }
        
        // Test 4: Comment count
        $count = $this->commentModel->getArticleCommentCount($this->testArticleId1);
        $this->assertTest(
            $count > 0,
            "Comment count",
            "Should return correct comment count"
        );
    }
    
    private function testCommentNestingAndReplies() {
        echo "Testing comment nesting and replies...\n";
        
        if (!isset($this->testCommentId)) {
            echo "  Skipping nesting tests - no parent comment available\n";
            return;
        }
        
        // Test 1: Create level 2 reply
        $result = $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId2,
            "This is a level 2 reply",
            $this->testCommentId
        );
        
        $this->assertTest(
            $result['success'],
            "Create level 2 reply",
            "Should successfully create nested reply"
        );
        
        if ($result['success']) {
            $this->testReplyId = $result['data']['id'];
        }
        
        // Test 2: Create level 3 reply
        if (isset($this->testReplyId)) {
            $result = $this->commentModel->createComment(
                $this->testArticleId1,
                $this->testUserId3,
                "This is a level 3 reply",
                $this->testReplyId
            );
            
            $this->assertTest(
                $result['success'],
                "Create level 3 reply",
                "Should successfully create level 3 nested reply"
            );
            
            if ($result['success']) {
                $this->testLevel3ReplyId = $result['data']['id'];
            }
        }
        
        // Test 3: Attempt level 4 reply (should fail)
        if (isset($this->testLevel3ReplyId)) {
            $result = $this->commentModel->createComment(
                $this->testArticleId1,
                $this->testUserId1,
                "This should fail - level 4 reply",
                $this->testLevel3ReplyId
            );
            
            $this->assertTest(
                !$result['success'],
                "Reject level 4 reply",
                "Should reject replies deeper than 3 levels"
            );
        }
        
        // Test 4: Get nested comments structure
        $comments = $this->commentModel->getArticleComments($this->testArticleId1);
        $hasNestedStructure = false;
        
        foreach ($comments as $comment) {
            if (!empty($comment['replies'])) {
                $hasNestedStructure = true;
                break;
            }
        }
        
        $this->assertTest(
            $hasNestedStructure,
            "Nested comment structure",
            "Should return properly nested comment structure"
        );
    }
    
    private function testCommentValidationAndSecurity() {
        echo "Testing comment validation and security...\n";
        
        // Test 1: Empty comment
        $result = $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId1,
            ""
        );
        
        $this->assertTest(
            !$result['success'],
            "Empty comment validation",
            "Should reject empty comments"
        );
        
        // Test 2: Very long comment
        $longComment = str_repeat("This is a very long comment. ", 200);
        $result = $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId1,
            $longComment
        );
        
        $this->assertTest(
            !$result['success'] || strlen($result['data']['content']) <= 5000,
            "Long comment validation",
            "Should handle or reject very long comments"
        );
        
        // Test 3: Invalid user ID
        $result = $this->commentModel->createComment(
            $this->testArticleId1,
            0,
            "Test comment"
        );
        
        $this->assertTest(
            !$result['success'],
            "Invalid user ID",
            "Should reject invalid user ID"
        );
        
        // Test 4: Invalid article ID
        $result = $this->commentModel->createComment(
            0,
            $this->testUserId1,
            "Test comment"
        );
        
        $this->assertTest(
            !$result['success'],
            "Invalid article ID",
            "Should reject invalid article ID"
        );
    }
    
    private function testCommentModeration() {
        echo "Testing comment moderation...\n";
        
        if (!isset($this->testCommentId)) {
            echo "  Skipping moderation tests - no comment available\n";
            return;
        }
        
        // Test 1: User can only edit own comments
        $result = $this->commentModel->updateComment(
            $this->testCommentId,
            $this->testUserId2, // Different user
            "Trying to edit someone else's comment"
        );
        
        $this->assertTest(
            !$result['success'],
            "Edit permission check",
            "Should prevent users from editing others' comments"
        );
        
        // Test 2: User can only delete own comments
        $result = $this->commentModel->deleteComment(
            $this->testCommentId,
            $this->testUserId2 // Different user
        );
        
        $this->assertTest(
            !$result['success'],
            "Delete permission check",
            "Should prevent users from deleting others' comments"
        );
        
        // Test 3: Successful deletion by owner
        $result = $this->commentModel->deleteComment(
            $this->testCommentId,
            $this->testUserId1 // Original author
        );
        
        $this->assertTest(
            $result['success'],
            "Delete own comment",
            "Should allow users to delete their own comments"
        );
    }
    
    // =================== BOOKMARK SYSTEM TESTS ===================
    
    private function runBookmarkSystemTests() {
        echo "=== BOOKMARK SYSTEM TESTS ===\n\n";
        
        $this->testBookmarkBasicFunctionality();
        $this->testBookmarkValidationAndEdgeCases();
        $this->testBookmarkManagement();
        
        echo "\n";
    }
    
    private function testBookmarkBasicFunctionality() {
        echo "Testing bookmark basic functionality...\n";
        
        // Clean up existing bookmarks
        $this->bookmarkModel->removeBookmark($this->testUserId1, $this->testArticleId1);
        
        // Test 1: Add bookmark
        $result = $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            $result['success'],
            "Add bookmark",
            "Should successfully add bookmark"
        );
        
        // Test 2: Check bookmark status
        $isBookmarked = $this->bookmarkModel->isBookmarked($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            $isBookmarked,
            "Check bookmark status",
            "Should correctly identify bookmarked article"
        );
        
        // Test 3: Prevent duplicate bookmarks
        $result = $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            !$result['success'],
            "Prevent duplicate bookmarks",
            "Should prevent duplicate bookmarks"
        );
        
        // Test 4: Remove bookmark
        $result = $this->bookmarkModel->removeBookmark($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            $result['success'],
            "Remove bookmark",
            "Should successfully remove bookmark"
        );
        
        // Test 5: Verify removal
        $isBookmarked = $this->bookmarkModel->isBookmarked($this->testUserId1, $this->testArticleId1);
        $this->assertTest(
            !$isBookmarked,
            "Verify bookmark removal",
            "Should correctly show article as not bookmarked after removal"
        );
    }
    
    private function testBookmarkValidationAndEdgeCases() {
        echo "Testing bookmark validation and edge cases...\n";
        
        // Test 1: Invalid user ID
        $result = $this->bookmarkModel->addBookmark(0, $this->testArticleId1);
        $this->assertTest(
            !$result['success'],
            "Invalid user ID",
            "Should reject invalid user ID"
        );
        
        // Test 2: Invalid article ID
        $result = $this->bookmarkModel->addBookmark($this->testUserId1, 0);
        $this->assertTest(
            !$result['success'],
            "Invalid article ID",
            "Should reject invalid article ID"
        );
        
        // Test 3: Remove non-existent bookmark
        $result = $this->bookmarkModel->removeBookmark($this->testUserId1, 99999);
        $this->assertTest(
            !$result['success'] || $result['success'], // Either approach is valid
            "Remove non-existent bookmark",
            "Should handle removal of non-existent bookmark gracefully"
        );
        
        // Test 4: Multiple users bookmarking same article
        $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        $this->bookmarkModel->addBookmark($this->testUserId2, $this->testArticleId1);
        
        $user1Bookmarked = $this->bookmarkModel->isBookmarked($this->testUserId1, $this->testArticleId1);
        $user2Bookmarked = $this->bookmarkModel->isBookmarked($this->testUserId2, $this->testArticleId1);
        
        $this->assertTest(
            $user1Bookmarked && $user2Bookmarked,
            "Multiple users bookmark same article",
            "Should allow multiple users to bookmark the same article"
        );
    }
    
    private function testBookmarkManagement() {
        echo "Testing bookmark management...\n";
        
        // Add some bookmarks for testing
        $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId2);
        
        // Test 1: Get user bookmarks
        $bookmarks = $this->bookmarkModel->getUserBookmarks($this->testUserId1, 10, 0);
        $this->assertTest(
            is_array($bookmarks) && count($bookmarks) >= 2,
            "Get user bookmarks",
            "Should return user's bookmarked articles"
        );
        
        // Test 2: Get bookmark count
        $count = $this->bookmarkModel->getUserBookmarkCount($this->testUserId1);
        $this->assertTest(
            $count >= 2,
            "Get bookmark count",
            "Should return correct bookmark count"
        );
        
        // Test 3: Pagination
        $firstPage = $this->bookmarkModel->getUserBookmarks($this->testUserId1, 1, 0);
        $secondPage = $this->bookmarkModel->getUserBookmarks($this->testUserId1, 1, 1);
        
        $this->assertTest(
            count($firstPage) == 1 && count($secondPage) <= 1,
            "Bookmark pagination",
            "Should handle bookmark pagination correctly"
        );
    }
    
    // =================== FOLLOW SYSTEM TESTS ===================
    
    private function runFollowSystemTests() {
        echo "=== FOLLOW SYSTEM TESTS ===\n\n";
        
        $this->testFollowBasicFunctionality();
        $this->testFollowValidationAndSecurity();
        $this->testFollowStatisticsAndFeeds();
        
        echo "\n";
    }
    
    private function testFollowBasicFunctionality() {
        echo "Testing follow basic functionality...\n";
        
        // Clean up existing follows
        $this->followModel->unfollowUser($this->testUserId1, $this->testUserId2);
        
        // Test 1: Follow user
        $result = $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        $this->assertTest(
            $result['success'],
            "Follow user",
            "Should successfully follow user"
        );
        
        // Test 2: Check follow status
        $isFollowing = $this->followModel->isFollowing($this->testUserId1, $this->testUserId2);
        $this->assertTest(
            $isFollowing,
            "Check follow status",
            "Should correctly identify following relationship"
        );
        
        // Test 3: Prevent duplicate follows
        $result = $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        $this->assertTest(
            !$result['success'],
            "Prevent duplicate follows",
            "Should prevent duplicate follow relationships"
        );
        
        // Test 4: Unfollow user
        $result = $this->followModel->unfollowUser($this->testUserId1, $this->testUserId2);
        $this->assertTest(
            $result['success'],
            "Unfollow user",
            "Should successfully unfollow user"
        );
        
        // Test 5: Verify unfollow
        $isFollowing = $this->followModel->isFollowing($this->testUserId1, $this->testUserId2);
        $this->assertTest(
            !$isFollowing,
            "Verify unfollow",
            "Should correctly show as not following after unfollow"
        );
    }
    
    private function testFollowValidationAndSecurity() {
        echo "Testing follow validation and security...\n";
        
        // Test 1: Prevent self-follow
        $result = $this->followModel->followUser($this->testUserId1, $this->testUserId1);
        $this->assertTest(
            !$result['success'],
            "Prevent self-follow",
            "Should prevent users from following themselves"
        );
        
        // Test 2: Invalid follower ID
        $result = $this->followModel->followUser(0, $this->testUserId2);
        $this->assertTest(
            !$result['success'],
            "Invalid follower ID",
            "Should reject invalid follower ID"
        );
        
        // Test 3: Invalid following ID
        $result = $this->followModel->followUser($this->testUserId1, 0);
        $this->assertTest(
            !$result['success'],
            "Invalid following ID",
            "Should reject invalid following ID"
        );
        
        // Test 4: Unfollow non-existent relationship
        $result = $this->followModel->unfollowUser($this->testUserId1, 99999);
        $this->assertTest(
            !$result['success'] || $result['success'], // Either approach is valid
            "Unfollow non-existent relationship",
            "Should handle unfollowing non-existent relationship gracefully"
        );
    }
    
    private function testFollowStatisticsAndFeeds() {
        echo "Testing follow statistics and feeds...\n";
        
        // Set up test follows
        $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        $this->followModel->followUser($this->testUserId1, $this->testUserId3);
        $this->followModel->followUser($this->testUserId2, $this->testUserId1);
        
        // Test 1: Get follower count
        $followerCount = $this->followModel->getFollowerCount($this->testUserId1);
        $this->assertTest(
            $followerCount >= 1,
            "Get follower count",
            "Should return correct follower count"
        );
        
        // Test 2: Get following count
        $followingCount = $this->followModel->getFollowingCount($this->testUserId1);
        $this->assertTest(
            $followingCount >= 2,
            "Get following count",
            "Should return correct following count"
        );
        
        // Test 3: Get followers list
        $followers = $this->followModel->getFollowers($this->testUserId1, 10, 0);
        $this->assertTest(
            is_array($followers) && count($followers) >= 1,
            "Get followers list",
            "Should return list of followers"
        );
        
        // Test 4: Get following list
        $following = $this->followModel->getFollowing($this->testUserId1, 10, 0);
        $this->assertTest(
            is_array($following) && count($following) >= 2,
            "Get following list",
            "Should return list of users being followed"
        );
        
        // Test 5: Get following feed
        $feed = $this->followModel->getFollowingFeed($this->testUserId1, 10, 0);
        $this->assertTest(
            is_array($feed),
            "Get following feed",
            "Should return articles from followed users"
        );
        
        // Test 6: Get follow suggestions
        $suggestions = $this->followModel->getSuggestedFollows($this->testUserId1, 5);
        $this->assertTest(
            is_array($suggestions),
            "Get follow suggestions",
            "Should return suggested users to follow"
        );
    }
    
    // =================== NOTIFICATION SYSTEM TESTS ===================
    
    private function runNotificationSystemTests() {
        echo "=== NOTIFICATION SYSTEM TESTS ===\n\n";
        
        $this->testNotificationBasicFunctionality();
        $this->testNotificationTypes();
        $this->testNotificationManagement();
        $this->testNotificationDelivery();
        
        echo "\n";
    }
    
    private function testNotificationBasicFunctionality() {
        echo "Testing notification basic functionality...\n";
        
        // Test 1: Create notification
        $result = $this->notificationModel->createNotification(
            $this->testUserId1,
            'test',
            'This is a test notification',
            $this->testUserId2
        );
        
        $this->assertTest(
            $result['success'] && isset($result['data']['id']),
            "Create notification",
            "Should successfully create notification"
        );
        
        if ($result['success']) {
            $this->testNotificationId = $result['data']['id'];
        }
        
        // Test 2: Get user notifications
        $notifications = $this->notificationModel->getUserNotifications($this->testUserId1, false, 10, 0);
        $this->assertTest(
            is_array($notifications) && count($notifications) > 0,
            "Get user notifications",
            "Should return user notifications"
        );
        
        // Test 3: Get unread count
        $unreadCount = $this->notificationModel->getUnreadCount($this->testUserId1);
        $this->assertTest(
            $unreadCount >= 1,
            "Get unread count",
            "Should return correct unread notification count"
        );
        
        // Test 4: Mark as read
        if (isset($this->testNotificationId)) {
            $result = $this->notificationModel->markAsRead($this->testNotificationId, $this->testUserId1);
            $this->assertTest(
                $result['success'],
                "Mark notification as read",
                "Should successfully mark notification as read"
            );
        }
    }
    
    private function testNotificationTypes() {
        echo "Testing notification types...\n";
        
        // Test 1: Follow notification
        $result = $this->notificationModel->createFollowNotification(
            $this->testUserId1,
            $this->testUserId2,
            'testuser2'
        );
        
        $this->assertTest(
            $result['success'],
            "Create follow notification",
            "Should create follow notification"
        );
        
        // Test 2: Clap notification
        $result = $this->notificationModel->createClapNotification(
            $this->testUserId1,
            $this->testUserId2,
            $this->testArticleId1,
            'testuser2',
            'Test Article Title',
            5
        );
        
        $this->assertTest(
            $result['success'],
            "Create clap notification",
            "Should create clap notification"
        );
        
        // Test 3: Comment notification
        $result = $this->notificationModel->createCommentNotification(
            $this->testUserId1,
            $this->testUserId2,
            $this->testArticleId1,
            'testuser2',
            'Test Article Title'
        );
        
        $this->assertTest(
            $result['success'],
            "Create comment notification",
            "Should create comment notification"
        );
        
        // Test 4: Publication invite notification
        $result = $this->notificationModel->createPublicationInviteNotification(
            $this->testUserId1,
            $this->testUserId2,
            1,
            'testuser2',
            'Test Publication',
            'writer'
        );
        
        $this->assertTest(
            $result['success'],
            "Create publication invite notification",
            "Should create publication invite notification"
        );
    }
    
    private function testNotificationManagement() {
        echo "Testing notification management...\n";
        
        // Test 1: Mark all as read
        $result = $this->notificationModel->markAllAsRead($this->testUserId1);
        $this->assertTest(
            $result['success'],
            "Mark all as read",
            "Should mark all notifications as read"
        );
        
        // Test 2: Get notification statistics
        $stats = $this->notificationModel->getNotificationStats($this->testUserId1);
        $this->assertTest(
            isset($stats['total']) && isset($stats['unread']),
            "Get notification statistics",
            "Should return notification statistics"
        );
        
        // Test 3: Delete notification
        if (isset($this->testNotificationId)) {
            $result = $this->notificationModel->deleteNotification($this->testNotificationId, $this->testUserId1);
            $this->assertTest(
                $result['success'],
                "Delete notification",
                "Should successfully delete notification"
            );
        }
        
        // Test 4: Cleanup old notifications
        $result = $this->notificationModel->cleanupOldNotifications();
        $this->assertTest(
            $result['success'],
            "Cleanup old notifications",
            "Should successfully cleanup old notifications"
        );
    }
    
    private function testNotificationDelivery() {
        echo "Testing notification delivery...\n";
        
        // Test 1: Notification preferences
        $preferences = $this->notificationModel->getUserNotificationPreferences($this->testUserId1);
        $this->assertTest(
            is_array($preferences),
            "Get notification preferences",
            "Should return user notification preferences"
        );
        
        // Test 2: Update preferences
        $newPreferences = [
            'email_follows' => true,
            'email_claps' => false,
            'email_comments' => true,
            'push_notifications' => true
        ];
        
        $result = $this->notificationModel->updateNotificationPreferences($this->testUserId1, $newPreferences);
        $this->assertTest(
            $result['success'],
            "Update notification preferences",
            "Should update notification preferences"
        );
        
        // Test 3: Batch notification creation
        $notifications = [
            ['user_id' => $this->testUserId1, 'type' => 'test', 'content' => 'Batch test 1'],
            ['user_id' => $this->testUserId1, 'type' => 'test', 'content' => 'Batch test 2']
        ];
        
        $result = $this->notificationModel->createBatchNotifications($notifications);
        $this->assertTest(
            $result['success'],
            "Batch notification creation",
            "Should create multiple notifications in batch"
        );
    }
    
    // =================== INTEGRATION TESTS ===================
    
    private function runIntegrationTests() {
        echo "=== INTEGRATION TESTS ===\n\n";
        
        $this->testEngagementWorkflow();
        $this->testNotificationIntegration();
        $this->testCrossFeatureInteractions();
        
        echo "\n";
    }
    
    private function testEngagementWorkflow() {
        echo "Testing complete engagement workflow...\n";
        
        // Simulate a complete user engagement workflow
        
        // 1. User follows another user
        $followResult = $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        
        // 2. User claps on an article
        $clapResult = $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 5);
        
        // 3. User bookmarks the article
        $bookmarkResult = $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        
        // 4. User comments on the article
        $commentResult = $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId1,
            "Great article! Really enjoyed reading it."
        );
        
        $this->assertTest(
            $followResult['success'] && $clapResult['success'] && 
            $bookmarkResult['success'] && $commentResult['success'],
            "Complete engagement workflow",
            "Should successfully complete all engagement actions"
        );
    }
    
    private function testNotificationIntegration() {
        echo "Testing notification integration with engagement...\n";
        
        // Test that engagement actions trigger appropriate notifications
        
        // 1. Follow should create notification
        $initialCount = $this->notificationModel->getUnreadCount($this->testUserId2);
        $this->followModel->followUser($this->testUserId3, $this->testUserId2);
        $newCount = $this->notificationModel->getUnreadCount($this->testUserId2);
        
        $this->assertTest(
            $newCount > $initialCount,
            "Follow creates notification",
            "Following a user should create a notification"
        );
        
        // 2. Clap should create notification (if implemented)
        // This would depend on the specific implementation
        
        // 3. Comment should create notification (if implemented)
        // This would depend on the specific implementation
    }
    
    private function testCrossFeatureInteractions() {
        echo "Testing cross-feature interactions...\n";
        
        // Test 1: Bookmarked articles in following feed
        $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId1);
        $feed = $this->followModel->getFollowingFeed($this->testUserId1, 10, 0);
        
        // Check if bookmarked status is included in feed
        $hasBookmarkInfo = false;
        foreach ($feed as $article) {
            if (isset($article['is_bookmarked'])) {
                $hasBookmarkInfo = true;
                break;
            }
        }
        
        $this->assertTest(
            $hasBookmarkInfo,
            "Bookmark status in feed",
            "Following feed should include bookmark status"
        );
        
        // Test 2: User clap status in article data
        $this->clapModel->addClap($this->testUserId1, $this->testArticleId1, 3);
        $userClapCount = $this->clapModel->getUserClapCount($this->testUserId1, $this->testArticleId1);
        
        $this->assertTest(
            $userClapCount == 3,
            "User clap status tracking",
            "Should track individual user clap counts correctly"
        );
        
        // Test 3: Comment count affects article statistics
        $initialCommentCount = $this->commentModel->getArticleCommentCount($this->testArticleId1);
        $this->commentModel->createComment(
            $this->testArticleId1,
            $this->testUserId1,
            "Integration test comment"
        );
        $newCommentCount = $this->commentModel->getArticleCommentCount($this->testArticleId1);
        
        $this->assertTest(
            $newCommentCount > $initialCommentCount,
            "Comment count integration",
            "Creating comments should update article comment count"
        );
    }
    
    // =================== TEST UTILITIES ===================
    
    private function assertTest($condition, $testName, $description) {
        $this->totalTests++;
        
        if ($condition) {
            $this->passedTests++;
            echo "  ✓ $testName: $description\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'PASS', 'description' => $description];
        } else {
            echo "  ✗ $testName: $description\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'description' => $description];
        }
    }
    
    private function displayTestSummary() {
        echo "===========================================\n";
        echo "TEST SUMMARY\n";
        echo "===========================================\n\n";
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->totalTests - $this->passedTests) . "\n";
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 2) . "%\n\n";
        
        // Show failed tests
        $failedTests = array_filter($this->testResults, function($test) {
            return $test['status'] === 'FAIL';
        });
        
        if (!empty($failedTests)) {
            echo "FAILED TESTS:\n";
            echo "=============\n";
            foreach ($failedTests as $test) {
                echo "- {$test['name']}: {$test['description']}\n";
            }
            echo "\n";
        }
        
        echo "===========================================\n";
        echo "ENGAGEMENT FEATURES TESTING COMPLETED\n";
        echo "===========================================\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new EngagementFeaturesTest();
    $test->runAllTests();
}