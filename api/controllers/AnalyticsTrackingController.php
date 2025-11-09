<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/CacheService.php';

class AnalyticsTrackingController {
    private $db;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = new CacheService();
    }
    
    /**
     * Track analytics events
     */
    public function trackEvent() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ]);
                return;
            }
            
            // Validate required fields
            $requiredFields = ['action', 'category'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => "Missing required field: {$field}"
                    ]);
                    return;
                }
            }
            
            // Store analytics event
            $eventId = $this->storeAnalyticsEvent($input);
            
            // Process real-time analytics
            $this->processRealTimeAnalytics($input);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'event_id' => $eventId
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to track event: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get analytics dashboard data
     */
    public function getDashboardData() {
        try {
            $timeframe = $_GET['timeframe'] ?? '7d';
            $cacheKey = "analytics_dashboard_{$timeframe}";
            
            // Try to get from cache first
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true
                ]);
                return;
            }
            
            $data = [
                'overview' => $this->getOverviewStats($timeframe),
                'page_views' => $this->getPageViewStats($timeframe),
                'user_engagement' => $this->getUserEngagementStats($timeframe),
                'content_performance' => $this->getContentPerformanceStats($timeframe),
                'real_time' => $this->getRealTimeStats()
            ];
            
            // Cache for 5 minutes
            $this->cache->set($cacheKey, $data, 300);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get dashboard data: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics() {
        try {
            $data = [
                'active_users' => $this->getActiveUsersCount(),
                'current_page_views' => $this->getCurrentPageViews(),
                'recent_events' => $this->getRecentEvents(50),
                'top_content' => $this->getTopContentRealTime()
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get real-time analytics: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Store analytics event in database
     */
    private function storeAnalyticsEvent($eventData) {
        $stmt = $this->db->prepare("
            INSERT INTO analytics_events (
                action, category, label, value, session_id, user_id, 
                url, referrer, user_agent, custom_parameters, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $eventData['action'],
            $eventData['category'],
            $eventData['label'] ?? null,
            $eventData['value'] ?? null,
            $eventData['session_id'] ?? null,
            $eventData['user_id'] ?? null,
            $eventData['url'] ?? null,
            $eventData['referrer'] ?? null,
            $eventData['user_agent'] ?? null,
            json_encode($eventData['custom_parameters'] ?? [])
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Process real-time analytics
     */
    private function processRealTimeAnalytics($eventData) {
        // Update real-time counters in cache
        $action = $eventData['action'];
        $category = $eventData['category'];
        
        // Increment action counter
        $actionKey = "realtime_action_{$action}";
        $currentCount = $this->cache->get($actionKey) ?? 0;
        $this->cache->set($actionKey, $currentCount + 1, 3600); // 1 hour TTL
        
        // Increment category counter
        $categoryKey = "realtime_category_{$category}";
        $currentCount = $this->cache->get($categoryKey) ?? 0;
        $this->cache->set($categoryKey, $currentCount + 1, 3600);
        
        // Track active sessions
        if (isset($eventData['session_id'])) {
            $sessionKey = "active_session_{$eventData['session_id']}";
            $this->cache->set($sessionKey, time(), 1800); // 30 minutes TTL
        }
        
        // Track page views
        if ($action === 'page_view' && isset($eventData['url'])) {
            $pageKey = "page_views_" . md5($eventData['url']);
            $currentViews = $this->cache->get($pageKey) ?? 0;
            $this->cache->set($pageKey, $currentViews + 1, 3600);
        }
    }
    
    /**
     * Get overview statistics
     */
    private function getOverviewStats($timeframe) {
        $days = $this->getTimeframeDays($timeframe);
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT session_id) as unique_sessions,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(CASE WHEN action = 'page_view' THEN 1 ELSE 0 END) as page_views
            FROM analytics_events 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get page view statistics
     */
    private function getPageViewStats($timeframe) {
        $days = $this->getTimeframeDays($timeframe);
        
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as unique_views
            FROM analytics_events 
            WHERE action = 'page_view' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user engagement statistics
     */
    private function getUserEngagementStats($timeframe) {
        $days = $this->getTimeframeDays($timeframe);
        
        $stmt = $this->db->prepare("
            SELECT 
                action,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM analytics_events 
            WHERE category = 'engagement' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY count DESC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get content performance statistics
     */
    private function getContentPerformanceStats($timeframe) {
        $days = $this->getTimeframeDays($timeframe);
        
        $stmt = $this->db->prepare("
            SELECT 
                JSON_EXTRACT(custom_parameters, '$.article_id') as article_id,
                COUNT(*) as total_interactions,
                SUM(CASE WHEN action = 'view' THEN 1 ELSE 0 END) as views,
                SUM(CASE WHEN action = 'clap' THEN 1 ELSE 0 END) as claps,
                SUM(CASE WHEN action = 'comment' THEN 1 ELSE 0 END) as comments,
                SUM(CASE WHEN action = 'share' THEN 1 ELSE 0 END) as shares
            FROM analytics_events 
            WHERE category = 'article' 
            AND JSON_EXTRACT(custom_parameters, '$.article_id') IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY JSON_EXTRACT(custom_parameters, '$.article_id')
            ORDER BY total_interactions DESC
            LIMIT 20
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get real-time statistics
     */
    private function getRealTimeStats() {
        return [
            'active_users' => $this->getActiveUsersCount(),
            'page_views_last_hour' => $this->getPageViewsLastHour(),
            'top_pages' => $this->getTopPagesRealTime()
        ];
    }
    
    /**
     * Get active users count
     */
    private function getActiveUsersCount() {
        // Count active sessions from cache
        $pattern = "active_session_*";
        $activeSessions = 0;
        
        // This is a simplified implementation
        // In production, you'd use Redis with pattern matching
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT session_id) as active_users
            FROM analytics_events 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['active_users'] ?? 0;
    }
    
    /**
     * Get current page views
     */
    private function getCurrentPageViews() {
        $stmt = $this->db->prepare("
            SELECT url, COUNT(*) as views
            FROM analytics_events 
            WHERE action = 'page_view' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY url
            ORDER BY views DESC
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent events
     */
    private function getRecentEvents($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT action, category, label, url, created_at
            FROM analytics_events 
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top content in real-time
     */
    private function getTopContentRealTime() {
        $stmt = $this->db->prepare("
            SELECT 
                JSON_EXTRACT(custom_parameters, '$.article_id') as article_id,
                COUNT(*) as interactions
            FROM analytics_events 
            WHERE category = 'article' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND JSON_EXTRACT(custom_parameters, '$.article_id') IS NOT NULL
            GROUP BY JSON_EXTRACT(custom_parameters, '$.article_id')
            ORDER BY interactions DESC
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get page views in the last hour
     */
    private function getPageViewsLastHour() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as views
            FROM analytics_events 
            WHERE action = 'page_view' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['views'] ?? 0;
    }
    
    /**
     * Get top pages in real-time
     */
    private function getTopPagesRealTime() {
        $stmt = $this->db->prepare("
            SELECT url, COUNT(*) as views
            FROM analytics_events 
            WHERE action = 'page_view' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY url
            ORDER BY views DESC
            LIMIT 5
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Convert timeframe to days
     */
    private function getTimeframeDays($timeframe) {
        switch ($timeframe) {
            case '1d': return 1;
            case '7d': return 7;
            case '30d': return 30;
            case '90d': return 90;
            default: return 7;
        }
    }
}