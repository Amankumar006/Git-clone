<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DashboardController extends BaseController {
    private $articleModel;
    private $userModel;
    private $clapModel;
    private $commentModel;
    private $followModel;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->articleModel = new Article();
        $this->userModel = new User();
        $this->clapModel = new Clap();
        $this->commentModel = new Comment();
        $this->followModel = new Follow();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Get writer dashboard data with article statistics
     */
    public function writerStats() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            // Get basic article counts
            $articleCounts = $this->articleModel->getArticleCountsByStatus($user['id']);
            
            // Get total engagement metrics
            $totalViews = $this->articleModel->getTotalViewsByAuthor($user['id']);
            $totalClaps = $this->clapModel->getTotalClapsByAuthor($user['id']);
            $totalComments = $this->commentModel->getTotalCommentsByAuthor($user['id']);
            
            // Get follower count
            $followerCount = $this->followModel->getFollowerCount($user['id']);
            
            // Get recent activity (last 30 days)
            $recentActivity = $this->getRecentActivity($user['id']);
            
            // Get top performing articles
            $topArticles = $this->articleModel->getTopArticlesByAuthor($user['id'], 5);
            
            $stats = [
                'article_counts' => $articleCounts,
                'total_views' => $totalViews,
                'total_claps' => $totalClaps,
                'total_comments' => $totalComments,
                'follower_count' => $followerCount,
                'recent_activity' => $recentActivity,
                'top_articles' => $topArticles
            ];

            $this->sendResponse($stats);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch writer statistics', 500);
        }
    }

    /**
     * Get detailed analytics for writer dashboard
     */
    public function writerAnalytics() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $timeframe = $_GET['timeframe'] ?? '30'; // days
            $timeframe = (int)$timeframe;

            // Get views over time
            $viewsOverTime = $this->articleModel->getViewsOverTime($user['id'], $timeframe);
            
            // Get engagement over time
            $engagementOverTime = $this->getEngagementOverTime($user['id'], $timeframe);
            
            // Get article performance comparison
            $articlePerformance = $this->articleModel->getArticlePerformanceComparison($user['id']);
            
            // Get audience insights
            $audienceInsights = $this->getAudienceInsights($user['id']);

            $analytics = [
                'views_over_time' => $viewsOverTime,
                'engagement_over_time' => $engagementOverTime,
                'article_performance' => $articlePerformance,
                'audience_insights' => $audienceInsights,
                'timeframe_days' => $timeframe
            ];

            $this->sendResponse($analytics);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch analytics', 500);
        }
    }

    /**
     * Get advanced analytics with detailed insights
     */
    public function advancedAnalytics() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $timeframe = $_GET['timeframe'] ?? '30';
            $compareWith = $_GET['compare_with'] ?? null; // previous period comparison
            $articleIds = $_GET['article_ids'] ?? null; // specific articles to analyze

            // Get detailed performance metrics
            $performanceMetrics = $this->getDetailedPerformanceMetrics($user['id'], $timeframe, $articleIds);
            
            // Get reader demographics
            $readerDemographics = $this->getReaderDemographics($user['id'], $timeframe);
            
            // Get engagement patterns analysis
            $engagementPatterns = $this->getAdvancedEngagementPatterns($user['id'], $timeframe);
            
            // Get comparative analytics if requested
            $comparativeAnalytics = null;
            if ($compareWith) {
                $comparativeAnalytics = $this->getComparativeAnalytics($user['id'], $timeframe, $compareWith);
            }

            // Get content performance insights
            $contentInsights = $this->getContentPerformanceInsights($user['id'], $timeframe);

            $analytics = [
                'performance_metrics' => $performanceMetrics,
                'reader_demographics' => $readerDemographics,
                'engagement_patterns' => $engagementPatterns,
                'comparative_analytics' => $comparativeAnalytics,
                'content_insights' => $contentInsights,
                'timeframe_days' => (int)$timeframe,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            $this->sendResponse($analytics);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch advanced analytics', 500);
        }
    }

    /**
     * Export analytics data in various formats
     */
    public function exportAnalytics() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $format = $_GET['format'] ?? 'json'; // json, csv, xlsx
            $timeframe = $_GET['timeframe'] ?? '30';
            $dataType = $_GET['data_type'] ?? 'all'; // all, performance, demographics, engagement

            // Get analytics data based on type
            $analyticsData = $this->getExportableAnalyticsData($user['id'], $timeframe, $dataType);

            switch ($format) {
                case 'csv':
                    $this->exportAsCSV($analyticsData, $dataType);
                    break;
                case 'xlsx':
                    $this->exportAsExcel($analyticsData, $dataType);
                    break;
                default:
                    $this->exportAsJSON($analyticsData, $dataType);
                    break;
            }

        } catch (Exception $e) {
            $this->sendError('Failed to export analytics', 500);
        }
    }

    /**
     * Get user's articles with management options
     */
    public function userArticles() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $status = $_GET['status'] ?? 'all'; // all, draft, published, archived
            $sortBy = $_GET['sort_by'] ?? 'updated_at'; // updated_at, created_at, views, claps
            $sortOrder = $_GET['sort_order'] ?? 'desc';

            $articles = $this->articleModel->getUserArticlesForDashboard(
                $user['id'], 
                $status, 
                $page, 
                $limit, 
                $sortBy, 
                $sortOrder
            );

            $this->sendResponse([
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ],
                'filters' => [
                    'status' => $status,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch user articles', 500);
        }
    }

    /**
     * Bulk operations on articles
     */
    public function bulkOperations() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $data = $this->getJsonInput();
            
            if (empty($data['article_ids']) || !is_array($data['article_ids'])) {
                $this->sendError('Article IDs array is required', 400);
                return;
            }

            if (empty($data['operation'])) {
                $this->sendError('Operation is required', 400);
                return;
            }

            $articleIds = $data['article_ids'];
            $operation = $data['operation'];

            // Validate operation
            $allowedOperations = ['delete', 'publish', 'unpublish', 'archive'];
            if (!in_array($operation, $allowedOperations)) {
                $this->sendError('Invalid operation', 400);
                return;
            }

            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($articleIds as $articleId) {
                try {
                    $result = false;
                    
                    switch ($operation) {
                        case 'delete':
                            $result = $this->articleModel->deleteArticle($articleId, $user['id']);
                            break;
                        case 'publish':
                            $result = $this->articleModel->publish($articleId, $user['id']);
                            break;
                        case 'unpublish':
                            $result = $this->articleModel->unpublish($articleId, $user['id']);
                            break;
                        case 'archive':
                            $result = $this->articleModel->archive($articleId, $user['id']);
                            break;
                    }

                    if ($result) {
                        $results[$articleId] = ['success' => true];
                        $successCount++;
                    } else {
                        $results[$articleId] = ['success' => false, 'error' => 'Operation failed or permission denied'];
                        $errorCount++;
                    }

                } catch (Exception $e) {
                    $results[$articleId] = ['success' => false, 'error' => $e->getMessage()];
                    $errorCount++;
                }
            }

            $this->sendResponse([
                'results' => $results,
                'summary' => [
                    'total' => count($articleIds),
                    'success' => $successCount,
                    'errors' => $errorCount
                ]
            ], "Bulk operation completed: {$successCount} successful, {$errorCount} failed");

        } catch (Exception $e) {
            error_log("Bulk operation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError('Bulk operation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recent activity for the user
     */
    private function getRecentActivity($userId) {
        try {
            $activities = [];

            // Get recent comments on user's articles
            $recentComments = $this->commentModel->getRecentCommentsOnUserArticles($userId, 10);
            foreach ($recentComments as $comment) {
                $activities[] = [
                    'type' => 'comment',
                    'data' => $comment,
                    'timestamp' => $comment['created_at']
                ];
            }

            // Get recent claps on user's articles
            $recentClaps = $this->clapModel->getRecentClapsOnUserArticles($userId, 10);
            foreach ($recentClaps as $clap) {
                $activities[] = [
                    'type' => 'clap',
                    'data' => $clap,
                    'timestamp' => $clap['created_at']
                ];
            }

            // Get recent followers
            $recentFollowers = $this->followModel->getRecentFollowers($userId, 5);
            foreach ($recentFollowers as $follower) {
                $activities[] = [
                    'type' => 'follow',
                    'data' => $follower,
                    'timestamp' => $follower['created_at']
                ];
            }

            // Sort by timestamp (most recent first)
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Return top 20 activities
            return array_slice($activities, 0, 20);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement over time data
     */
    private function getEngagementOverTime($userId, $days) {
        try {
            $engagement = [];

            // Get claps over time
            $clapsOverTime = $this->clapModel->getClapsOverTime($userId, $days);
            
            // Get comments over time
            $commentsOverTime = $this->commentModel->getCommentsOverTime($userId, $days);
            
            // Get follows over time
            $followsOverTime = $this->followModel->getFollowsOverTime($userId, $days);

            // Combine data by date
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $dates[] = $date;
                
                $engagement[] = [
                    'date' => $date,
                    'claps' => $clapsOverTime[$date] ?? 0,
                    'comments' => $commentsOverTime[$date] ?? 0,
                    'follows' => $followsOverTime[$date] ?? 0
                ];
            }

            return $engagement;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get audience insights
     */
    private function getAudienceInsights($userId) {
        try {
            // Get top readers (users who engage most with this author's content)
            $topReaders = $this->getTopReaders($userId);
            
            // Get engagement patterns (which days/times get most engagement)
            $engagementPatterns = $this->getEngagementPatterns($userId);
            
            // Get content performance by tags
            $tagPerformance = $this->getTagPerformance($userId);

            return [
                'top_readers' => $topReaders,
                'engagement_patterns' => $engagementPatterns,
                'tag_performance' => $tagPerformance
            ];

        } catch (Exception $e) {
            return [
                'top_readers' => [],
                'engagement_patterns' => [],
                'tag_performance' => []
            ];
        }
    }

    /**
     * Get top readers for this author
     */
    private function getTopReaders($userId) {
        try {
            return $this->userModel->getTopReadersByAuthor($userId, 10);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement patterns (day of week, hour of day)
     */
    private function getEngagementPatterns($userId) {
        try {
            $patterns = [
                'by_day_of_week' => $this->getEngagementByDayOfWeek($userId),
                'by_hour_of_day' => $this->getEngagementByHourOfDay($userId)
            ];

            return $patterns;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement by day of week
     */
    private function getEngagementByDayOfWeek($userId) {
        try {
            return $this->clapModel->getEngagementByDayOfWeek($userId);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement by hour of day
     */
    private function getEngagementByHourOfDay($userId) {
        try {
            return $this->clapModel->getEngagementByHourOfDay($userId);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get tag performance for this author
     */
    private function getTagPerformance($userId) {
        try {
            return $this->articleModel->getTagPerformanceByAuthor($userId);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get reader dashboard statistics
     */
    public function readerStats() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            // Get reading statistics
            $totalArticlesRead = $this->getArticlesReadCount($user['id']);
            $totalReadingTime = $this->getTotalReadingTime($user['id']);
            $articlesBookmarked = $this->getBookmarkedArticlesCount($user['id']);
            $authorsFollowed = $this->followModel->getFollowingCount($user['id']);
            $readingStreak = $this->getReadingStreak($user['id']);
            $favoriteTopics = $this->getFavoriteTopics($user['id']);

            $stats = [
                'total_articles_read' => $totalArticlesRead,
                'total_reading_time' => $totalReadingTime,
                'articles_bookmarked' => $articlesBookmarked,
                'authors_followed' => $authorsFollowed,
                'reading_streak' => $readingStreak,
                'favorite_topics' => $favoriteTopics
            ];

            $this->sendResponse($stats);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch reader statistics', 500);
        }
    }

    /**
     * Get user's bookmarked articles
     */
    public function getBookmarks() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);

            // Get bookmarked articles with full details
            $bookmarks = $this->getBookmarkedArticlesWithDetails($user['id'], $page, $limit);

            $this->sendResponse([
                'bookmarks' => $bookmarks,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch bookmarks', 500);
        }
    }

    /**
     * Get following feed articles
     */
    public function getFollowingFeed() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);

            // Get articles from followed authors
            $articles = $this->followModel->getFollowingFeed($user['id'], $limit, ($page - 1) * $limit);

            // Add preview text for each article
            foreach ($articles as &$article) {
                $article['content'] = json_decode($article['content'], true);
                $article['preview'] = $this->generateArticlePreview($article['content']);
                $article['tags'] = $this->getArticleTags($article['id']);
            }

            $this->sendResponse([
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch following feed', 500);
        }
    }

    /**
     * Get reading history
     */
    public function getReadingHistory() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);

            // Get reading history
            $history = $this->getUserReadingHistory($user['id'], $page, $limit);

            $this->sendResponse([
                'history' => $history,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch reading history', 500);
        }
    }

    /**
     * Get count of articles read by user
     */
    private function getArticlesReadCount($userId) {
        try {
            $sql = "SELECT COUNT(DISTINCT article_id) as count 
                    FROM article_reads 
                    WHERE user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get total reading time for user
     */
    private function getTotalReadingTime($userId) {
        try {
            $sql = "SELECT SUM(time_spent) as total_time 
                    FROM article_reads 
                    WHERE user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)($result['total_time'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of bookmarked articles
     */
    private function getBookmarkedArticlesCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM bookmarks b
                    JOIN articles a ON b.article_id = a.id
                    WHERE b.user_id = ? AND a.status = 'published'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get reading streak (consecutive days with reading activity)
     */
    private function getReadingStreak($userId) {
        try {
            $sql = "SELECT DATE(read_at) as read_date
                    FROM article_reads 
                    WHERE user_id = ?
                    GROUP BY DATE(read_at)
                    ORDER BY read_date DESC
                    LIMIT 30";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $readDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($readDates)) {
                return 0;
            }

            $streak = 0;
            $currentDate = new DateTime();
            
            foreach ($readDates as $readDate) {
                $readDateTime = new DateTime($readDate);
                $daysDiff = $currentDate->diff($readDateTime)->days;
                
                if ($daysDiff === $streak) {
                    $streak++;
                    $currentDate->sub(new DateInterval('P1D'));
                } else {
                    break;
                }
            }
            
            return $streak;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get user's favorite topics based on reading history
     */
    private function getFavoriteTopics($userId) {
        try {
            $sql = "SELECT t.name, COUNT(*) as read_count
                    FROM article_reads ar
                    JOIN articles a ON ar.article_id = a.id
                    JOIN article_tags at ON a.id = at.article_id
                    JOIN tags t ON at.tag_id = t.id
                    WHERE ar.user_id = ?
                    GROUP BY t.id, t.name
                    ORDER BY read_count DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll();
            
            return array_column($results, 'name');
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get bookmarked articles with full details
     */
    private function getBookmarkedArticlesWithDetails($userId, $page, $limit) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT a.*, u.username as author_name, u.profile_image_url as author_avatar,
                           b.created_at as bookmarked_at,
                           GROUP_CONCAT(t.name) as tags
                    FROM bookmarks b
                    JOIN articles a ON b.article_id = a.id
                    JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    WHERE b.user_id = ? AND a.status = 'published'
                    GROUP BY a.id, b.created_at
                    ORDER BY b.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            $bookmarks = $stmt->fetchAll();
            
            // Process bookmarks
            foreach ($bookmarks as &$bookmark) {
                $bookmark['tags'] = $bookmark['tags'] ? explode(',', $bookmark['tags']) : [];
            }
            
            return $bookmarks;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get user's reading history
     */
    private function getUserReadingHistory($userId, $page, $limit) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT a.id, a.title, u.username as author_name,
                           ar.read_at, ar.time_spent,
                           CASE 
                               WHEN ar.scroll_depth >= 90 THEN 100
                               WHEN ar.scroll_depth >= 75 THEN 90
                               WHEN ar.scroll_depth >= 50 THEN 75
                               WHEN ar.scroll_depth >= 25 THEN 50
                               ELSE 25
                           END as completion_percentage
                    FROM article_reads ar
                    JOIN articles a ON ar.article_id = a.id
                    JOIN users u ON a.author_id = u.id
                    WHERE ar.user_id = ? AND a.status = 'published'
                    ORDER BY ar.read_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Generate article preview text
     */
    private function generateArticlePreview($content, $maxLength = 200) {
        if (is_array($content)) {
            // Extract text from rich content
            $text = $this->extractTextFromContent($content);
        } else {
            $text = strip_tags($content);
        }
        
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Extract plain text from rich content
     */
    private function extractTextFromContent($content) {
        $text = '';
        
        if (isset($content['blocks']) && is_array($content['blocks'])) {
            foreach ($content['blocks'] as $block) {
                if (isset($block['text'])) {
                    $text .= $block['text'] . ' ';
                }
            }
        } elseif (is_string($content)) {
            $text = strip_tags($content);
        }
        
        return trim($text);
    }

    /**
     * Get article tags
     */
    private function getArticleTags($articleId) {
        try {
            $sql = "SELECT t.name
                    FROM article_tags at
                    JOIN tags t ON at.tag_id = t.id
                    WHERE at.article_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            
            return array_column($stmt->fetchAll(), 'name');
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get detailed performance metrics
     */
    private function getDetailedPerformanceMetrics($userId, $timeframe, $articleIds = null) {
        try {
            $whereClause = "WHERE a.author_id = ? AND a.status = 'published'";
            $params = [$userId];

            if ($articleIds) {
                $articleIdsArray = explode(',', $articleIds);
                $placeholders = str_repeat('?,', count($articleIdsArray) - 1) . '?';
                $whereClause .= " AND a.id IN ($placeholders)";
                $params = array_merge($params, $articleIdsArray);
            }

            $sql = "SELECT 
                        a.id,
                        a.title,
                        a.published_at,
                        a.view_count,
                        a.clap_count,
                        a.comment_count,
                        a.reading_time,
                        COUNT(DISTINCT av.id) as unique_views,
                        COUNT(DISTINCT ar.id) as completed_reads,
                        AVG(ar.time_spent) as avg_time_spent,
                        AVG(ar.scroll_depth) as avg_scroll_depth,
                        COUNT(DISTINCT b.user_id) as bookmark_count,
                        (a.view_count + a.clap_count * 2 + a.comment_count * 3 + COUNT(DISTINCT ar.id) * 5) as engagement_score,
                        (COUNT(DISTINCT ar.id) / NULLIF(a.view_count, 0)) * 100 as read_completion_rate
                    FROM articles a
                    LEFT JOIN article_views av ON a.id = av.article_id 
                        AND av.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    LEFT JOIN article_reads ar ON a.id = ar.article_id 
                        AND ar.read_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    LEFT JOIN bookmarks b ON a.id = b.article_id
                    $whereClause
                    GROUP BY a.id
                    ORDER BY engagement_score DESC";

            $params = array_merge([$timeframe, $timeframe], $params);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get reader demographics
     */
    private function getReaderDemographics($userId, $timeframe) {
        try {
            // Get geographic distribution (based on IP analysis - simplified)
            $geographicData = $this->getGeographicDistribution($userId, $timeframe);
            
            // Get reading behavior patterns
            $readingBehavior = $this->getReadingBehaviorPatterns($userId, $timeframe);
            
            // Get device and platform analytics
            $deviceAnalytics = $this->getDeviceAnalytics($userId, $timeframe);
            
            // Get reader retention metrics
            $retentionMetrics = $this->getReaderRetentionMetrics($userId, $timeframe);

            return [
                'geographic_distribution' => $geographicData,
                'reading_behavior' => $readingBehavior,
                'device_analytics' => $deviceAnalytics,
                'retention_metrics' => $retentionMetrics
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get advanced engagement patterns
     */
    private function getAdvancedEngagementPatterns($userId, $timeframe) {
        try {
            // Get engagement velocity (how quickly engagement happens after publishing)
            $engagementVelocity = $this->getEngagementVelocity($userId, $timeframe);
            
            // Get content lifecycle analysis
            $contentLifecycle = $this->getContentLifecycleAnalysis($userId, $timeframe);
            
            // Get viral coefficient analysis
            $viralAnalysis = $this->getViralCoefficientAnalysis($userId, $timeframe);
            
            // Get engagement quality metrics
            $engagementQuality = $this->getEngagementQualityMetrics($userId, $timeframe);

            return [
                'engagement_velocity' => $engagementVelocity,
                'content_lifecycle' => $contentLifecycle,
                'viral_analysis' => $viralAnalysis,
                'engagement_quality' => $engagementQuality
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get comparative analytics between time periods
     */
    private function getComparativeAnalytics($userId, $currentPeriod, $comparisonType) {
        try {
            $currentData = $this->getPeriodAnalytics($userId, $currentPeriod);
            
            $previousPeriod = $currentPeriod;
            if ($comparisonType === 'previous_period') {
                $previousData = $this->getPreviousPeriodAnalytics($userId, $currentPeriod);
            } elseif ($comparisonType === 'same_period_last_year') {
                $previousData = $this->getSamePeriodLastYearAnalytics($userId, $currentPeriod);
            } else {
                $previousData = $this->getPreviousPeriodAnalytics($userId, $currentPeriod);
            }

            // Calculate percentage changes
            $comparison = [
                'current_period' => $currentData,
                'previous_period' => $previousData,
                'changes' => $this->calculatePercentageChanges($currentData, $previousData)
            ];

            return $comparison;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get content performance insights
     */
    private function getContentPerformanceInsights($userId, $timeframe) {
        try {
            // Get optimal publishing times
            $optimalTimes = $this->getOptimalPublishingTimes($userId);
            
            // Get content length analysis
            $contentLengthAnalysis = $this->getContentLengthAnalysis($userId, $timeframe);
            
            // Get tag performance correlation
            $tagPerformance = $this->getTagPerformanceCorrelation($userId, $timeframe);
            
            // Get content freshness impact
            $freshnessImpact = $this->getContentFreshnessImpact($userId, $timeframe);

            return [
                'optimal_publishing_times' => $optimalTimes,
                'content_length_analysis' => $contentLengthAnalysis,
                'tag_performance_correlation' => $tagPerformance,
                'content_freshness_impact' => $freshnessImpact
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get geographic distribution of readers
     */
    private function getGeographicDistribution($userId, $timeframe) {
        try {
            $sql = "SELECT 
                        SUBSTRING_INDEX(av.ip_address, '.', 2) as ip_prefix,
                        COUNT(*) as view_count,
                        COUNT(DISTINCT av.user_id) as unique_readers
                    FROM article_views av
                    JOIN articles a ON av.article_id = a.id
                    WHERE a.author_id = ? 
                        AND av.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND av.ip_address IS NOT NULL
                    GROUP BY ip_prefix
                    ORDER BY view_count DESC
                    LIMIT 20";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $timeframe]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get reading behavior patterns
     */
    private function getReadingBehaviorPatterns($userId, $timeframe) {
        try {
            $sql = "SELECT 
                        AVG(ar.time_spent) as avg_reading_time,
                        AVG(ar.scroll_depth) as avg_scroll_depth,
                        COUNT(CASE WHEN ar.scroll_depth >= 90 THEN 1 END) as full_reads,
                        COUNT(CASE WHEN ar.scroll_depth BETWEEN 50 AND 89 THEN 1 END) as partial_reads,
                        COUNT(CASE WHEN ar.scroll_depth < 50 THEN 1 END) as quick_scans,
                        COUNT(*) as total_reads
                    FROM article_reads ar
                    JOIN articles a ON ar.article_id = a.id
                    WHERE a.author_id = ? 
                        AND ar.read_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $timeframe]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get device analytics
     */
    private function getDeviceAnalytics($userId, $timeframe) {
        try {
            $sql = "SELECT 
                        CASE 
                            WHEN av.user_agent LIKE '%Mobile%' THEN 'Mobile'
                            WHEN av.user_agent LIKE '%Tablet%' THEN 'Tablet'
                            ELSE 'Desktop'
                        END as device_type,
                        COUNT(*) as view_count,
                        COUNT(DISTINCT av.user_id) as unique_users
                    FROM article_views av
                    JOIN articles a ON av.article_id = a.id
                    WHERE a.author_id = ? 
                        AND av.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND av.user_agent IS NOT NULL
                    GROUP BY device_type
                    ORDER BY view_count DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $timeframe]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get reader retention metrics
     */
    private function getReaderRetentionMetrics($userId, $timeframe) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT av1.user_id) as total_readers,
                        COUNT(DISTINCT av2.user_id) as returning_readers,
                        (COUNT(DISTINCT av2.user_id) / COUNT(DISTINCT av1.user_id)) * 100 as retention_rate
                    FROM article_views av1
                    JOIN articles a1 ON av1.article_id = a1.id
                    LEFT JOIN article_views av2 ON av1.user_id = av2.user_id 
                        AND av2.viewed_at > av1.viewed_at
                        AND av2.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    JOIN articles a2 ON av2.article_id = a2.id AND a2.author_id = ?
                    WHERE a1.author_id = ? 
                        AND av1.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND av1.user_id IS NOT NULL";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$timeframe, $userId, $userId, $timeframe]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement velocity analysis
     */
    private function getEngagementVelocity($userId, $timeframe) {
        try {
            $sql = "SELECT 
                        a.id,
                        a.title,
                        a.published_at,
                        COUNT(CASE WHEN c.created_at <= DATE_ADD(a.published_at, INTERVAL 1 HOUR) THEN 1 END) as claps_1h,
                        COUNT(CASE WHEN c.created_at <= DATE_ADD(a.published_at, INTERVAL 24 HOUR) THEN 1 END) as claps_24h,
                        COUNT(CASE WHEN c.created_at <= DATE_ADD(a.published_at, INTERVAL 7 DAY) THEN 1 END) as claps_7d
                    FROM articles a
                    LEFT JOIN claps c ON a.id = c.article_id
                    WHERE a.author_id = ? 
                        AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        AND a.status = 'published'
                    GROUP BY a.id
                    ORDER BY a.published_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $timeframe]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get exportable analytics data
     */
    private function getExportableAnalyticsData($userId, $timeframe, $dataType) {
        try {
            $data = [];

            if ($dataType === 'all' || $dataType === 'performance') {
                $data['performance'] = $this->getDetailedPerformanceMetrics($userId, $timeframe);
            }

            if ($dataType === 'all' || $dataType === 'demographics') {
                $data['demographics'] = $this->getReaderDemographics($userId, $timeframe);
            }

            if ($dataType === 'all' || $dataType === 'engagement') {
                $data['engagement'] = $this->getAdvancedEngagementPatterns($userId, $timeframe);
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Export analytics as JSON
     */
    private function exportAsJSON($data, $dataType) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="analytics_' . $dataType . '_' . date('Y-m-d') . '.json"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export analytics as CSV
     */
    private function exportAsCSV($data, $dataType) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="analytics_' . $dataType . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (isset($data['performance']) && is_array($data['performance'])) {
            fputcsv($output, ['Article Performance Data']);
            fputcsv($output, ['ID', 'Title', 'Views', 'Claps', 'Comments', 'Engagement Score', 'Read Completion Rate']);
            
            foreach ($data['performance'] as $article) {
                fputcsv($output, [
                    $article['id'],
                    $article['title'],
                    $article['view_count'],
                    $article['clap_count'],
                    $article['comment_count'],
                    $article['engagement_score'],
                    $article['read_completion_rate'] ?? 0
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export analytics as Excel (simplified - would need PHPSpreadsheet for full implementation)
     */
    private function exportAsExcel($data, $dataType) {
        // For now, export as CSV with Excel-compatible format
        $this->exportAsCSV($data, $dataType);
    }

    // Placeholder methods for additional analytics features
    private function getContentLifecycleAnalysis($userId, $timeframe) { return []; }
    private function getViralCoefficientAnalysis($userId, $timeframe) { return []; }
    private function getEngagementQualityMetrics($userId, $timeframe) { return []; }
    private function getPeriodAnalytics($userId, $period) { return []; }
    private function getPreviousPeriodAnalytics($userId, $period) { return []; }
    private function getSamePeriodLastYearAnalytics($userId, $period) { return []; }
    private function calculatePercentageChanges($current, $previous) { return []; }
    private function getOptimalPublishingTimes($userId) { return []; }
    private function getContentLengthAnalysis($userId, $timeframe) { return []; }
    private function getTagPerformanceCorrelation($userId, $timeframe) { return []; }
    private function getContentFreshnessImpact($userId, $timeframe) { return []; }
}