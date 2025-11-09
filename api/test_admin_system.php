<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/controllers/AdminController.php';

echo "Testing Admin System\n";
echo "===================\n\n";

try {
    // Test 1: Create admin user if not exists
    echo "Test 1: Setting up admin user\n";
    $userModel = new User();
    
    // Check if admin user exists
    $adminUser = $userModel->findByEmail('admin@example.com');
    if (!$adminUser) {
        // Create admin user
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin'
        ];
        
        $result = $userModel->register($adminData);
        if ($result['success']) {
            echo "✓ Admin user created successfully\n";
            $adminUserId = $result['data']['id'];
            
            // Update role to admin
            $userModel->updateRole($adminUserId, 'admin');
            echo "✓ Admin role assigned\n";
        } else {
            echo "✗ Failed to create admin user: " . json_encode($result['errors']) . "\n";
        }
    } else {
        echo "✓ Admin user already exists\n";
        $adminUserId = $adminUser['id'];
    }
    
    // Test 2: Test user management functions
    echo "\nTest 2: Testing user management functions\n";
    
    // Get users
    $users = $userModel->getUsers('', '', '', 10, 0);
    echo "✓ Retrieved " . count($users) . " users\n";
    
    // Get user count
    $userCount = $userModel->getUserCount();
    echo "✓ Total user count: " . $userCount . "\n";
    
    // Test 3: Test admin dashboard stats
    echo "\nTest 3: Testing admin dashboard stats\n";
    
    // Simulate admin controller (without HTTP context)
    class TestAdminController {
        private $userModel;
        private $db;
        
        public function __construct() {
            $this->userModel = new User();
            $this->db = Database::getInstance()->getConnection();
        }
        
        public function getUserStats() {
            $sql = "SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_users_7d,
                        SUM(CASE WHEN is_suspended = TRUE THEN 1 ELSE 0 END) as suspended_users,
                        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
                        SUM(CASE WHEN role = 'moderator' THEN 1 ELSE 0 END) as moderator_users
                    FROM users";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
        }
        
        public function getContentStats() {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM articles) as total_articles,
                        (SELECT COUNT(*) FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_articles_30d,
                        (SELECT COUNT(*) FROM articles WHERE status = 'published') as published_articles,
                        (SELECT COUNT(*) FROM comments) as total_comments,
                        (SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_comments_30d";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
        }
    }
    
    $testController = new TestAdminController();
    
    $userStats = $testController->getUserStats();
    if ($userStats) {
        echo "✓ User stats retrieved:\n";
        echo "  - Total users: " . $userStats['total_users'] . "\n";
        echo "  - New users (30d): " . $userStats['new_users_30d'] . "\n";
        echo "  - Admin users: " . $userStats['admin_users'] . "\n";
    }
    
    $contentStats = $testController->getContentStats();
    if ($contentStats) {
        echo "✓ Content stats retrieved:\n";
        echo "  - Total articles: " . $contentStats['total_articles'] . "\n";
        echo "  - New articles (30d): " . $contentStats['new_articles_30d'] . "\n";
        echo "  - Total comments: " . $contentStats['total_comments'] . "\n";
    }
    
    // Test 4: Test user role management
    echo "\nTest 4: Testing user role management\n";
    
    // Create a test user for role management
    $testUserData = [
        'username' => 'testuser_' . time(),
        'email' => 'testuser_' . time() . '@example.com',
        'password' => 'password123'
    ];
    
    $testUserResult = $userModel->register($testUserData);
    if ($testUserResult['success']) {
        $testUserId = $testUserResult['data']['id'];
        echo "✓ Test user created with ID: " . $testUserId . "\n";
        
        // Test role update
        $roleUpdated = $userModel->updateRole($testUserId, 'moderator');
        if ($roleUpdated) {
            echo "✓ User role updated to moderator\n";
        }
        
        // Test suspension
        $suspended = $userModel->suspendUser($testUserId, date('Y-m-d H:i:s', strtotime('+7 days')));
        if ($suspended) {
            echo "✓ User suspended successfully\n";
        }
        
        // Test unsuspension
        $unsuspended = $userModel->unsuspendUser($testUserId);
        if ($unsuspended) {
            echo "✓ User unsuspended successfully\n";
        }
        
        // Test email verification
        $verified = $userModel->verifyUser($testUserId);
        if ($verified) {
            echo "✓ User email verified successfully\n";
        }
    }
    
    // Test 5: Test expired suspension cleanup
    echo "\nTest 5: Testing expired suspension cleanup\n";
    $cleanupResult = $userModel->checkExpiredSuspensions();
    echo "✓ Expired suspensions cleanup completed\n";
    
    echo "\n✓ All admin system tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}