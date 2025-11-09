<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/ModerationAction.php';
require_once __DIR__ . '/../models/ContentFilter.php';
require_once __DIR__ . '/../models/SecurityMonitor.php';
require_once __DIR__ . '/../models/AuditLogger.php';
require_once __DIR__ . '/../models/SystemHealth.php';
require_once __DIR__ . '/../models/PlatformAnalytics.php';

class AdminModerationTest {
    private $db;
    private $testResults = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests() {
        echo "Running Admin and Moderation Tests\n";
        echo "==================================\n\n";
        
        $this->testContentReporting();
        $this->testModerationActions();
        $this->testContentFiltering();
        $this->testSecurityMonitoring();
        $this->testAuditLogging();
        $this->testSystemHealth();
        $this->testPlatformAnalytics();
        $this->testAdminUserManagement();
        $this->testIPBlocking();
        $this->testAlertSystem();
        
        $this->printTestSummary();
    }
    
    private function testContentReporting() {
        echo "Testing Content Reporting System\n";
        echo "-------------------------------\n";
        
        try {
            $reportModel = new Report();
            
            // Test 1: Create content report
            $report = $reportModel->createReport(
                1, // reporter_id
                'article',
                1, // content_id
                'spam',
                'This article contains spam content'
            );
            
            $this->assert($report !== false, "Create content report");
            $this->assert(isset($report['id']), "Report has ID");
            
            // Test 2: Get pending reports
            $pendingReports = $reportModel->getPendingReports(10, 0);
            $this->assert(is_array($pendingReports), "Get pending reports");
            
            // Test 3: Update report status
            $updated = $reportModel->updateReportStatus(
                $report['id'],
                'reviewing',
                1,
                'Under admin review'
            );
            $this->assert($updated === true, "Update report status");
            
            // Test 4: Get report statistics
            $stats = $reportModel->getReportStats();
            $this->assert(isset($stats['total_reports']), "Get report statistics");
            
            echo "✓ Content reporting tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Content reporting test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['content_reporting'] = false;
            return;
        }
        
        $this->testResults['content_reporting'] = true;
    }
    
    private function testModerationActions() {
        echo "Testing Moderation Actions\n";
        echo "-------------------------\n";
        
        try {
            $moderationModel = new ModerationAction();
            
            // Test 1: Log moderation action
            $actionId = $moderationModel->logAction(
                1, // admin_id
                'approve',
                'article',
                1,
                'Content approved after review'
            );
            
            $this->assert($actionId !== false, "Log moderation action");
            
            // Test 2: Approve content
            $approved = $moderationModel->approveContent(
                1, // admin_id
                'article',
                1,
                'Content meets community guidelines'
            );
            $this->assert($approved === true, "Approve content");
            
            // Test 3: Remove content
            $removed = $moderationModel->removeContent(
                1, // admin_id
                'article',
                2,
                'Content violates community guidelines'
            );
            $this->assert($removed === true, "Remove content");
            
            // Test 4: Warn user
            $warned = $moderationModel->warnUser(
                1, // admin_id
                2, // user_id
                'Inappropriate content posted'
            );
            $this->assert($warned === true, "Warn user");
            
            // Test 5: Get moderation history
            $history = $moderationModel->getActionHistory(null, null, 10, 0);
            $this->assert(is_array($history), "Get moderation history");
            
            // Test 6: Get moderation statistics
            $stats = $moderationModel->getModerationStats(30);
            $this->assert(isset($stats['total_actions']), "Get moderation statistics");
            
            echo "✓ Moderation actions tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Moderation actions test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['moderation_actions'] = false;
            return;
        }
        
        $this->testResults['moderation_actions'] = true;
    }
    
    private function testContentFiltering() {
        echo "Testing Content Filtering\n";
        echo "------------------------\n";
        
        try {
            $filterModel = new ContentFilter();
            
            // Test 1: Scan spam content
            $spamContent = "Buy now! Click here for free money! Limited time offer!";
            $flags = $filterModel->scanContent('article', 999, $spamContent);
            $this->assert(is_array($flags), "Scan spam content");
            $this->assert(count($flags) > 0, "Spam content flagged");
            
            // Test 2: Scan normal content
            $normalContent = "This is a normal article about technology.";
            $flags = $filterModel->scanContent('article', 998, $normalContent);
            $this->assert(is_array($flags), "Scan normal content");
            
            // Test 3: Get flagged content
            $flaggedContent = $filterModel->getFlaggedContent(false, 10, 0);
            $this->assert(is_array($flaggedContent), "Get flagged content");
            
            // Test 4: Mark flag as reviewed
            if (!empty($flaggedContent)) {
                $flagId = $flaggedContent[0]['id'];
                $reviewed = $filterModel->markFlagAsReviewed($flagId, 1);
                $this->assert($reviewed === true, "Mark flag as reviewed");
            }
            
            // Test 5: Get filter statistics
            $stats = $filterModel->getFilterStats();
            $this->assert(isset($stats['total_flags']), "Get filter statistics");
            
            echo "✓ Content filtering tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Content filtering test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['content_filtering'] = false;
            return;
        }
        
        $this->testResults['content_filtering'] = true;
    }
    
    private function testSecurityMonitoring() {
        echo "Testing Security Monitoring\n";
        echo "--------------------------\n";
        
        try {
            $securityMonitor = new SecurityMonitor();
            
            // Test 1: Log security event
            $eventId = $securityMonitor->logSecurityEvent(
                'failed_login',
                'medium',
                'Failed login attempt',
                1,
                '192.168.1.100',
                'Mozilla/5.0'
            );
            $this->assert($eventId !== false, "Log security event");
            
            // Test 2: Detect suspicious logins
            // Create multiple failed attempts
            for ($i = 0; $i < 6; $i++) {
                $securityMonitor->logSecurityEvent(
                    'failed_login',
                    'medium',
                    "Failed login #{$i}",
                    rand(1, 5),
                    '192.168.1.200',
                    'Mozilla/5.0'
                );
            }
            
            $suspiciousLogins = $securityMonitor->detectSuspiciousLogins(300);
            $this->assert(is_array($suspiciousLogins), "Detect suspicious logins");
            
            // Test 3: Block IP
            $blocked = $securityMonitor->blockIP(
                '192.168.1.200',
                'Multiple failed login attempts',
                1,
                60
            );
            $this->assert($blocked === true, "Block IP address");
            
            // Test 4: Check if IP is blocked
            $isBlocked = $securityMonitor->isIPBlocked('192.168.1.200');
            $this->assert($isBlocked !== false, "Check IP is blocked");
            
            // Test 5: Get security statistics
            $stats = $securityMonitor->getSecurityStats(30);
            $this->assert(isset($stats['total_events']), "Get security statistics");
            
            // Test 6: Generate security report
            $report = $securityMonitor->generateSecurityReport(30);
            $this->assert(isset($report['risk_level']), "Generate security report");
            
            echo "✓ Security monitoring tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Security monitoring test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['security_monitoring'] = false;
            return;
        }
        
        $this->testResults['security_monitoring'] = true;
    }
    
    private function testAuditLogging() {
        echo "Testing Audit Logging\n";
        echo "--------------------\n";
        
        try {
            $auditLogger = new AuditLogger();
            
            // Test 1: Log admin action
            $actionId = $auditLogger->logAction(
                1, // admin_id
                'user_suspend',
                'user',
                2, // target_id
                ['status' => 'active'],
                ['status' => 'suspended'],
                '192.168.1.1',
                'Mozilla/5.0'
            );
            $this->assert($actionId !== false, "Log admin action");
            
            // Test 2: Get audit log
            $auditLog = $auditLogger->getAuditLog([], 10, 0);
            $this->assert(is_array($auditLog), "Get audit log");
            
            // Test 3: Get audit statistics
            $stats = $auditLogger->getAuditStats(30);
            $this->assert(isset($stats['summary']), "Get audit statistics");
            
            // Test 4: Get admin activity
            $activity = $auditLogger->getAdminActivity(30);
            $this->assert(is_array($activity), "Get admin activity");
            
            // Test 5: Track data change
            $changeId = $auditLogger->trackDataChange(
                1, // admin_id
                'users',
                2, // record_id
                'email',
                'old@example.com',
                'new@example.com',
                '192.168.1.1'
            );
            $this->assert($changeId !== false, "Track data change");
            
            echo "✓ Audit logging tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Audit logging test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['audit_logging'] = false;
            return;
        }
        
        $this->testResults['audit_logging'] = true;
    }
    
    private function testSystemHealth() {
        echo "Testing System Health\n";
        echo "--------------------\n";
        
        try {
            $systemHealth = new SystemHealth();
            
            // Test 1: Record health metric
            $metricId = $systemHealth->recordMetric(
                'cpu_usage',
                75.5,
                '%',
                'warning'
            );
            $this->assert($metricId !== false, "Record health metric");
            
            // Test 2: Get current health status
            $healthStatus = $systemHealth->getCurrentHealthStatus();
            $this->assert(isset($healthStatus['overall_status']), "Get current health status");
            $this->assert(isset($healthStatus['health_score']), "Health status has score");
            
            // Test 3: Create system alert
            $alertId = $systemHealth->createAlert(
                'security',
                'warning',
                'Test Alert',
                'This is a test alert',
                ['test' => true]
            );
            $this->assert($alertId !== false, "Create system alert");
            
            // Test 4: Get active alerts
            $alerts = $systemHealth->getActiveAlerts(10);
            $this->assert(is_array($alerts), "Get active alerts");
            
            // Test 5: Resolve alert
            $resolved = $systemHealth->resolveAlert($alertId, 1);
            $this->assert($resolved === true, "Resolve alert");
            
            // Test 6: Get health history
            $history = $systemHealth->getHealthHistory(null, 24);
            $this->assert(is_array($history), "Get health history");
            
            echo "✓ System health tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ System health test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['system_health'] = false;
            return;
        }
        
        $this->testResults['system_health'] = true;
    }
    
    private function testPlatformAnalytics() {
        echo "Testing Platform Analytics\n";
        echo "-------------------------\n";
        
        try {
            $analytics = new PlatformAnalytics();
            
            // Test 1: Get user growth analytics
            $userGrowth = $analytics->getUserGrowthAnalytics(30);
            $this->assert(is_array($userGrowth), "Get user growth analytics");
            
            // Test 2: Get content analytics
            $contentAnalytics = $analytics->getContentAnalytics(30);
            $this->assert(is_array($contentAnalytics), "Get content analytics");
            
            // Test 3: Get engagement analytics
            $engagementAnalytics = $analytics->getEngagementAnalytics(30);
            $this->assert(is_array($engagementAnalytics), "Get engagement analytics");
            
            // Test 4: Get top articles
            $topArticles = $analytics->getTopArticles(30, 10);
            $this->assert(is_array($topArticles), "Get top articles");
            
            // Test 5: Get trending topics
            $trendingTopics = $analytics->getTrendingTopics(30, 10);
            $this->assert(is_array($trendingTopics), "Get trending topics");
            
            // Test 6: Get platform health metrics
            $healthMetrics = $analytics->getPlatformHealthMetrics();
            $this->assert(is_array($healthMetrics), "Get platform health metrics");
            
            // Test 7: Get comparative analytics
            $comparative = $analytics->getComparativeAnalytics(30, 30);
            $this->assert(is_array($comparative), "Get comparative analytics");
            
            echo "✓ Platform analytics tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Platform analytics test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['platform_analytics'] = false;
            return;
        }
        
        $this->testResults['platform_analytics'] = true;
    }
    
    private function testAdminUserManagement() {
        echo "Testing Admin User Management\n";
        echo "----------------------------\n";
        
        try {
            // Test user management through User model
            $userModel = new User();
            
            // Test 1: Get users for admin
            $users = $userModel->getUsers('', '', '', 10, 0);
            $this->assert(is_array($users), "Get users for admin");
            
            // Test 2: Get user count
            $userCount = $userModel->getUserCount();
            $this->assert(is_numeric($userCount), "Get user count");
            
            // Test 3: Create test user for management
            $testUser = $userModel->register([
                'username' => 'testuser_' . time(),
                'email' => 'test_' . time() . '@example.com',
                'password' => 'password123'
            ]);
            
            if ($testUser['success']) {
                $userId = $testUser['data']['id'];
                
                // Test 4: Update user role
                $roleUpdated = $userModel->updateRole($userId, 'moderator');
                $this->assert($roleUpdated === true, "Update user role");
                
                // Test 5: Suspend user
                $suspended = $userModel->suspendUser($userId, date('Y-m-d H:i:s', strtotime('+7 days')));
                $this->assert($suspended === true, "Suspend user");
                
                // Test 6: Unsuspend user
                $unsuspended = $userModel->unsuspendUser($userId);
                $this->assert($unsuspended === true, "Unsuspend user");
                
                // Test 7: Verify user
                $verified = $userModel->verifyUser($userId);
                $this->assert($verified === true, "Verify user");
            }
            
            echo "✓ Admin user management tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Admin user management test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['admin_user_management'] = false;
            return;
        }
        
        $this->testResults['admin_user_management'] = true;
    }
    
    private function testIPBlocking() {
        echo "Testing IP Blocking System\n";
        echo "-------------------------\n";
        
        try {
            $securityMonitor = new SecurityMonitor();
            
            // Test 1: Block IP with duration
            $blocked = $securityMonitor->blockIP(
                '10.0.0.100',
                'Suspicious activity detected',
                1,
                30 // 30 minutes
            );
            $this->assert($blocked === true, "Block IP with duration");
            
            // Test 2: Check if IP is blocked
            $isBlocked = $securityMonitor->isIPBlocked('10.0.0.100');
            $this->assert($isBlocked !== false, "Check IP is blocked");
            $this->assert(isset($isBlocked['expires_at']), "Blocked IP has expiration");
            
            // Test 3: Block IP permanently
            $blockedPerm = $securityMonitor->blockIP(
                '10.0.0.200',
                'Malicious activity',
                1,
                null // Permanent
            );
            $this->assert($blockedPerm === true, "Block IP permanently");
            
            // Test 4: Check permanent block
            $isPermanentlyBlocked = $securityMonitor->isIPBlocked('10.0.0.200');
            $this->assert($isPermanentlyBlocked !== false, "Check permanent IP block");
            $this->assert($isPermanentlyBlocked['expires_at'] === null, "Permanent block has no expiration");
            
            echo "✓ IP blocking tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ IP blocking test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['ip_blocking'] = false;
            return;
        }
        
        $this->testResults['ip_blocking'] = true;
    }
    
    private function testAlertSystem() {
        echo "Testing Alert System\n";
        echo "-------------------\n";
        
        try {
            $systemHealth = new SystemHealth();
            
            // Test 1: Create different types of alerts
            $securityAlert = $systemHealth->createAlert(
                'security',
                'critical',
                'Security Breach Detected',
                'Unauthorized access attempt detected'
            );
            $this->assert($securityAlert !== false, "Create security alert");
            
            $performanceAlert = $systemHealth->createAlert(
                'performance',
                'warning',
                'High CPU Usage',
                'CPU usage exceeded 90% threshold'
            );
            $this->assert($performanceAlert !== false, "Create performance alert");
            
            // Test 2: Get active alerts
            $activeAlerts = $systemHealth->getActiveAlerts(20);
            $this->assert(is_array($activeAlerts), "Get active alerts");
            $this->assert(count($activeAlerts) >= 2, "Multiple alerts retrieved");
            
            // Test 3: Resolve alerts
            $resolved1 = $systemHealth->resolveAlert($securityAlert, 1);
            $this->assert($resolved1 === true, "Resolve security alert");
            
            $resolved2 = $systemHealth->resolveAlert($performanceAlert, 1);
            $this->assert($resolved2 === true, "Resolve performance alert");
            
            // Test 4: Check resolved alerts are not in active list
            $activeAlertsAfter = $systemHealth->getActiveAlerts(20);
            $activeIds = array_column($activeAlertsAfter, 'id');
            $this->assert(!in_array($securityAlert, $activeIds), "Resolved alert not in active list");
            
            echo "✓ Alert system tests passed\n\n";
            
        } catch (Exception $e) {
            echo "✗ Alert system test failed: " . $e->getMessage() . "\n\n";
            $this->testResults['alert_system'] = false;
            return;
        }
        
        $this->testResults['alert_system'] = true;
    }
    
    private function assert($condition, $message) {
        if ($condition) {
            echo "  ✓ {$message}\n";
        } else {
            echo "  ✗ {$message}\n";
            throw new Exception("Assertion failed: {$message}");
        }
    }
    
    private function printTestSummary() {
        echo "Test Summary\n";
        echo "============\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        $failedTests = $totalTests - $passedTests;
        
        foreach ($this->testResults as $testName => $result) {
            $status = $result ? "✓ PASS" : "✗ FAIL";
            $testDisplayName = ucwords(str_replace('_', ' ', $testName));
            echo "{$status} - {$testDisplayName}\n";
        }
        
        echo "\nResults: {$passedTests}/{$totalTests} tests passed";
        if ($failedTests > 0) {
            echo " ({$failedTests} failed)";
        }
        echo "\n";
        
        if ($passedTests === $totalTests) {
            echo "\n🎉 All admin and moderation tests passed successfully!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review the output above.\n";
        }
    }
}

// Run the tests
$tester = new AdminModerationTest();
$tester->runAllTests();