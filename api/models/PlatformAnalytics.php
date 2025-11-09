<?php

class PlatformAnalytics extends BaseRepository {
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get user growth analytics
     */
    public function getUserGrowthAnalytics($days = 30) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as new_users,
                    SUM(COUNT(*)) OVER (ORDER BY DATE(created_at)) as cumulative_users
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Get content creation analytics
     */
    public function getContentAnalytics($days = 30) {
        $sql = "SELECT 
                    DATE(a.created_at) as date,
                    COUNT(a.id) as articles_created,
                    COUNT(CASE WHEN a.status = 'published' THEN 1 END) as articles_published,
                    COALESCE(c.comments_count, 0) as comments_created
                FROM articles a
                LEFT JOIN (
                    SELECT DATE(created_at) as date, COUNT(*) as comments_count
                    FROM comments 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(created_at)
                ) c ON DATE(a.created_at) = c.date
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(a.created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get engagement analytics
     */
    public function getEngagementAnalytics($days = 30) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    'claps' as type,
                    COUNT(*) as count
                FROM claps 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT 
                    DATE(created_at) as date,
                    'comments' as type,
                    COUNT(*) as count
                FROM comments 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT 
                    DATE(created_at) as date,
                    'bookmarks' as type,
                    COUNT(*) as count
                FROM bookmarks 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT 
                    DATE(created_at) as date,
                    'follows' as type,
                    COUNT(*) as count
                FROM follows 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                
                ORDER BY date ASC, type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $days, $days, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get top performing articles
     */
    public function getTopArticles($days = 30, $limit = 10) {
        $sql = "SELECT 
                    a.id,
                    a.title,
                    a.view_count,
                    a.clap_count,
                    a.comment_count,
                    u.username as author_username,
                    a.published_at,
                    (a.view_count * 1 + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                WHERE a.status = 'published' 
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY engagement_score DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get trending topics/tags
     */
    public function getTrendingTopics($days = 30, $limit = 10) {
        $sql = "SELECT 
                    t.name,
                    t.slug,
                    COUNT(at.article_id) as article_count,
                    SUM(a.view_count) as total_views,
                    SUM(a.clap_count) as total_claps,
                    AVG(a.view_count) as avg_views_per_article
                FROM tags t
                JOIN article_tags at ON t.id = at.tag_id
                JOIN articles a ON at.article_id = a.id
                WHERE a.status = 'published' 
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY t.id, t.name, t.slug
                ORDER BY (article_count * 2 + total_views * 0.1 + total_claps * 5) DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get user engagement patterns
     */
    public function getUserEngagementPatterns($days = 30) {
        $sql = "SELECT 
                    HOUR(created_at) as hour_of_day,
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as activity_count,
                    'articles' as activity_type
                FROM articles 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
                
                UNION ALL
                
                SELECT 
                    HOUR(created_at) as hour_of_day,
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as activity_count,
                    'comments' as activity_type
                FROM comments 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
                
                ORDER BY day_of_week, hour_of_day";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get platform health metrics
     */
    public function getPlatformHealthMetrics() {
        $sql = "SELECT 
                    'active_users_24h' as metric,
                    COUNT(DISTINCT user_id) as value
                FROM (
                    SELECT author_id as user_id FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    UNION
                    SELECT user_id FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    UNION
                    SELECT user_id FROM claps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ) active_users
                
                UNION ALL
                
                SELECT 
                    'articles_published_24h' as metric,
                    COUNT(*) as value
                FROM articles 
                WHERE status = 'published' AND published_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                
                UNION ALL
                
                SELECT 
                    'avg_reading_time' as metric,
                    AVG(reading_time) as value
                FROM articles 
                WHERE status = 'published' AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 
                    'engagement_rate' as metric,
                    (SUM(clap_count + comment_count) / SUM(view_count)) * 100 as value
                FROM articles 
                WHERE status = 'published' AND view_count > 0 AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get retention analytics
     */
    public function getRetentionAnalytics($cohortDays = 30) {
        $sql = "SELECT 
                    DATE(u.created_at) as cohort_date,
                    COUNT(DISTINCT u.id) as cohort_size,
                    COUNT(DISTINCT CASE WHEN activity.last_activity >= DATE_ADD(u.created_at, INTERVAL 1 DAY) THEN u.id END) as day_1_retention,
                    COUNT(DISTINCT CASE WHEN activity.last_activity >= DATE_ADD(u.created_at, INTERVAL 7 DAY) THEN u.id END) as day_7_retention,
                    COUNT(DISTINCT CASE WHEN activity.last_activity >= DATE_ADD(u.created_at, INTERVAL 30 DAY) THEN u.id END) as day_30_retention
                FROM users u
                LEFT JOIN (
                    SELECT 
                        user_id,
                        MAX(GREATEST(
                            COALESCE(article_activity, '1970-01-01'),
                            COALESCE(comment_activity, '1970-01-01'),
                            COALESCE(clap_activity, '1970-01-01')
                        )) as last_activity
                    FROM (
                        SELECT 
                            u.id as user_id,
                            MAX(a.created_at) as article_activity,
                            MAX(c.created_at) as comment_activity,
                            MAX(cl.created_at) as clap_activity
                        FROM users u
                        LEFT JOIN articles a ON u.id = a.author_id
                        LEFT JOIN comments c ON u.id = c.user_id
                        LEFT JOIN claps cl ON u.id = cl.user_id
                        GROUP BY u.id
                    ) user_activities
                    GROUP BY user_id
                ) activity ON u.id = activity.user_id
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(u.created_at)
                ORDER BY cohort_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cohortDays]);
        return $stmt->fetchAll();
    }

    /**
     * Export analytics data to CSV format
     */
    public function exportAnalyticsData($type, $days = 30) {
        switch ($type) {
            case 'user_growth':
                return $this->getUserGrowthAnalytics($days);
            case 'content':
                return $this->getContentAnalytics($days);
            case 'engagement':
                return $this->getEngagementAnalytics($days);
            case 'top_articles':
                return $this->getTopArticles($days, 100);
            case 'trending_topics':
                return $this->getTrendingTopics($days, 50);
            case 'retention':
                return $this->getRetentionAnalytics($days);
            default:
                throw new Exception('Invalid export type');
        }
    }

    /**
     * Get comparative analytics between two time periods
     */
    public function getComparativeAnalytics($currentDays = 30, $previousDays = 30) {
        $currentPeriodStart = date('Y-m-d', strtotime("-{$currentDays} days"));
        $previousPeriodStart = date('Y-m-d', strtotime("-" . ($currentDays + $previousDays) . " days"));
        $previousPeriodEnd = date('Y-m-d', strtotime("-{$currentDays} days"));
        
        $sql = "SELECT 
                    'users' as metric,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as current_period,
                    COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) as previous_period
                FROM users
                
                UNION ALL
                
                SELECT 
                    'articles' as metric,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as current_period,
                    COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) as previous_period
                FROM articles WHERE status = 'published'
                
                UNION ALL
                
                SELECT 
                    'comments' as metric,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as current_period,
                    COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) as previous_period
                FROM comments
                
                UNION ALL
                
                SELECT 
                    'claps' as metric,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as current_period,
                    COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) as previous_period
                FROM claps";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $currentPeriodStart, $previousPeriodStart, $previousPeriodEnd,
            $currentPeriodStart, $previousPeriodStart, $previousPeriodEnd,
            $currentPeriodStart, $previousPeriodStart, $previousPeriodEnd,
            $currentPeriodStart, $previousPeriodStart, $previousPeriodEnd
        ]);
        
        $results = $stmt->fetchAll();
        
        // Calculate percentage changes
        foreach ($results as &$result) {
            $current = (int)$result['current_period'];
            $previous = (int)$result['previous_period'];
            
            if ($previous > 0) {
                $result['percentage_change'] = round((($current - $previous) / $previous) * 100, 2);
            } else {
                $result['percentage_change'] = $current > 0 ? 100 : 0;
            }
        }
        
        return $results;
    }
}