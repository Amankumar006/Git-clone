<?php

require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../config/database.php';

class ClapSystemTest {
    private $clapModel;
    private $testUserId = 1;
    private $testArticleId = 1;
    
    public function __construct() {
        $this->clapModel = new Clap();
    }
    
    public function runTests() {
        echo "Running Clap System Tests...\n\n";
        
        $this->testAddClap();
        $this->testGetClapStatus();
        $this->testClapLimit();
        $this->testRemoveClap();
        
        echo "\nAll clap system tests completed!\n";
    }
    
    private function testAddClap() {
        echo "Testing add clap functionality...\n";
        
        // Clean up any existing claps for test
        $this->clapModel->removeClap($this->testUserId, $this->testArticleId);
        
        // Test adding a clap
        $result = $this->clapModel->addClap($this->testUserId, $this->testArticleId, 1);
        
        if ($result['success']) {
            echo "✓ Successfully added clap\n";
            echo "  - User clap count: " . $result['data']['count'] . "\n";
            echo "  - Total article claps: " . $result['total_claps'] . "\n";
        } else {
            echo "✗ Failed to add clap: " . $result['error'] . "\n";
        }
    }
    
    private function testGetClapStatus() {
        echo "\nTesting clap status retrieval...\n";
        
        $userClapCount = $this->clapModel->getUserClapCount($this->testUserId, $this->testArticleId);
        $totalClaps = $this->clapModel->getArticleTotalClaps($this->testArticleId);
        $canClap = $this->clapModel->canUserClap($this->testUserId, $this->testArticleId);
        
        echo "✓ Retrieved clap status\n";
        echo "  - User clap count: $userClapCount\n";
        echo "  - Total article claps: $totalClaps\n";
        echo "  - Can user clap: " . ($canClap ? 'Yes' : 'No') . "\n";
    }
    
    private function testClapLimit() {
        echo "\nTesting clap limit (50 claps max)...\n";
        
        // Try to add 50 claps at once
        $result = $this->clapModel->addClap($this->testUserId, $this->testArticleId, 49);
        
        if ($result['success']) {
            echo "✓ Added claps up to limit\n";
            echo "  - User clap count: " . $result['data']['count'] . "\n";
            
            // Try to add one more (should be capped at 50)
            $result2 = $this->clapModel->addClap($this->testUserId, $this->testArticleId, 5);
            
            if ($result2['success'] && $result2['data']['count'] == 50) {
                echo "✓ Clap limit enforced correctly (capped at 50)\n";
            } else {
                echo "✗ Clap limit not enforced properly\n";
            }
            
            // Check if user can still clap
            $canClap = $this->clapModel->canUserClap($this->testUserId, $this->testArticleId);
            if (!$canClap) {
                echo "✓ User correctly cannot clap after reaching limit\n";
            } else {
                echo "✗ User can still clap after reaching limit\n";
            }
        } else {
            echo "✗ Failed to test clap limit: " . $result['error'] . "\n";
        }
    }
    
    private function testRemoveClap() {
        echo "\nTesting remove clap functionality...\n";
        
        $result = $this->clapModel->removeClap($this->testUserId, $this->testArticleId);
        
        if ($result['success']) {
            echo "✓ Successfully removed clap\n";
            echo "  - Total article claps after removal: " . $result['total_claps'] . "\n";
            
            // Verify user clap count is 0
            $userClapCount = $this->clapModel->getUserClapCount($this->testUserId, $this->testArticleId);
            if ($userClapCount == 0) {
                echo "✓ User clap count correctly reset to 0\n";
            } else {
                echo "✗ User clap count not reset properly: $userClapCount\n";
            }
        } else {
            echo "✗ Failed to remove clap: " . $result['error'] . "\n";
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ClapSystemTest();
    $test->runTests();
}