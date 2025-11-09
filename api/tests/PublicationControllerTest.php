<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/PublicationController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWTHelper.php';

class PublicationControllerTest {
    private $controller;
    private $userModel;
    private $testUserId;
    private $testWriterId;
    private $testPublicationId;
    private $authToken;
    private $writerToken;
    
    public function __construct() {
        // Set up basic $_SERVER variables for controller
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        
        $this->controller = new PublicationController();
        $this->userModel = new User();
    }
    
    public function runAllTests() {
        echo "=== Publication Controller API Tests ===\n\n";
        
        $this->setupTestData();
        
        // Test authentication and authorization
        $this->testAuthenticationRequired();
        $this->testUnauthorizedAccess();
        
        // Test publication CRUD operations
        $this->testCreatePublication();
        $this->testGetPublication();
        $this->testUpdatePublication();
        $this->testGetUserPublications();
        
        // Test member management endpoints
        $this->testInviteMember();
        $this->testUpdateMemberRole();
        $this->testRemoveMember();
        
        // Test publication following
        $this->testFollowPublication();
        $this->testUnfollowPublication();
        $this->testGetFollowedPublications();
        
        // Test article management
        $this->testGetPublicationArticles();
        $this->testGetFilteredArticles();
        
        // Test search functionality
        $this->testSearchPublications();
        
        // Test error handling
        $this->testValidationErrors();
        $this->testNotFoundErrors();
        
        $this->cleanupTestData();
        
        echo "\n=== All Publication Controller Tests Completed ===\n";
    }
    
    private function setupTestData() {
        echo "Setting up test data for controller tests...\n";
        
        // Create test users
        $userData1 = [
            'username' => 'controller_owner_' . time(),
            'email' => 'controller_owner_' . time() . '@test.com',
            'password' => 'testpass123'
        ];
        
        $userData2 = [
            'username' => 'controller_writer_' . time(),
            'email' => 'controller_writer_' . time() . '@test.com',
            'password' => 'testpass123'
        ];
        
        $userResult1 = $this->userModel->create($userData1);
        $userResult2 = $this->userModel->create($userData2);
        
        if (!$userResult1['success'] || !$userResult2['success']) {
            throw new Exception('Failed to create test users for controller tests');
        }
        
        $this->testUserId = $userResult1['user']['id'];
        $this->testWriterId = $userResult2['user']['id'];
        
        // Generate JWT tokens
        $this->authToken = JWTHelper::encodeCustomToken([
            'user_id' => $this->testUserId,
            'username' => $userData1['username'],
            'exp' => time() + 3600
        ]);
        
        $this->writerToken = JWTHelper::encodeCustomToken([
            'user_id' => $this->testWriterId,
            'username' => $userData2['username'],
            'exp' => time() + 3600
        ]);
        
        echo "Controller test data setup complete.\n\n";
    }
    
    private function testAuthenticationRequired() {
        echo "Testing authentication requirements...\n";
        
        // Test create without authentication
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        unset($_SERVER['HTTP_AUTHORIZATION']);
        
        ob_start();
        $this->controller->create();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'Authentication required') !== false) {
            echo "✓ Authentication requirement works correctly\n";
        } else {
            echo "✗ Authentication requirement failed\n";
        }
        
        echo "\n";
    }
    
    private function testUnauthorizedAccess() {
        echo "Testing unauthorized access prevention...\n";
        
        // Set up authenticated request with writer token
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->writerToken;
        $_POST = json_encode([
            'id' => 1,
            'name' => 'Unauthorized Update'
        ]);
        
        ob_start();
        $this->controller->update();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && (
            strpos($response['error'], 'Insufficient permissions') !== false ||
            strpos($response['error'], 'not found') !== false
        )) {
            echo "✓ Unauthorized access prevention works correctly\n";
        } else {
            echo "✗ Unauthorized access prevention failed\n";
        }
        
        echo "\n";
    }
    
    private function testCreatePublication() {
        echo "Testing publication creation endpoint...\n";
        
        // Set up authenticated request
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->authToken;
        $_POST = json_encode([
            'name' => 'Controller Test Publication',
            'description' => 'Created via controller test',
            'logo_url' => 'https://example.com/logo.png'
        ]);
        
        ob_start();
        $this->controller->create();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && isset($response['data']['id'])) {
            echo "✓ Publication creation endpoint works correctly\n";
            $this->testPublicationId = $response['data']['id'];
        } else {
            echo "✗ Publication creation endpoint failed\n";
            echo "Response: " . $output . "\n";
        }
        
        // Test validation
        $_POST = json_encode(['name' => '']); // Empty name
        
        ob_start();
        $this->controller->create();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'Validation') !== false) {
            echo "✓ Publication creation validation works correctly\n";
        } else {
            echo "✗ Publication creation validation failed\n";
        }
        
        echo "\n";
    }
    
    private function testGetPublication() {
        echo "Testing get publication endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test get publication - no test publication created\n\n";
            return;
        }
        
        $_GET['id'] = $this->testPublicationId;
        
        ob_start();
        $this->controller->show();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && $response['data']['id'] == $this->testPublicationId) {
            echo "✓ Get publication endpoint works correctly\n";
            
            // Verify response structure
            $expectedKeys = ['id', 'name', 'description', 'owner_username', 'stats', 'members'];
            $hasAllKeys = true;
            foreach ($expectedKeys as $key) {
                if (!array_key_exists($key, $response['data'])) {
                    $hasAllKeys = false;
                    break;
                }
            }
            
            if ($hasAllKeys) {
                echo "✓ Publication response structure is correct\n";
            } else {
                echo "✗ Publication response structure is incorrect\n";
            }
        } else {
            echo "✗ Get publication endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testUpdatePublication() {
        echo "Testing update publication endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test update publication - no test publication created\n\n";
            return;
        }
        
        $_POST = json_encode([
            'id' => $this->testPublicationId,
            'name' => 'Updated Controller Test Publication',
            'description' => 'Updated via controller test'
        ]);
        
        ob_start();
        $this->controller->update();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && $response['data']['name'] === 'Updated Controller Test Publication') {
            echo "✓ Update publication endpoint works correctly\n";
        } else {
            echo "✗ Update publication endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testGetUserPublications() {
        echo "Testing get user publications endpoint...\n";
        
        ob_start();
        $this->controller->getUserPublications();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && isset($response['data']['owned']) && isset($response['data']['member'])) {
            echo "✓ Get user publications endpoint works correctly\n";
            
            // Verify owned publications include our test publication
            $foundTestPub = false;
            foreach ($response['data']['owned'] as $pub) {
                if ($pub['id'] == $this->testPublicationId) {
                    $foundTestPub = true;
                    break;
                }
            }
            
            if ($foundTestPub) {
                echo "✓ User publications include created publication\n";
            } else {
                echo "✗ User publications missing created publication\n";
            }
        } else {
            echo "✗ Get user publications endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testInviteMember() {
        echo "Testing invite member endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test invite member - no test publication created\n\n";
            return;
        }
        
        // Get writer email for invitation
        $writer = $this->userModel->findById($this->testWriterId);
        
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId,
            'email' => $writer['email'],
            'role' => 'writer'
        ]);
        
        ob_start();
        $this->controller->inviteMember();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && isset($response['data']['member'])) {
            echo "✓ Invite member endpoint works correctly\n";
        } else {
            echo "✗ Invite member endpoint failed\n";
            echo "Response: " . $output . "\n";
        }
        
        // Test invalid email
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId,
            'email' => 'nonexistent@test.com',
            'role' => 'writer'
        ]);
        
        ob_start();
        $this->controller->inviteMember();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'not found') !== false) {
            echo "✓ Invalid email handling works correctly\n";
        } else {
            echo "✗ Invalid email handling failed\n";
        }
        
        echo "\n";
    }
    
    private function testUpdateMemberRole() {
        echo "Testing update member role endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test update member role - no test publication created\n\n";
            return;
        }
        
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId,
            'user_id' => $this->testWriterId,
            'role' => 'editor'
        ]);
        
        ob_start();
        $this->controller->updateMemberRole();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success']) {
            echo "✓ Update member role endpoint works correctly\n";
        } else {
            echo "✗ Update member role endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testRemoveMember() {
        echo "Testing remove member endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test remove member - no test publication created\n\n";
            return;
        }
        
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId,
            'user_id' => $this->testWriterId
        ]);
        
        ob_start();
        $this->controller->removeMember();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success']) {
            echo "✓ Remove member endpoint works correctly\n";
        } else {
            echo "✗ Remove member endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testFollowPublication() {
        echo "Testing follow publication endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test follow publication - no test publication created\n\n";
            return;
        }
        
        // Switch to writer token for following
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->writerToken;
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId
        ]);
        
        ob_start();
        $this->controller->follow();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && $response['data']['is_following'] === true) {
            echo "✓ Follow publication endpoint works correctly\n";
        } else {
            echo "✗ Follow publication endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testUnfollowPublication() {
        echo "Testing unfollow publication endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test unfollow publication - no test publication created\n\n";
            return;
        }
        
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId
        ]);
        
        ob_start();
        $this->controller->unfollow();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && $response['data']['is_following'] === false) {
            echo "✓ Unfollow publication endpoint works correctly\n";
        } else {
            echo "✗ Unfollow publication endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testGetFollowedPublications() {
        echo "Testing get followed publications endpoint...\n";
        
        ob_start();
        $this->controller->getFollowedPublications();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && is_array($response['data'])) {
            echo "✓ Get followed publications endpoint works correctly\n";
        } else {
            echo "✗ Get followed publications endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testGetPublicationArticles() {
        echo "Testing get publication articles endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test get publication articles - no test publication created\n\n";
            return;
        }
        
        $_GET['id'] = $this->testPublicationId;
        $_GET['status'] = 'published';
        
        ob_start();
        $this->controller->getArticles();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && is_array($response['data'])) {
            echo "✓ Get publication articles endpoint works correctly\n";
        } else {
            echo "✗ Get publication articles endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testGetFilteredArticles() {
        echo "Testing get filtered articles endpoint...\n";
        
        if (!$this->testPublicationId) {
            echo "✗ Cannot test get filtered articles - no test publication created\n\n";
            return;
        }
        
        $_GET['id'] = $this->testPublicationId;
        $_GET['search'] = 'test';
        $_GET['sort'] = 'popular';
        
        ob_start();
        $this->controller->getFilteredArticles();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && is_array($response['data'])) {
            echo "✓ Get filtered articles endpoint works correctly\n";
        } else {
            echo "✗ Get filtered articles endpoint failed\n";
        }
        
        echo "\n";
    }
    
    private function testSearchPublications() {
        echo "Testing search publications endpoint...\n";
        
        $_GET['q'] = 'Controller Test';
        
        ob_start();
        $this->controller->search();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if ($response['success'] && is_array($response['data'])) {
            echo "✓ Search publications endpoint works correctly\n";
        } else {
            echo "✗ Search publications endpoint failed\n";
        }
        
        // Test empty query
        $_GET['q'] = '';
        
        ob_start();
        $this->controller->search();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'required') !== false) {
            echo "✓ Empty search query validation works correctly\n";
        } else {
            echo "✗ Empty search query validation failed\n";
        }
        
        echo "\n";
    }
    
    private function testValidationErrors() {
        echo "Testing validation error handling...\n";
        
        // Switch back to owner token
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->authToken;
        
        // Test missing publication ID
        $_POST = json_encode(['name' => 'Test']);
        
        ob_start();
        $this->controller->update();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'required') !== false) {
            echo "✓ Missing ID validation works correctly\n";
        } else {
            echo "✗ Missing ID validation failed\n";
        }
        
        // Test invalid role
        $_POST = json_encode([
            'publication_id' => $this->testPublicationId,
            'email' => 'test@test.com',
            'role' => 'invalid_role'
        ]);
        
        ob_start();
        $this->controller->inviteMember();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'Invalid role') !== false) {
            echo "✓ Invalid role validation works correctly\n";
        } else {
            echo "✗ Invalid role validation failed\n";
        }
        
        echo "\n";
    }
    
    private function testNotFoundErrors() {
        echo "Testing not found error handling...\n";
        
        // Test non-existent publication
        $_GET['id'] = 99999;
        
        ob_start();
        $this->controller->show();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response['success'] && strpos($response['error'], 'not found') !== false) {
            echo "✓ Non-existent publication handling works correctly\n";
        } else {
            echo "✗ Non-existent publication handling failed\n";
        }
        
        echo "\n";
    }
    
    private function cleanupTestData() {
        echo "Cleaning up controller test data...\n";
        
        // Delete test publication
        if ($this->testPublicationId) {
            $publicationModel = new Publication();
            $publicationModel->delete($this->testPublicationId);
        }
        
        // Delete test users
        if ($this->testUserId) {
            $this->userModel->delete($this->testUserId);
        }
        if ($this->testWriterId) {
            $this->userModel->delete($this->testWriterId);
        }
        
        // Clean up globals
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_POST);
        unset($_GET);
        
        echo "Controller test data cleanup complete.\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new PublicationControllerTest();
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Controller test execution failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}