<?php

/**
 * Performance monitoring service
 */
class PerformanceMonitor {
    private static $startTime;
    private static $queries = [];
    private static $memoryUsage = [];
    
    /**
     * Start performance monitoring
     */
    public static function start() {
        self::$startTime = microtime(true);
        self::$memoryUsage['start'] = memory_get_usage(true);
    }
    
    /**
     * End performance monitoring and return stats
     */
    public static function end() {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'execution_time' => round(($endTime - self::$startTime) * 1000, 2), // in milliseconds
            'memory_usage' => [
                'start' => self::formatBytes(self::$memoryUsage['start']),
                'end' => self::formatBytes($endMemory),
                'peak' => self::formatBytes($peakMemory),
                'used' => self::formatBytes($endMemory - self::$memoryUsage['start'])
            ],
            'queries' => [
                'count' => count(self::$queries),
                'total_time' => array_sum(array_column(self::$queries, 'time')),
                'queries' => self::$queries
            ]
        ];
    }
    
    /**
     * Log database query
     */
    public static function logQuery($sql, $params = [], $executionTime = 0) {
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $executionTime,
            'memory' => memory_get_usage(true)
        ];
    }
    
    /**
     * Get current performance stats
     */
    public static function getCurrentStats() {
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        
        return [
            'elapsed_time' => round(($currentTime - self::$startTime) * 1000, 2),
            'current_memory' => self::formatBytes($currentMemory),
            'queries_count' => count(self::$queries)
        ];
    }
    
    /**
     * Check if performance is within acceptable limits
     */
    public static function checkPerformance($maxExecutionTime = 1000, $maxMemoryUsage = 50 * 1024 * 1024) {
        $stats = self::getCurrentStats();
        $currentMemory = memory_get_usage(true);
        
        $issues = [];
        
        if ($stats['elapsed_time'] > $maxExecutionTime) {
            $issues[] = "Execution time ({$stats['elapsed_time']}ms) exceeds limit ({$maxExecutionTime}ms)";
        }
        
        if ($currentMemory > $maxMemoryUsage) {
            $issues[] = "Memory usage (" . self::formatBytes($currentMemory) . ") exceeds limit (" . self::formatBytes($maxMemoryUsage) . ")";
        }
        
        if (count(self::$queries) > 20) {
            $issues[] = "Too many database queries (" . count(self::$queries) . ")";
        }
        
        return [
            'is_acceptable' => empty($issues),
            'issues' => $issues,
            'stats' => $stats
        ];
    }
    
    /**
     * Log slow queries
     */
    public static function logSlowQueries($threshold = 100) {
        $slowQueries = array_filter(self::$queries, function($query) use ($threshold) {
            return $query['time'] > $threshold;
        });
        
        if (!empty($slowQueries)) {
            error_log("Slow queries detected: " . json_encode($slowQueries));
        }
        
        return $slowQueries;
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Enhanced Database class with performance monitoring
 */
class PerformanceDatabase extends Database {
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $result = parent::query($sql, $params);
            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            PerformanceMonitor::logQuery($sql, $params, $executionTime);
            
            return $result;
        } catch (Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            PerformanceMonitor::logQuery($sql, $params, $executionTime);
            throw $e;
        }
    }
}

/**
 * Performance middleware for API endpoints
 */
class PerformanceMiddleware {
    public static function start() {
        PerformanceMonitor::start();
        
        // Set response headers for performance monitoring
        header('X-Performance-Monitor: enabled');
    }
    
    public static function end($includeStats = false) {
        $stats = PerformanceMonitor::end();
        
        // Add performance headers
        header('X-Execution-Time: ' . $stats['execution_time'] . 'ms');
        header('X-Memory-Usage: ' . $stats['memory_usage']['used']);
        header('X-Query-Count: ' . $stats['queries']['count']);
        
        // Check for performance issues
        $performance = PerformanceMonitor::checkPerformance();
        if (!$performance['is_acceptable']) {
            header('X-Performance-Warning: ' . implode('; ', $performance['issues']));
            error_log('Performance warning: ' . json_encode($performance));
        }
        
        // Log slow queries
        PerformanceMonitor::logSlowQueries();
        
        // Include stats in response if requested
        if ($includeStats) {
            return $stats;
        }
    }
}