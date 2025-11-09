<?php

require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../config/database.php';

class CommentSystemTest {
    private $commentModel;
    private $testUserId = 1;
    private $testArticleId = 1;
    
    public function __construct() {
        $this->commentModel = new Comment();
    }
    
    public function runTests() {
        echo "Running Comment System Tests...\n\n";
        
        $this->testCreateComment();
        $this->testCreateReply();
        $this->testGetArticleComments();
        $this->testUpdateComment();
        $this->testDeleteComment();
        $this->testNestingLimit();
        
        echo "\nAll comment system tests completed!\n";
    }
    
    private function testCreateComment() {
        echo "Testing create comment functionality...\n";
        
        $result = $this->commentModel->createComment(
            $this->testArticleId, 
            $this->testUserId, 
            "This is a test comment"
        );
        
        if ($result['success']) {
            echo "✓ Successfully created comment\n";
            echo "  - Comment ID: " . $result['data']['id'] . "\n";
            echo "  - Content: " . $result['data']['content'] . "\n";
            echo "  - Author: " . $result['data']['username'] . "\n";
            
            // Store for later tests
            $this->testCommentId = $result['data']['id'];
        } else {
            echo "✗ Failed to create comment: " . $result['error'] . "\n";
        }
    }
    
    private function testCreateReply() {
        echo "\nTesting create reply functionality...\n";
        
        if (!isset($this->testCommentId)) {
            echo "✗ Skipping reply test - no parent comment available\n";
            return;
        }
        
        $result = $this->commentModel->createComment(
            $this->testArticleId, 
            $this->testUserId, 
            "This is a reply to the comment",
            $this->testCommentId
        );
        
        if ($result['success']) {
            echo "✓ Successfully created reply\n";
            echo "  - Reply ID: " . $result['data']['id'] . "\n";
            echo "  - Parent ID: " . $result['data']['parent_comment_id'] . "\n";
            echo "  - Content: " . $result['data']['content'] . "\n";
            
            $this->testReplyId = $result['data']['id'];
        } else {
            echo "✗ Failed to create reply: " . $result['error'] . "\n";
        }
    }
    
    private function testGetArticleComments() {
        echo "\nTesting get article comments functionality...\n";
        
        $comments = $this->commentModel->getArticleComments($this->testArticleId);
        
        echo "✓ Retrieved article comments\n";
        echo "  - Total top-level comments: " . count($comments) . "\n";
        
        foreach ($comments as $comment) {
            echo "  - Comment: " . substr($comment['content'], 0, 50) . "...\n";
            if (!empty($comment['replies'])) {
                echo "    - Replies: " . count($comment['replies']) . "\n";
            }
        }
    }
    
    private function testUpdateComment() {
        echo "\nTesting update comment functionality...\n";
        
        if (!isset($this->testCommentId)) {
            echo "✗ Skipping update test - no comment available\n";
            return;
        }
        
        $newContent = "This is an updated test comment";
        $result = $this->commentModel->updateComment(
            $this->testCommentId, 
            $this->testUserId, 
            $newContent
        );
        
        if ($result['success']) {
            echo "✓ Successfully updated comment\n";
            echo "  - New content: " . $result['data']['content'] . "\n";
            echo "  - Updated at: " . $result['data']['updated_at'] . "\n";
        } else {
            echo "✗ Failed to update comment: " . $result['error'] . "\n";
        }
    }
    
    private function testNestingLimit() {
        echo "\nTesting comment nesting limit (max 3 levels)...\n";
        
        if (!isset($this->testReplyId)) {
            echo "✗ Skipping nesting test - no reply available\n";
            return;
        }
        
        // Create a reply to the reply (level 3)
        $result1 = $this->commentModel->createComment(
            $this->testArticleId, 
            $this->testUserId, 
            "Level 3 reply",
            $this->testReplyId
        );
        
        if ($result1['success']) {
            echo "✓ Created level 3 reply\n";
            
            // Try to create level 4 (should fail)
            $result2 = $this->commentModel->createComment(
                $this->testArticleId, 
                $this->testUserId, 
                "Level 4 reply (should fail)",
                $result1['data']['id']
            );
            
            if (!$result2['success']) {
                echo "✓ Level 4 reply correctly rejected\n";
                echo "  - Error: " . $result2['error'] . "\n";
            } else {
                echo "✗ Level 4 reply was incorrectly allowed\n";
            }
        } else {
            echo "✗ Failed to create level 3 reply: " . $result1['error'] . "\n";
        }
    }
    
    private function testDeleteComment() {
        echo "\nTesting delete comment functionality...\n";
        
        if (!isset($this->testCommentId)) {
            echo "✗ Skipping delete test - no comment available\n";
            return;
        }
        
        $result = $this->commentModel->deleteComment($this->testCommentId, $this->testUserId);
        
        if ($result['success']) {
            echo "✓ Successfully deleted comment\n";
            echo "  - Message: " . $result['message'] . "\n";
            
            // Verify comment count updated
            $count = $this->commentModel->getArticleCommentCount($this->testArticleId);
            echo "  - Article comment count after deletion: $count\n";
        } else {
            echo "✗ Failed to delete comment: " . $result['error'] . "\n";
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new CommentSystemTest();
    $test->runTests();
}