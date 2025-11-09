<?php

require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../config/database.php';

class BookmarkFollowSystemTest {
    private $bookmarkModel;
    private $followModel;
    private $testUserId1 = 1;
    private $testUserId2 = 2;
    private $testArticleId = 1;
    
    public function __construct() {
        $this->bookmarkModel = new Bookmark();
        $this->followModel = new Follow();
    }
    
    public function runTests() {
        echo "Running Bookmark and Follow System Tests...\n\n";
        
        $this->testBookmarkSystem();
        $this->testFollowSystem();
        
        echo "\nAll bookmark and follow system tests completed!\n";
    }
    
    private function testBookmarkSystem() {
        echo "=== BOOKMARK SYSTEM TESTS ===\n\n";
        
        $this->testAddBookmark();
        $this->testBookmarkStatus();
        $this->testRemoveBookmark();
        $this->testGetUserBookmarks();
    }
    
    private function testFollowSystem() {
        echo "\n=== FOLLOW SYSTEM TESTS ===\n\n";
        
        $this->testFollowUser();
        $this->testFollowStatus();
        $this->testUnfollowUser();
        $this->testFollowCounts();
        $this->testFollowingFeed();
    }
    
    private function testAddBookmark() {
        echo "Testing add bookmark functionality...\n";
        
        // Clean up any existing bookmark
        $this->bookmarkModel->removeBookmark($this->testUserId1, $this->testArticleId);
        
        $result = $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId);
        
        if ($result['success']) {
            echo "✓ Successfully added bookmark\n";
            echo "  - Message: " . $result['message'] . "\n";
        } else {
            echo "✗ Failed to add bookmark: " . $result['error'] . "\n";
        }
    }
    
    private function testBookmarkStatus() {
        echo "\nTesting bookmark status check...\n";
        
        $isBookmarked = $this->bookmarkModel->isBookmarked($this->testUserId1, $this->testArticleId);
        
        if ($isBookmarked) {
            echo "✓ Bookmark status correctly shows as bookmarked\n";
        } else {
            echo "✗ Bookmark status incorrect\n";
        }
        
        // Test duplicate bookmark
        $result = $this->bookmarkModel->addBookmark($this->testUserId1, $this->testArticleId);
        if (!$result['success']) {
            echo "✓ Duplicate bookmark correctly rejected\n";
            echo "  - Error: " . $result['error'] . "\n";
        } else {
            echo "✗ Duplicate bookmark was incorrectly allowed\n";
        }
    }
    
    private function testGetUserBookmarks() {
        echo "\nTesting get user bookmarks...\n";
        
        $bookmarks = $this->bookmarkModel->getUserBookmarks($this->testUserId1, 10, 0);
        $count = $this->bookmarkModel->getUserBookmarkCount($this->testUserId1);
        
        echo "✓ Retrieved user bookmarks\n";
        echo "  - Bookmark count: $count\n";
        echo "  - Bookmarks retrieved: " . count($bookmarks) . "\n";
    }
    
    private function testRemoveBookmark() {
        echo "\nTesting remove bookmark functionality...\n";
        
        $result = $this->bookmarkModel->removeBookmark($this->testUserId1, $this->testArticleId);
        
        if ($result['success']) {
            echo "✓ Successfully removed bookmark\n";
            echo "  - Message: " . $result['message'] . "\n";
            
            // Verify bookmark is removed
            $isBookmarked = $this->bookmarkModel->isBookmarked($this->testUserId1, $this->testArticleId);
            if (!$isBookmarked) {
                echo "✓ Bookmark status correctly shows as not bookmarked\n";
            } else {
                echo "✗ Bookmark still shows as bookmarked after removal\n";
            }
        } else {
            echo "✗ Failed to remove bookmark: " . $result['error'] . "\n";
        }
    }
    
    private function testFollowUser() {
        echo "Testing follow user functionality...\n";
        
        // Clean up any existing follow
        $this->followModel->unfollowUser($this->testUserId1, $this->testUserId2);
        
        $result = $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        
        if ($result['success']) {
            echo "✓ Successfully followed user\n";
            echo "  - Message: " . $result['message'] . "\n";
        } else {
            echo "✗ Failed to follow user: " . $result['error'] . "\n";
        }
        
        // Test self-follow prevention
        $selfFollowResult = $this->followModel->followUser($this->testUserId1, $this->testUserId1);
        if (!$selfFollowResult['success']) {
            echo "✓ Self-follow correctly prevented\n";
            echo "  - Error: " . $selfFollowResult['error'] . "\n";
        } else {
            echo "✗ Self-follow was incorrectly allowed\n";
        }
    }
    
    private function testFollowStatus() {
        echo "\nTesting follow status check...\n";
        
        $isFollowing = $this->followModel->isFollowing($this->testUserId1, $this->testUserId2);
        
        if ($isFollowing) {
            echo "✓ Follow status correctly shows as following\n";
        } else {
            echo "✗ Follow status incorrect\n";
        }
        
        // Test duplicate follow
        $result = $this->followModel->followUser($this->testUserId1, $this->testUserId2);
        if (!$result['success']) {
            echo "✓ Duplicate follow correctly rejected\n";
            echo "  - Error: " . $result['error'] . "\n";
        } else {
            echo "✗ Duplicate follow was incorrectly allowed\n";
        }
    }
    
    private function testFollowCounts() {
        echo "\nTesting follow counts...\n";
        
        $followerCount = $this->followModel->getFollowerCount($this->testUserId2);
        $followingCount = $this->followModel->getFollowingCount($this->testUserId1);
        
        echo "✓ Retrieved follow counts\n";
        echo "  - User {$this->testUserId2} followers: $followerCount\n";
        echo "  - User {$this->testUserId1} following: $followingCount\n";
        
        // Get followers and following lists
        $followers = $this->followModel->getFollowers($this->testUserId2, 10, 0);
        $following = $this->followModel->getFollowing($this->testUserId1, 10, 0);
        
        echo "  - Followers list count: " . count($followers) . "\n";
        echo "  - Following list count: " . count($following) . "\n";
    }
    
    private function testFollowingFeed() {
        echo "\nTesting following feed...\n";
        
        $feed = $this->followModel->getFollowingFeed($this->testUserId1, 10, 0);
        
        echo "✓ Retrieved following feed\n";
        echo "  - Articles in feed: " . count($feed) . "\n";
        
        // Test suggestions
        $suggestions = $this->followModel->getSuggestedFollows($this->testUserId1, 5);
        echo "  - Follow suggestions: " . count($suggestions) . "\n";
    }
    
    private function testUnfollowUser() {
        echo "\nTesting unfollow user functionality...\n";
        
        $result = $this->followModel->unfollowUser($this->testUserId1, $this->testUserId2);
        
        if ($result['success']) {
            echo "✓ Successfully unfollowed user\n";
            echo "  - Message: " . $result['message'] . "\n";
            
            // Verify unfollow
            $isFollowing = $this->followModel->isFollowing($this->testUserId1, $this->testUserId2);
            if (!$isFollowing) {
                echo "✓ Follow status correctly shows as not following\n";
            } else {
                echo "✗ Still shows as following after unfollow\n";
            }
        } else {
            echo "✗ Failed to unfollow user: " . $result['error'] . "\n";
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new BookmarkFollowSystemTest();
    $test->runTests();
}