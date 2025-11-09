<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Search.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class SearchController extends BaseController {
    private $searchModel;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->searchModel = new Search();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Perform comprehensive search
     * GET /api/search
     */
    public function search() {
        try {
            $query = $_GET['q'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            
            // Parse filters
            $filters = [];
            if (!empty($_GET['type'])) {
                $filters['type'] = $_GET['type'];
            }
            if (!empty($_GET['author'])) {
                $filters['author'] = $_GET['author'];
            }
            if (!empty($_GET['author_id'])) {
                $filters['author_id'] = (int)$_GET['author_id'];
            }
            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Get current user ID for logging
            $userId = null;
            $authResult = $this->authMiddleware->authenticate(false); // Optional auth
            if ($authResult['success']) {
                $userId = $authResult['user']['id'];
            }

            // Perform search
            $results = $this->searchModel->search($query, $filters, $page, $limit);

            // Log search query
            $totalResults = $results['total_count'];
            $this->searchModel->logSearch($query, $userId, $totalResults);

            $this->sendResponse([
                'success' => true,
                'data' => $results,
                'query' => $query,
                'filters' => $filters
            ]);

        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            $this->sendError('Search failed', 500);
        }
    }

    /**
     * Search articles only
     * GET /api/search/articles
     */
    public function searchArticles() {
        try {
            $query = $_GET['q'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            
            // Parse filters
            $filters = [];
            if (!empty($_GET['author'])) {
                $filters['author'] = $_GET['author'];
            }
            if (!empty($_GET['author_id'])) {
                $filters['author_id'] = (int)$_GET['author_id'];
            }
            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Sort options
            $sortBy = $_GET['sort'] ?? 'relevance'; // relevance, date, popularity
            $sortOrder = $_GET['order'] ?? 'desc';

            $results = $this->searchModel->searchArticles($query, $filters, $page, $limit);

            // Apply additional sorting if needed
            if ($sortBy === 'date') {
                usort($results['data'], function($a, $b) use ($sortOrder) {
                    $comparison = strtotime($b['published_at']) - strtotime($a['published_at']);
                    return $sortOrder === 'asc' ? -$comparison : $comparison;
                });
            } elseif ($sortBy === 'popularity') {
                usort($results['data'], function($a, $b) use ($sortOrder) {
                    $scoreA = ($a['view_count'] ?? 0) + ($a['clap_count'] ?? 0) * 2;
                    $scoreB = ($b['view_count'] ?? 0) + ($b['clap_count'] ?? 0) * 2;
                    $comparison = $scoreB - $scoreA;
                    return $sortOrder === 'asc' ? -$comparison : $comparison;
                });
            }

            $this->sendResponse([
                'success' => true,
                'data' => $results['data'],
                'total' => $results['total'],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_pages' => ceil($results['total'] / $limit)
                ],
                'query' => $query,
                'filters' => $filters,
                'sort' => ['by' => $sortBy, 'order' => $sortOrder]
            ]);

        } catch (Exception $e) {
            error_log("Article search error: " . $e->getMessage());
            $this->sendError('Article search failed', 500);
        }
    }

    /**
     * Get search suggestions for autocomplete
     * GET /api/search/suggestions
     */
    public function getSuggestions() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));

            if (strlen($query) < 2) {
                $this->sendResponse([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }

            $suggestions = $this->searchModel->getSuggestions($query, $limit);

            $this->sendResponse([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (Exception $e) {
            error_log("Search suggestions error: " . $e->getMessage());
            $this->sendError('Failed to get suggestions', 500);
        }
    }

    /**
     * Get popular searches
     * GET /api/search/popular
     */
    public function getPopularSearches() {
        try {
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));

            $popularSearches = $this->searchModel->getPopularSearches($limit);

            $this->sendResponse([
                'success' => true,
                'data' => $popularSearches
            ]);

        } catch (Exception $e) {
            error_log("Popular searches error: " . $e->getMessage());
            $this->sendError('Failed to get popular searches', 500);
        }
    }

    /**
     * Get search analytics (admin only)
     * GET /api/search/analytics
     */
    public function getSearchAnalytics() {
        try {
            // Check authentication
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            // For now, allow any authenticated user. In production, add admin check
            $days = min(365, max(1, (int)($_GET['days'] ?? 30)));

            $analytics = $this->searchModel->getSearchAnalytics($days);

            $this->sendResponse([
                'success' => true,
                'data' => $analytics,
                'period_days' => $days
            ]);

        } catch (Exception $e) {
            error_log("Search analytics error: " . $e->getMessage());
            $this->sendError('Failed to get search analytics', 500);
        }
    }

    /**
     * Advanced search with multiple filters
     * POST /api/search/advanced
     */
    public function advancedSearch() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $query = $input['query'] ?? '';
            $page = max(1, (int)($input['page'] ?? 1));
            $limit = min(50, max(1, (int)($input['limit'] ?? 10)));
            
            // Advanced filters
            $filters = [];
            if (!empty($input['filters'])) {
                $allowedFilters = ['author', 'author_id', 'tag', 'date_from', 'date_to', 'type'];
                foreach ($allowedFilters as $filter) {
                    if (isset($input['filters'][$filter])) {
                        $filters[$filter] = $input['filters'][$filter];
                    }
                }
            }

            // Multiple tags support
            if (!empty($input['tags']) && is_array($input['tags'])) {
                $filters['tags'] = $input['tags'];
            }

            // Reading time filter
            if (!empty($input['reading_time'])) {
                $filters['reading_time_min'] = $input['reading_time']['min'] ?? null;
                $filters['reading_time_max'] = $input['reading_time']['max'] ?? null;
            }

            // Engagement filters
            if (!empty($input['engagement'])) {
                $filters['min_claps'] = $input['engagement']['min_claps'] ?? null;
                $filters['min_views'] = $input['engagement']['min_views'] ?? null;
            }

            $results = $this->searchModel->search($query, $filters, $page, $limit);

            $this->sendResponse([
                'success' => true,
                'data' => $results,
                'query' => $query,
                'filters' => $filters
            ]);

        } catch (Exception $e) {
            error_log("Advanced search error: " . $e->getMessage());
            $this->sendError('Advanced search failed', 500);
        }
    }

    /**
     * Save search query for user
     * POST /api/search/save
     */
    public function saveSearch() {
        try {
            // Check authentication
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $authResult['user']['id'];
            
            $query = $input['query'] ?? '';
            $filters = $input['filters'] ?? [];
            $name = $input['name'] ?? $query;

            if (empty($query)) {
                $this->sendError('Search query is required', 400);
                return;
            }

            // Save to saved_searches table (would need to create this table)
            $sql = "INSERT INTO saved_searches (user_id, name, query, filters, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    query = VALUES(query), 
                    filters = VALUES(filters), 
                    updated_at = NOW()";

            $stmt = $this->searchModel->db->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $name,
                $query,
                json_encode($filters)
            ]);

            if ($result) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Search saved successfully'
                ]);
            } else {
                $this->sendError('Failed to save search', 500);
            }

        } catch (Exception $e) {
            error_log("Save search error: " . $e->getMessage());
            $this->sendError('Failed to save search', 500);
        }
    }

    /**
     * Get user's saved searches
     * GET /api/search/saved
     */
    public function getSavedSearches() {
        try {
            // Check authentication
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];

            $sql = "SELECT * FROM saved_searches 
                    WHERE user_id = ? 
                    ORDER BY updated_at DESC, created_at DESC";

            $stmt = $this->searchModel->db->prepare($sql);
            $stmt->execute([$userId]);
            $savedSearches = $stmt->fetchAll();

            // Parse filters JSON
            foreach ($savedSearches as &$search) {
                $search['filters'] = json_decode($search['filters'], true) ?? [];
            }

            $this->sendResponse([
                'success' => true,
                'data' => $savedSearches
            ]);

        } catch (Exception $e) {
            error_log("Get saved searches error: " . $e->getMessage());
            $this->sendError('Failed to get saved searches', 500);
        }
    }
}