<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Report.php';
require_once __DIR__ . '/models/ModerationAction.php';
require_once __DIR__ . '/models/ContentFilter.php';

echo "Testing Content Moderation System\n";
echo "==================================\n\n";

try {
    // Test 1: Create a report
    echo "Test 1: Creating a content report\n";
    $reportModel = new Report();
    
    $report = $reportModel->createReport(
        1, // reporter_id (assuming user ID 1 exists)
        'article',
        1, // content_id (assuming article ID 1 exists)
        'spam',
        'This article contains spam content'
    );
    
    if ($report) {
        echo "✓ Report created successfully with ID: " . $report['id'] . "\n";
    } else {
        echo "✗ Failed to create report\n";
    }
    
    // Test 2: Get pending reports
    echo "\nTest 2: Fetching pending reports\n";
    $pendingReports = $reportModel->getPendingReports(10, 0);
    echo "✓ Found " . count($pendingReports) . " pending reports\n";
    
    // Test 3: Get report statistics
    echo "\nTest 3: Getting report statistics\n";
    $stats = $reportModel->getReportStats();
    if ($stats) {
        echo "✓ Report stats:\n";
        echo "  - Total reports: " . $stats['total_reports'] . "\n";
        echo "  - Pending: " . $stats['pending_reports'] . "\n";
        echo "  - Resolved: " . $stats['resolved_reports'] . "\n";
    }
    
    // Test 4: Content filtering
    echo "\nTest 4: Testing content filtering\n";
    $filterModel = new ContentFilter();
    
    // Test spam detection
    $spamContent = "Buy now! Click here for free money! Limited time offer! Act now!";
    $flags = $filterModel->scanContent('article', 999, $spamContent);
    echo "✓ Spam content scan completed, found " . count($flags) . " flags\n";
    
    // Test normal content
    $normalContent = "This is a normal article about technology and programming.";
    $flags = $filterModel->scanContent('article', 998, $normalContent);
    echo "✓ Normal content scan completed, found " . count($flags) . " flags\n";
    
    // Test 5: Moderation actions
    echo "\nTest 5: Testing moderation actions\n";
    $moderationModel = new ModerationAction();
    
    // Test content approval
    $actionId = $moderationModel->logAction(
        1, // admin_id
        'approve',
        'article',
        1,
        'Content approved after review'
    );
    
    if ($actionId) {
        echo "✓ Moderation action logged with ID: " . $actionId . "\n";
    }
    
    // Test 6: Get moderation statistics
    echo "\nTest 6: Getting moderation statistics\n";
    $moderationStats = $moderationModel->getModerationStats();
    if ($moderationStats) {
        echo "✓ Moderation stats:\n";
        echo "  - Total actions: " . $moderationStats['total_actions'] . "\n";
        echo "  - Approvals: " . $moderationStats['approvals'] . "\n";
        echo "  - Removals: " . $moderationStats['removals'] . "\n";
    }
    
    // Test 7: Get flagged content
    echo "\nTest 7: Getting flagged content\n";
    $flaggedContent = $filterModel->getFlaggedContent(false, 10, 0);
    echo "✓ Found " . count($flaggedContent) . " flagged content items\n";
    
    // Test 8: Filter statistics
    echo "\nTest 8: Getting filter statistics\n";
    $filterStats = $filterModel->getFilterStats();
    if ($filterStats) {
        echo "✓ Filter stats:\n";
        echo "  - Total flags: " . $filterStats['total_flags'] . "\n";
        echo "  - Spam flags: " . $filterStats['spam_flags'] . "\n";
        echo "  - Pending review: " . $filterStats['pending_review'] . "\n";
    }
    
    echo "\n✓ All content moderation tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}