<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Feed.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class FeedController extends BaseController {
    private $feedModel;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->feedModel = new Feed();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Get homepage feed
     * GET /api/feed
     */
    public function getHomepageFeed() {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));

            // Check if user is authenticated
            $authResult = $this->authMiddleware->authenticate(false); // Optional auth
            
            if ($authResult['success']) {
                // Personalized feed for authenticated users
                $userId = $authResult['user']['id'];
                $feed = $this->feedModel->getPersonalizedFeed($userId, $page, $limit);
            } else {
                // Public feed for non-authenticated users
                $feed = $this->feedModel->getPublicFeed($page, $limit);
            }

            $this->sendResponse([
                'success' => true,
                'data' => $feed['data'],
                'pagination' => $feed['pagination'],
                'feed_type' => $authResult['success'] ? 'personalized' : 'public'
            ]);

        } catch (Exception $e) {
            error_log("Homepage feed error: " . $e->getMessage());
            $this->sendError('Failed to load homepage feed', 500);
        }
    }

    /**
     * Get trending articles
     * GET /api/feed/trending
     */
    public function getTrending() {
        try {
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            $timeframe = $_GET['timeframe'] ?? '7 days';

            $articles = $this->feedModel->getTrendingArticles($limit, $timeframe);

            $this->sendResponse([
                'success' => true,
                'data' => $articles,
                'timeframe' => $timeframe
            ]);

        } catch (Exception $e) {
            error_log("Trending articles error: " . $e->getMessage());
            $this->sendError('Failed to load trending articles', 500);
        }
    }

    /**
     * Get popular articles
     * GET /api/feed/popular
     */
    public function getPopular() {
        try {
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            $timeframe = $_GET['timeframe'] ?? '30 days';

            $articles = $this->feedModel->getPopularArticles($limit, $timeframe);

            $this->sendResponse([
                'success' => true,
                'data' => $articles,
                'timeframe' => $timeframe
            ]);

        } catch (Exception $e) {
            error_log("Popular articles error: " . $e->getMessage());
            $this->sendError('Failed to load popular articles', 500);
        }
    }

    /**
     * Get latest articles
     * GET /api/feed/latest
     */
    public function getLatest() {
        try {
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            
            // Check if user is authenticated to exclude their own articles
            $excludeUserId = null;
            $authResult = $this->authMiddleware->authenticate(false);
            if ($authResult['success']) {
                $excludeUserId = $authResult['user']['id'];
            }

            $articles = $this->feedModel->getLatestArticles($limit, $excludeUserId);

            $this->sendResponse([
                'success' => true,
                'data' => $articles
            ]);

        } catch (Exception $e) {
            error_log("Latest articles error: " . $e->getMessage());
            $this->sendError('Failed to load latest articles', 500);
        }
    }

    /**
     * Get personalized recommendations
     * GET /api/feed/recommendations
     */
    public function getRecommendations() {
        try {
            // Check authentication
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));

            $recommendations = $this->feedModel->getRecommendedArticles($userId, $limit);

            $this->sendResponse([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (Exception $e) {
            error_log("Recommendations error: " . $e->getMessage());
            $this->sendError('Failed to load recommendations', 500);
        }
    }

    /**
     * Get following feed (articles from followed authors)
     * GET /api/feed/following
     */
    public function getFollowingFeed() {
        try {
            // Check authentication
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));

            // Get articles from followed authors
            $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                           GROUP_CONCAT(DISTINCT t.name) as tags
                    FROM articles a
                    INNER JOIN follows f ON a.author_id = f.following_id
                    LEFT JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    WHERE f.follower_id = ? 
                    AND a.status = 'published'
                    GROUP BY a.id
                    ORDER BY a.published_at DESC
                    LIMIT ? OFFSET ?";

            $offset = ($page - 1) * $limit;
            $stmt = $this->feedModel->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            $articles = $stmt->fetchAll();

            // Process articles
            foreach ($articles as &$article) {
                $article['content'] = json_decode($article['content'], true);
                $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
                $article['feed_type'] = 'following';
            }

            $this->sendResponse([
                'success' => true,
                'data' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'has_more' => count($articles) === $limit
                ]
            ]);

        } catch (Exception $e) {
            error_log("Following feed error: " . $e->getMessage());
            $this->sendError('Failed to load following feed', 500);
        }
    }

    /**
     * Get filtered feed with specific criteria
     * GET /api/feed/filtered
     */
    public function getFilteredFeed() {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));

            // Parse filters
            $filters = [];
            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }
            if (!empty($_GET['author_id'])) {
                $filters['author_id'] = (int)$_GET['author_id'];
            }
            if (!empty($_GET['timeframe'])) {
                $filters['timeframe'] = $_GET['timeframe'];
            }
            if (!empty($_GET['sort'])) {
                $filters['sort'] = $_GET['sort'];
            }
            if (!empty($_GET['min_reading_time'])) {
                $filters['min_reading_time'] = (int)$_GET['min_reading_time'];
            }
            if (!empty($_GET['max_reading_time'])) {
                $filters['max_reading_time'] = (int)$_GET['max_reading_time'];
            }

            $feed = $this->feedModel->getFilteredFeed($filters, $page, $limit);

            $this->sendResponse([
                'success' => true,
                'data' => $feed['data'],
                'pagination' => $feed['pagination'],
                'filters' => $filters
            ]);

        } catch (Exception $e) {
            error_log("Filtered feed error: " . $e->getMessage());
            $this->sendError('Failed to load filtered feed', 500);
        }
    }

    /**
     * Get feed statistics for analytics
     * GET /api/feed/stats
     */
    public function getFeedStats() {
        try {
            // Check authentication (optional)
            $authResult = $this->authMiddleware->authenticate(false);
            $userId = $authResult['success'] ? $authResult['user']['id'] : null;

            $stats = [];

            // Total published articles
            $sql = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
            $stmt = $this->feedModel->db->prepare($sql);
            $stmt->execute();
            $stats['total_articles'] = (int)$stmt->fetchColumn();

            // Articles published today
            $sql = "SELECT COUNT(*) as today FROM articles 
                    WHERE status = 'published' 
                    AND DATE(published_at) = CURDATE()";
            $stmt = $this->feedModel->db->prepare($sql);
            $stmt->execute();
            $stats['articles_today'] = (int)$stmt->fetchColumn();

            // Articles published this week
            $sql = "SELECT COUNT(*) as week FROM articles 
                    WHERE status = 'published' 
                    AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->feedModel->db->prepare($sql);
            $stmt->execute();
            $stats['articles_this_week'] = (int)$stmt->fetchColumn();

            // Top tags this week
            $sql = "SELECT t.name, COUNT(*) as usage_count
                    FROM tags t
                    INNER JOIN article_tags at ON t.id = at.tag_id
                    INNER JOIN articles a ON at.article_id = a.id
                    WHERE a.status = 'published'
                    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY t.id
                    ORDER BY usage_count DESC
                    LIMIT 10";
            $stmt = $this->feedModel->db->prepare($sql);
            $stmt->execute();
            $stats['trending_tags'] = $stmt->fetchAll();

            // User-specific stats if authenticated
            if ($userId) {
                // Articles from followed authors
                $sql = "SELECT COUNT(*) as following_articles
                        FROM articles a
                        INNER JOIN follows f ON a.author_id = f.following_id
                        WHERE f.follower_id = ? 
                        AND a.status = 'published'
                        AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $stmt = $this->feedModel->db->prepare($sql);
                $stmt->execute([$userId]);
                $stats['following_articles_this_week'] = (int)$stmt->fetchColumn();

                // User's reading activity
                $sql = "SELECT COUNT(DISTINCT article_id) as read_articles
                        FROM claps 
                        WHERE user_id = ?
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $stmt = $this->feedModel->db->prepare($sql);
                $stmt->execute([$userId]);
                $stats['articles_engaged_this_week'] = (int)$stmt->fetchColumn();
            }

            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            error_log("Feed stats error: " . $e->getMessage());
            $this->sendError('Failed to load feed statistics', 500);
        }
    }
}