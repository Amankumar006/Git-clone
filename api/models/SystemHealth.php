<?php

class SystemHealth extends BaseRepository {
    protected $table = 'system_health_metrics';
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Record a health metric
     */
    public function recordMetric($metricName, $value, $unit = null, $status = 'normal', $metadata = null) {
        try {
            $sql = "INSERT INTO {$this->table} (metric_name, metric_value, metric_unit, status, metadata) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $metricName,
                $value,
                $unit,
                $status,
                $metadata ? json_encode($metadata) : null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to record health metric: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current system health status
     */
    public function getCurrentHealthStatus() {
        $metrics = [
            'database_connections' => $this->checkDatabaseHealth(),
            'response_time' => $this->checkResponseTime(),
            'memory_usage' => $this->checkMemoryUsage(),
            'disk_space' => $this->checkDiskSpace(),
            'error_rate' => $this->checkErrorRate(),
            'active_users' => $this->checkActiveUsers()
        ];
        
        // Calculate overall health score
        $healthScore = $this->calculateHealthScore($metrics);
        
        return [
            'overall_status' => $this->getStatusFromScore($healthScore),
            'health_score' => $healthScore,
            'metrics' => $metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth() {
        try {
            $startTime = microtime(true);
            
            // Test database connection and simple query
            $sql = "SELECT COUNT(*) as count FROM users LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            $status = 'normal';
            if ($responseTime > 1000) {
                $status = 'critical';
            } elseif ($responseTime > 500) {
                $status = 'warning';
            }
            
            $this->recordMetric('database_response_time', $responseTime, 'ms', $status);
            
            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'connection_active' => true
            ];
        } catch (Exception $e) {
            $this->recordMetric('database_response_time', 0, 'ms', 'critical', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'response_time_ms' => 0,
                'connection_active' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check API response time
     */
    private function checkResponseTime() {
        try {
            $startTime = microtime(true);
            
            // Simulate a typical API operation
            $sql = "SELECT COUNT(*) FROM articles WHERE status = 'published' LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stmt->fetchAll();
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $status = 'normal';
            if ($responseTime > 2000) {
                $status = 'critical';
            } elseif ($responseTime > 1000) {
                $status = 'warning';
            }
            
            $this->recordMetric('api_response_time', $responseTime, 'ms', $status);
            
            return [
                'status' => $status,
                'avg_response_time_ms' => round($responseTime, 2)
            ];
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'avg_response_time_ms' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        
        $status = 'normal';
        if ($memoryPercent > 90) {
            $status = 'critical';
        } elseif ($memoryPercent > 75) {
            $status = 'warning';
        }
        
        $this->recordMetric('memory_usage_percent', $memoryPercent, '%', $status);
        
        return [
            'status' => $status,
            'usage_bytes' => $memoryUsage,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percent' => round($memoryPercent, 2)
        ];
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace() {
        $diskFree = disk_free_space('.');
        $diskTotal = disk_total_space('.');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = ($diskUsed / $diskTotal) * 100;
        
        $status = 'normal';
        if ($diskPercent > 95) {
            $status = 'critical';
        } elseif ($diskPercent > 85) {
            $status = 'warning';
        }
        
        $this->recordMetric('disk_usage_percent', $diskPercent, '%', $status);
        
        return [
            'status' => $status,
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
            'usage_percent' => round($diskPercent, 2)
        ];
    }

    /**
     * Check error rate
     */
    private function checkErrorRate() {
        try {
            // Check for recent errors in security events
            $sql = "SELECT COUNT(*) as error_count
                    FROM security_events 
                    WHERE severity IN ('high', 'critical') 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $errorCount = $result['error_count'];
            
            $status = 'normal';
            if ($errorCount > 10) {
                $status = 'critical';
            } elseif ($errorCount > 5) {
                $status = 'warning';
            }
            
            $this->recordMetric('error_rate_hourly', $errorCount, 'count', $status);
            
            return [
                'status' => $status,
                'errors_last_hour' => $errorCount
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'errors_last_hour' => 0,
                'error' => 'Could not check error rate'
            ];
        }
    }

    /**
     * Check active users
     */
    private function checkActiveUsers() {
        try {
            $sql = "SELECT COUNT(DISTINCT user_id) as active_users
                    FROM (
                        SELECT author_id as user_id FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        UNION
                        SELECT user_id FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        UNION
                        SELECT user_id FROM claps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) active_users";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $activeUsers = $result['active_users'];
            
            $this->recordMetric('active_users_24h', $activeUsers, 'count', 'normal');
            
            return [
                'status' => 'normal',
                'active_users_24h' => $activeUsers
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'active_users_24h' => 0,
                'error' => 'Could not check active users'
            ];
        }
    }

    /**
     * Get health metrics history
     */
    public function getHealthHistory($metricName = null, $hours = 24) {
        $whereClause = "recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
        $params = [$hours];
        
        if ($metricName) {
            $whereClause .= " AND metric_name = ?";
            $params[] = $metricName;
        }
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$whereClause}
                ORDER BY recorded_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create system alert
     */
    public function createAlert($type, $severity, $title, $message, $metadata = null) {
        try {
            $sql = "INSERT INTO system_alerts (alert_type, severity, title, message, metadata) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $type,
                $severity,
                $title,
                $message,
                $metadata ? json_encode($metadata) : null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to create system alert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active alerts
     */
    public function getActiveAlerts($limit = 50) {
        $sql = "SELECT * FROM system_alerts 
                WHERE is_resolved = FALSE 
                ORDER BY severity DESC, created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Resolve alert
     */
    public function resolveAlert($alertId, $resolvedBy) {
        try {
            $sql = "UPDATE system_alerts 
                    SET is_resolved = TRUE, resolved_by = ?, resolved_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$resolvedBy, $alertId]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Failed to resolve alert: ' . $e->getMessage());
            return false;
        }
    }

    private function calculateHealthScore($metrics) {
        $score = 100;
        
        foreach ($metrics as $metric) {
            if (isset($metric['status'])) {
                switch ($metric['status']) {
                    case 'critical':
                        $score -= 25;
                        break;
                    case 'warning':
                        $score -= 10;
                        break;
                }
            }
        }
        
        return max(0, $score);
    }

    private function getStatusFromScore($score) {
        if ($score >= 90) return 'healthy';
        if ($score >= 70) return 'warning';
        if ($score >= 50) return 'degraded';
        return 'critical';
    }

    private function parseMemoryLimit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;
        
        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
}