<?php

class SecurityMonitor extends BaseRepository {
    protected $table = 'security_events';
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Log a security event
     */
    public function logSecurityEvent($eventType, $severity, $description, $userId = null, $ipAddress = null, $userAgent = null, $metadata = null) {
        try {
            $sql = "INSERT INTO {$this->table} (event_type, severity, description, user_id, ip_address, user_agent, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $eventType,
                $severity,
                $description,
                $userId,
                $ipAddress,
                $userAgent,
                $metadata ? json_encode($metadata) : null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to log security event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect suspicious login attempts
     */
    public function detectSuspiciousLogins($timeWindow = 300) { // 5 minutes
        $sql = "SELECT 
                    ip_address,
                    COUNT(*) as attempt_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    MIN(created_at) as first_attempt,
                    MAX(created_at) as last_attempt
                FROM {$this->table}
                WHERE event_type = 'failed_login' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY ip_address
                HAVING attempt_count >= 5 OR unique_users >= 3
                ORDER BY attempt_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$timeWindow]);
        return $stmt->fetchAll();
    }

    /**
     * Detect unusual user activity patterns
     */
    public function detectUnusualActivity($userId, $hours = 24) {
        // Get user's typical activity pattern
        $sql = "SELECT 
                    AVG(hourly_actions) as avg_hourly_actions,
                    STDDEV(hourly_actions) as stddev_hourly_actions
                FROM (
                    SELECT 
                        DATE(created_at) as date,
                        HOUR(created_at) as hour,
                        COUNT(*) as hourly_actions
                    FROM {$this->table}
                    WHERE user_id = ? 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
                    GROUP BY DATE(created_at), HOUR(created_at)
                ) hourly_stats";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $hours]);
        $baseline = $stmt->fetch();
        
        if (!$baseline || !$baseline['avg_hourly_actions']) {
            return null; // Not enough data
        }
        
        // Check recent activity
        $sql = "SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as actions
                FROM {$this->table}
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY HOUR(created_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $hours]);
        $recentActivity = $stmt->fetchAll();
        
        $anomalies = [];
        $threshold = $baseline['avg_hourly_actions'] + (2 * $baseline['stddev_hourly_actions']);
        
        foreach ($recentActivity as $activity) {
            if ($activity['actions'] > $threshold) {
                $anomalies[] = [
                    'hour' => $activity['hour'],
                    'actions' => $activity['actions'],
                    'threshold' => $threshold,
                    'severity' => $activity['actions'] > ($threshold * 2) ? 'high' : 'medium'
                ];
            }
        }
        
        return $anomalies;
    }

    /**
     * Monitor for potential data breaches
     */
    public function detectDataBreachAttempts() {
        $sql = "SELECT 
                    user_id,
                    ip_address,
                    COUNT(*) as access_attempts,
                    COUNT(DISTINCT event_type) as event_types,
                    GROUP_CONCAT(DISTINCT event_type) as events
                FROM {$this->table}
                WHERE event_type IN ('unauthorized_access', 'data_export', 'bulk_download', 'admin_escalation')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY user_id, ip_address
                HAVING access_attempts >= 10 OR event_types >= 3
                ORDER BY access_attempts DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get security events with filtering
     */
    public function getSecurityEvents($filters = [], $limit = 50, $offset = 0) {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['event_type'])) {
            $whereConditions[] = "event_type = ?";
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['severity'])) {
            $whereConditions[] = "severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (!empty($filters['user_id'])) {
            $whereConditions[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['ip_address'])) {
            $whereConditions[] = "ip_address = ?";
            $params[] = $filters['ip_address'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT se.*, u.username
                FROM {$this->table} se
                LEFT JOIN users u ON se.user_id = u.id
                WHERE {$whereClause}
                ORDER BY se.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_events,
                    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_events,
                    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_events,
                    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_events,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(DISTINCT user_id) as affected_users
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetch();
    }

    /**
     * Get top security threats
     */
    public function getTopThreats($days = 30, $limit = 10) {
        $sql = "SELECT 
                    event_type,
                    COUNT(*) as event_count,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(DISTINCT user_id) as affected_users,
                    MAX(created_at) as last_occurrence
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY event_type
                ORDER BY event_count DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Block suspicious IP address
     */
    public function blockIP($ipAddress, $reason, $adminId = null, $duration = null) {
        try {
            $expiresAt = $duration ? date('Y-m-d H:i:s', strtotime("+{$duration} minutes")) : null;
            
            $sql = "INSERT INTO ip_blocks (ip_address, reason, blocked_by, expires_at) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason),
                    blocked_by = VALUES(blocked_by),
                    expires_at = VALUES(expires_at),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ipAddress, $reason, $adminId, $expiresAt]);
            
            // Log the blocking action
            $this->logSecurityEvent(
                'ip_blocked',
                'medium',
                "IP address {$ipAddress} blocked: {$reason}",
                $adminId,
                $ipAddress,
                null,
                ['duration' => $duration, 'expires_at' => $expiresAt]
            );
            
            return true;
        } catch (Exception $e) {
            error_log('Failed to block IP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ipAddress) {
        $sql = "SELECT * FROM ip_blocks 
                WHERE ip_address = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND is_active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ipAddress]);
        return $stmt->fetch();
    }

    /**
     * Clean up expired blocks and events
     */
    public function cleanupExpiredData() {
        try {
            // Remove expired IP blocks
            $sql = "UPDATE ip_blocks SET is_active = FALSE 
                    WHERE expires_at IS NOT NULL AND expires_at <= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $expiredBlocks = $stmt->rowCount();
            
            // Archive old security events (older than 1 year)
            $sql = "DELETE FROM {$this->table} 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    AND severity IN ('low', 'medium')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $archivedEvents = $stmt->rowCount();
            
            return [
                'expired_blocks' => $expiredBlocks,
                'archived_events' => $archivedEvents
            ];
        } catch (Exception $e) {
            error_log('Failed to cleanup expired data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate security report
     */
    public function generateSecurityReport($days = 30) {
        $report = [
            'period' => $days,
            'generated_at' => date('Y-m-d H:i:s'),
            'stats' => $this->getSecurityStats($days),
            'top_threats' => $this->getTopThreats($days, 10),
            'suspicious_logins' => $this->detectSuspiciousLogins(3600), // Last hour
            'breach_attempts' => $this->detectDataBreachAttempts()
        ];
        
        // Calculate risk score
        $stats = $report['stats'];
        $riskScore = 0;
        
        if ($stats['critical_events'] > 0) $riskScore += $stats['critical_events'] * 10;
        if ($stats['high_events'] > 0) $riskScore += $stats['high_events'] * 5;
        if ($stats['medium_events'] > 0) $riskScore += $stats['medium_events'] * 2;
        
        $report['risk_score'] = min(100, $riskScore);
        $report['risk_level'] = $this->getRiskLevel($report['risk_score']);
        
        return $report;
    }

    private function getRiskLevel($score) {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'medium';
        if ($score >= 20) return 'low';
        return 'minimal';
    }
}