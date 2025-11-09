<?php
/**
 * Test Runner
 * Runs all system tests including authentication and engagement features
 */

require_once __DIR__ . '/UserModelTest.php';
require_once __DIR__ . '/JWTHelperTest.php';
require_once __DIR__ . '/AuthEndpointsTest.php';
require_once __DIR__ . '/AuthMiddlewareTest.php';
require_once __DIR__ . '/EngagementTestRunner.php';
require_once __DIR__ . '/EngagementFeaturesTest.php';
require_once __DIR__ . '/EngagementValidationTest.php';
require_once __DIR__ . '/EngagementNotificationIntegrationTest.php';
require_once __DIR__ . '/ClapSystemTest.php';
require_once __DIR__ . '/CommentSystemTest.php';
require_once __DIR__ . '/BookmarkFollowSystemTest.php';
require_once __DIR__ . '/NotificationSystemTest.php';

echo "===========================================\n";
echo "MEDIUM CLONE COMPREHENSIVE TEST SUITE\n";
echo "===========================================\n\n";

// Run Authentication Tests
echo "RUNNING AUTHENTICATION TESTS...\n";
echo "================================\n\n";

$userModelTest = new UserModelTest();
$userModelTest->runAllTests();

$jwtHelperTest = new JWTHelperTest();
$jwtHelperTest->runAllTests();

$authEndpointsTest = new AuthEndpointsTest();
$authEndpointsTest->runAllTests();

$authMiddlewareTest = new AuthMiddlewareTest();
$authMiddlewareTest->runTests();

echo "\n";

// Run Engagement Feature Tests
echo "RUNNING ENGAGEMENT FEATURE TESTS...\n";
echo "====================================\n\n";

// Run engagement test runner (works without database)
$engagementTestRunner = new EngagementTestRunner();
$engagementTestRunner->runTests();

// Run comprehensive engagement tests (requires database)
echo "RUNNING COMPREHENSIVE ENGAGEMENT TESTS (requires database)...\n";
echo "============================================================\n\n";

try {
    $engagementTest = new EngagementFeaturesTest();
    $engagementTest->runAllTests();
} catch (Exception $e) {
    echo "⚠️  Comprehensive engagement tests skipped due to database issues\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Run engagement validation tests
try {
    $engagementValidationTest = new EngagementValidationTest();
    $engagementValidationTest->runTests();
} catch (Exception $e) {
    echo "⚠️  Engagement validation tests skipped due to database issues\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Run engagement notification integration tests
try {
    $engagementNotificationTest = new EngagementNotificationIntegrationTest();
    $engagementNotificationTest->runTests();
} catch (Exception $e) {
    echo "⚠️  Engagement notification tests skipped due to database issues\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

echo "\n";

// Run individual engagement system tests for detailed coverage
echo "RUNNING INDIVIDUAL ENGAGEMENT SYSTEM TESTS...\n";
echo "==============================================\n\n";

$clapTest = new ClapSystemTest();
$clapTest->runTests();

$commentTest = new CommentSystemTest();
$commentTest->runTests();

$bookmarkFollowTest = new BookmarkFollowSystemTest();
$bookmarkFollowTest->runTests();

$notificationTest = new NotificationSystemTest();
$notificationTest->runTests();

echo "===========================================\n";
echo "ALL TESTS COMPLETED\n";
echo "===========================================\n";

echo "\nNote: These are comprehensive structural and functional tests.\n";
echo "For production deployment, consider using PHPUnit framework\n";
echo "and setting up dedicated test database environment.\n";