<?php

/**
 * Publication System Test Runner
 * 
 * This script runs comprehensive tests for the publication system including:
 * - Publication creation and management
 * - Member invitation and role management
 * - Article submission and approval workflow
 * - Permission system testing
 * - Publication statistics
 */

require_once __DIR__ . '/PublicationSystemTest.php';
require_once __DIR__ . '/PublicationControllerTest.php';

echo "========================================\n";
echo "   PUBLICATION SYSTEM TEST RUNNER\n";
echo "========================================\n\n";

echo "Starting comprehensive publication system tests...\n";
echo "Test environment: " . (defined('DB_NAME') ? DB_NAME : 'Unknown') . "\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Run model and business logic tests
    echo "Running Publication Model Tests...\n";
    $modelTestRunner = new PublicationSystemTest();
    $modelTestRunner->runAllTests();
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Run controller API tests
    echo "Running Publication Controller Tests...\n";
    $controllerTestRunner = new PublicationControllerTest();
    $controllerTestRunner->runAllTests();
    
    echo "\n========================================\n";
    echo "   ALL TESTS COMPLETED SUCCESSFULLY\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n========================================\n";
    echo "   TEST EXECUTION FAILED\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    exit(1);
}

echo "\nTest run completed at: " . date('Y-m-d H:i:s') . "\n";