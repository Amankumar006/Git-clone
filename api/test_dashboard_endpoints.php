<?php

/**
 * Test Dashboard Endpoints
 * Tests the dashboard API endpoints to ensure they return data
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// Mock authentication for testing
class MockAuthMiddleware {
    public function authenticate() {
        // Return a mock user for testing
        return [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];
    }
}

// Override the auth middleware
class TestDashboardController extends DashboardController {
    public function __construct() {
        parent::__construct();
        $this->authMiddleware = new MockAuthMiddleware();
    }
    
    // Make protected methods public for testing
    public function testWriterStats() {
        return $this->writerStats();
    }
    
    public function testReaderStats() {
        return $this->readerStats();
    }
}

echo "Testing Dashboard Endpoints...\n\n";

try {
    $controller = new TestDashboardController();
    
    // Test writer stats
    echo "Testing Writer Stats...\n";
    ob_start();
    $controller->testWriterStats();
    $writerStatsOutput = ob_get_clean();
    
    if (!empty($writerStatsOutput)) {
        $writerStats = json_decode($writerStatsOutput, true);
        if ($writerStats && isset($writerStats['success']) && $writerStats['success']) {
            echo "✅ Writer stats endpoint working\n";
            echo "Data: " . json_encode($writerStats['data'], JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ Writer stats endpoint returned error\n";
            echo "Response: " . $writerStatsOutput . "\n\n";
        }
    } else {
        echo "❌ Writer stats endpoint returned no data\n\n";
    }
    
    // Test reader stats
    echo "Testing Reader Stats...\n";
    ob_start();
    $controller->testReaderStats();
    $readerStatsOutput = ob_get_clean();
    
    if (!empty($readerStatsOutput)) {
        $readerStats = json_decode($readerStatsOutput, true);
        if ($readerStats && isset($readerStats['success']) && $readerStats['success']) {
            echo "✅ Reader stats endpoint working\n";
            echo "Data: " . json_encode($readerStats['data'], JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ Reader stats endpoint returned error\n";
            echo "Response: " . $readerStatsOutput . "\n\n";
        }
    } else {
        echo "❌ Reader stats endpoint returned no data\n\n";
    }
    
    // Test database tables exist
    echo "Checking required tables...\n";
    $db = Database::getInstance()->getConnection();
    
    $requiredTables = ['article_views', 'article_reads', 'article_tags', 'tags'];
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✅ Table '$table' exists\n";
            
            // Check if table has data
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch();
            echo "   Records: {$count['count']}\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error testing dashboard endpoints: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
echo "If tables are missing, run: php setup_analytics_tables.php\n";