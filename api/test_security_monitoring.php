<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/SecurityMonitor.php';
require_once __DIR__ . '/models/AuditLogger.php';
require_once __DIR__ . '/models/SystemHealth.php';

echo "Testing Security and Monitoring System\n";
echo "=====================================\n\n";

try {
    // Test 1: Security Event Logging
    echo "Test 1: Security Event Logging\n";
    $securityMonitor = new SecurityMonitor();
    
    // Log various security events
    $eventId1 = $securityMonitor->logSecurityEvent(
        'failed_login',
        'medium',
        'Failed login attempt from suspicious IP',
        1,
        '192.168.1.100',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    );
    
    $eventId2 = $securityMonitor->logSecurityEvent(
        'unauthorized_access',
        'high',
        'Attempt to access admin panel without proper credentials',
        null,
        '10.0.0.50',
        'curl/7.68.0'
    );
    
    if ($eventId1 && $eventId2) {
        echo "✓ Security events logged successfully (IDs: {$eventId1}, {$eventId2})\n";
    } else {
        echo "✗ Failed to log security events\n";
    }
    
    // Test 2: Suspicious Login Detection
    echo "\nTest 2: Suspicious Login Detection\n";
    
    // Create multiple failed login attempts from same IP
    for ($i = 0; $i < 6; $i++) {
        $securityMonitor->logSecurityEvent(
            'failed_login',
            'medium',
            "Failed login attempt #{$i}",
            rand(1, 10),
            '192.168.1.200',
            'Mozilla/5.0'
        );
    }
    
    $suspiciousLogins = $securityMonitor->detectSuspiciousLogins(300);
    echo "✓ Detected " . count($suspiciousLogins) . " suspicious login patterns\n";
    
    foreach ($suspiciousLogins as $login) {
        echo "  - IP: " . $login['ip_address'] . " with " . $login['attempt_count'] . " attempts\n";
    }
    
    // Test 3: Security Statistics
    echo "\nTest 3: Security Statistics\n";
    $securityStats = $securityMonitor->getSecurityStats(30);
    if ($securityStats) {
        echo "✓ Security stats retrieved:\n";
        echo "  - Total events: " . $securityStats['total_events'] . "\n";
        echo "  - Critical events: " . $securityStats['critical_events'] . "\n";
        echo "  - High events: " . $securityStats['high_events'] . "\n";
        echo "  - Unique IPs: " . $securityStats['unique_ips'] . "\n";
    }
    
    // Test 4: IP Blocking
    echo "\nTest 4: IP Blocking\n";
    $blockResult = $securityMonitor->blockIP(
        '192.168.1.200',
        'Multiple failed login attempts',
        1,
        60 // 60 minutes
    );
    
    if ($blockResult) {
        echo "✓ IP address blocked successfully\n";
        
        // Test if IP is blocked
        $isBlocked = $securityMonitor->isIPBlocked('192.168.1.200');
        if ($isBlocked) {
            echo "✓ IP block verification successful\n";
        } else {
            echo "✗ IP block verification failed\n";
        }
    } else {
        echo "✗ Failed to block IP address\n";
    }
    
    // Test 5: Audit Logging
    echo "\nTest 5: Audit Logging\n";
    $auditLogger = new AuditLogger();
    
    $auditId = $auditLogger->logAction(
        1, // admin_id
        'user_suspend',
        'user',
        5, // target user ID
        ['status' => 'active'],
        ['status' => 'suspended', 'reason' => 'Policy violation'],
        '192.168.1.1',
        'Mozilla/5.0'
    );
    
    if ($auditId) {
        echo "✓ Audit action logged successfully (ID: {$auditId})\n";
    } else {
        echo "✗ Failed to log audit action\n";
    }
    
    // Test audit statistics
    $auditStats = $auditLogger->getAuditStats(30);
    if ($auditStats) {
        echo "✓ Audit stats retrieved:\n";
        echo "  - Total actions: " . $auditStats['summary']['total_actions'] . "\n";
        echo "  - Active admins: " . $auditStats['summary']['active_admins'] . "\n";
    }
    
    // Test 6: System Health Monitoring
    echo "\nTest 6: System Health Monitoring\n";
    $systemHealth = new SystemHealth();
    
    // Record some test metrics
    $metricId1 = $systemHealth->recordMetric('cpu_usage', 75.5, '%', 'warning');
    $metricId2 = $systemHealth->recordMetric('memory_usage', 85.2, '%', 'warning');
    $metricId3 = $systemHealth->recordMetric('response_time', 250, 'ms', 'normal');
    
    if ($metricId1 && $metricId2 && $metricId3) {
        echo "✓ Health metrics recorded successfully\n";
    }
    
    // Get current health status
    $healthStatus = $systemHealth->getCurrentHealthStatus();
    if ($healthStatus) {
        echo "✓ System health status retrieved:\n";
        echo "  - Overall status: " . $healthStatus['overall_status'] . "\n";
        echo "  - Health score: " . $healthStatus['health_score'] . "\n";
        echo "  - Database status: " . $healthStatus['metrics']['database_connections']['status'] . "\n";
        echo "  - Memory usage: " . $healthStatus['metrics']['memory_usage']['usage_percent'] . "%\n";
    }
    
    // Test 7: System Alerts
    echo "\nTest 7: System Alerts\n";
    $alertId = $systemHealth->createAlert(
        'security',
        'warning',
        'Multiple Failed Login Attempts',
        'Detected multiple failed login attempts from IP 192.168.1.200',
        ['ip_address' => '192.168.1.200', 'attempt_count' => 6]
    );
    
    if ($alertId) {
        echo "✓ System alert created successfully (ID: {$alertId})\n";
        
        // Get active alerts
        $activeAlerts = $systemHealth->getActiveAlerts(10);
        echo "✓ Retrieved " . count($activeAlerts) . " active alerts\n";
        
        // Resolve the alert
        $resolved = $systemHealth->resolveAlert($alertId, 1);
        if ($resolved) {
            echo "✓ Alert resolved successfully\n";
        }
    }
    
    // Test 8: Data Breach Detection
    echo "\nTest 8: Data Breach Detection\n";
    
    // Simulate suspicious data access attempts
    for ($i = 0; $i < 12; $i++) {
        $securityMonitor->logSecurityEvent(
            'data_export',
            'high',
            "Bulk data export attempt #{$i}",
            2,
            '10.0.0.100',
            'Python-requests/2.25.1'
        );
    }
    
    $breachAttempts = $securityMonitor->detectDataBreachAttempts();
    echo "✓ Detected " . count($breachAttempts) . " potential data breach attempts\n";
    
    foreach ($breachAttempts as $attempt) {
        echo "  - User ID: " . $attempt['user_id'] . ", IP: " . $attempt['ip_address'] . 
             ", Attempts: " . $attempt['access_attempts'] . "\n";
    }
    
    // Test 9: Security Report Generation
    echo "\nTest 9: Security Report Generation\n";
    $securityReport = $securityMonitor->generateSecurityReport(30);
    if ($securityReport) {
        echo "✓ Security report generated successfully:\n";
        echo "  - Risk level: " . $securityReport['risk_level'] . "\n";
        echo "  - Risk score: " . $securityReport['risk_score'] . "\n";
        echo "  - Report period: " . $securityReport['period'] . " days\n";
        echo "  - Top threats: " . count($securityReport['top_threats']) . "\n";
    }
    
    // Test 10: Cleanup Operations
    echo "\nTest 10: Cleanup Operations\n";
    $cleanupResults = $securityMonitor->cleanupExpiredData();
    if ($cleanupResults) {
        echo "✓ Cleanup completed:\n";
        echo "  - Expired blocks removed: " . $cleanupResults['expired_blocks'] . "\n";
        echo "  - Events archived: " . $cleanupResults['archived_events'] . "\n";
    }
    
    echo "\n✓ All security and monitoring tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}