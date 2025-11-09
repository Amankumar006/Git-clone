<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../models/ContentFilter.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ArticleController extends BaseController {
    private $articleModel;
    private $tagModel;
    private $contentFilter;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->articleModel = new Article();
        $this->tagModel = new Tag();
        $this->contentFilter = new ContentFilter();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    /**
     * Get token from Authorization header
     */
    private function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    /**
     * Authenticate user with development mode support
     */
    private function authenticateUser() {
        $isDevelopment = !defined('APP_ENV') || APP_ENV !== 'production';
        
        if ($isDevelopment) {
            // Development: check for token but don't require it
            $token = $this->getTokenFromHeader();
            if ($token) {
                try {
                    return $this->authMiddleware->authenticate();
                } catch (Exception $e) {
                    // Token exists but is invalid, use fake user for development
                    error_log('Development mode: Invalid token, using fake user');
                    return ['id' => 2, 'username' => 'dev_user'];
                }
            } else {
                // No token provided, use fake user for development
                error_log('Development mode: No token provided, using fake user');
                return ['id' => 2, 'username' => 'dev_user'];
            }
        } else {
            // Production: require authentication
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return null;
            }
            return $user;
        }
    }

    /**
     * Get all published articles with pagination
     */
    public function index() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $filters = [];

            // Apply filters
            if (!empty($_GET['author_id'])) {
                $filters['author_id'] = $_GET['author_id'];
            }

            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }

            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }

            $articles = $this->articleModel->getArticles($filters, $page, $limit);

            $this->sendResponse([
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch articles', 500);
        }
    }

    /**
     * Get single article by ID or slug
     */
    public function show() {
        try {
            $identifier = $_GET['id'] ?? $_GET['slug'] ?? null;
            
            if (!$identifier) {
                $this->sendError('Article ID or slug is required', 400);
                return;
            }

            // Try to find by ID first, then by slug
            if (is_numeric($identifier)) {
                $article = $this->articleModel->findById($identifier);
            } else {
                $article = $this->articleModel->findBySlug($identifier);
            }
            
            if (!$article) {
                $this->sendError('Article not found', 404);
                return;
            }

            // Increment view count for published articles
            if ($article['status'] === 'published') {
                $this->articleModel->incrementViewCount($article['id']);
                $article['view_count']++;
            }

            $this->sendResponse($article);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch article', 500);
        }
    }

    /**
     * Create new article
     */
    public function create() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                return;
            }

            $data = $this->getJsonInput();
            
            // Validate required fields
            $errors = $this->validateArticleData($data);
            if (!empty($errors)) {
                $this->sendError('Validation failed', 400, $errors);
                return;
            }

            // Set default values for optional fields
            $data['subtitle'] = ''; // Always empty for Medium-like experience
            $data['featured_image_url'] = $data['featured_image_url'] ?? null;
            $data['publication_id'] = $data['publication_id'] ?? null;
            
            // Calculate reading time
            $data['reading_time'] = $this->articleModel->calculateReadingTime($data['content']);
            $data['author_id'] = $user['id'];

            $article = $this->articleModel->create($data);
            
            // Scan content for potential issues after creation
            if ($article && isset($article['id'])) {
                $contentToScan = $data['title'] . ' ' . $data['content'];
                $this->contentFilter->scanContent('article', $article['id'], $contentToScan);
            }
            
            if ($article) {
                $this->sendResponse($article, 'Article created successfully', 201);
            } else {
                $this->sendError('Failed to create article', 500);
            }

        } catch (Exception $e) {
            error_log('Article creation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('Request data: ' . json_encode($data ?? []));
            $this->sendError('Failed to create article: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update existing article
     */
    public function update() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Check if article exists and user owns it
            $existingArticle = $this->articleModel->findById($id);
            if (!$existingArticle) {
                $this->sendError('Article not found', 404);
                return;
            }

            if ($existingArticle['author_id'] != $user['id']) {
                $this->sendError('Permission denied', 403);
                return;
            }

            $data = $this->getJsonInput();
            
            // Validate data
            $errors = $this->validateArticleData($data, false);
            if (!empty($errors)) {
                $this->sendError('Validation failed', 400, $errors);
                return;
            }

            // Recalculate reading time if content changed
            if (isset($data['content'])) {
                $data['reading_time'] = $this->articleModel->calculateReadingTime($data['content']);
            }

            $article = $this->articleModel->update($id, $data);
            
            // Scan updated content for potential issues
            if ($article && isset($data['content'])) {
                $contentToScan = ($data['title'] ?? $existingArticle['title']) . ' ' . $data['content'];
                $this->contentFilter->scanContent('article', $id, $contentToScan);
            }
            
            if ($article) {
                $this->sendResponse($article, 'Article updated successfully');
            } else {
                $this->sendError('Failed to update article', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to update article', 500);
        }
    }

    /**
     * Delete article
     */
    public function delete() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            $result = $this->articleModel->deleteArticle($id, $user['id']);
            
            if ($result) {
                $this->sendResponse(null, 'Article deleted successfully');
            } else {
                $this->sendError('Failed to delete article or permission denied', 403);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to delete article', 500);
        }
    }

    /**
     * Get user's drafts
     */
    public function drafts() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);

            $drafts = $this->articleModel->getUserDrafts($user['id'], $page, $limit);

            $this->sendResponse([
                'drafts' => $drafts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch drafts', 500);
        }
    }

    /**
     * Get trending articles
     */
    public function trending() {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $articles = $this->articleModel->getTrendingArticles($limit);
            
            $this->sendResponse($articles);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch trending articles', 500);
        }
    }

    /**
     * Search articles
     */
    public function search() {
        try {
            $query = $_GET['q'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);

            if (empty($query)) {
                $this->sendError('Search query is required', 400);
                return;
            }

            $filters = ['search' => $query];
            
            // Add additional filters
            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }

            if (!empty($_GET['author_id'])) {
                $filters['author_id'] = $_GET['author_id'];
            }

            $articles = $this->articleModel->getArticles($filters, $page, $limit);

            $this->sendResponse([
                'articles' => $articles,
                'query' => $query,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Search failed', 500);
        }
    }

    /**
     * Publish an article with SEO metadata generation
     */
    public function publish() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Get publishing options from request body
            $data = $this->getJsonInput();
            $publishingOptions = [
                'allow_comments' => $data['allow_comments'] ?? true,
                'include_in_search' => $data['include_in_search'] ?? true,
                'notify_followers' => $data['notify_followers'] ?? true
            ];

            $article = $this->articleModel->publish($id, $user['id'], $publishingOptions);
            
            if ($article) {
                // Generate SEO metadata
                $seoMetadata = $this->generateSEOMetadata($article);
                
                $response = [
                    'article' => $article,
                    'seo_metadata' => $seoMetadata,
                    'article_url' => $this->generateArticleUrl($article['slug'])
                ];
                
                $this->sendResponse($response, 'Article published successfully');
            } else {
                $this->sendError('Failed to publish article or permission denied', 403);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to publish article', 500);
        }
    }

    /**
     * Unpublish an article
     */
    public function unpublish() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            $article = $this->articleModel->unpublish($id, $user['id']);
            
            if ($article) {
                $this->sendResponse($article, 'Article unpublished successfully');
            } else {
                $this->sendError('Failed to unpublish article or permission denied', 403);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to unpublish article', 500);
        }
    }

    /**
     * Archive an article
     */
    public function archive() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            $article = $this->articleModel->archive($id, $user['id']);
            
            if ($article) {
                $this->sendResponse($article, 'Article archived successfully');
            } else {
                $this->sendError('Failed to archive article or permission denied', 403);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to archive article', 500);
        }
    }

    /**
     * Get articles by status for authenticated user
     */
    public function getByStatus() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $status = $_GET['status'] ?? 'draft';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);

            // Validate status
            if (!in_array($status, ['draft', 'published', 'archived'])) {
                $this->sendError('Invalid status', 400);
                return;
            }

            $articles = $this->articleModel->getArticlesByStatus($user['id'], $status, $page, $limit);

            $this->sendResponse([
                'articles' => $articles,
                'status' => $status,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch articles', 500);
        }
    }

    /**
     * Get article preview data for publishing dialog
     */
    public function preview() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            $preview = $this->articleModel->getArticlePreview($id, $user['id']);
            
            if ($preview) {
                $response = [
                    'preview' => $preview,
                    'social_preview' => [
                        'title' => $preview['title'],
                        'description' => substr(strip_tags($preview['content']), 0, 160),
                        'image' => $preview['featured_image_url'],
                        'url' => "http://localhost:8000/article/" . $preview['slug']
                    ]
                ];
                
                $this->sendResponse($response);
            } else {
                $this->sendError('Article not found or permission denied', 404);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to generate preview', 500);
        }
    }

    /**
     * Validate article data
     */
    private function validateArticleData($data, $isCreate = true) {
        $errors = [];

        if ($isCreate || isset($data['title'])) {
            if (empty($data['title']) || strlen(trim($data['title'])) < 1) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($data['title']) > 255) {
                $errors['title'] = 'Title must be less than 255 characters';
            }
        }



        if ($isCreate || isset($data['content'])) {
            if (empty($data['content'])) {
                $errors['content'] = 'Content is required';
            }
        }

        if (isset($data['tags'])) {
            if (!is_array($data['tags'])) {
                $errors['tags'] = 'Tags must be an array';
            } elseif (count($data['tags']) > 5) {
                $errors['tags'] = 'Maximum 5 tags allowed';
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'])) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }

    /**
     * Generate SEO metadata for an article
     */
    private function generateSEOMetadata($article) {
        $baseUrl = $this->getBaseUrl();
        $articleUrl = $baseUrl . '/article/' . $article['slug'];
        
        // Generate meta description from content or subtitle
        $description = $this->generateMetaDescription($article);
        
        // Extract keywords from tags and content
        $keywords = $this->extractKeywords($article);
        
        return [
            'title' => $article['title'] . ' | Medium Clone',
            'description' => $description,
            'keywords' => $keywords,
            'canonical_url' => $articleUrl,
            'og_title' => $article['title'],
            'og_description' => $description,
            'og_image' => $article['featured_image_url'],
            'og_url' => $articleUrl,
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $article['title'],
            'twitter_description' => $description,
            'twitter_image' => $article['featured_image_url'],
            'structured_data' => $this->generateStructuredData($article, $articleUrl)
        ];
    }

    /**
     * Generate meta description from article content
     */
    private function generateMetaDescription($article, $maxLength = 160) {
        // Extract plain text from content
        $content = is_string($article['content']) ? $article['content'] : json_encode($article['content']);
        $plainText = strip_tags($content);
        $description = $plainText;
        
        if (strlen($description) <= $maxLength) {
            return $description;
        }
        
        // Truncate at word boundary
        $truncated = substr($description, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($description, 0, $lastSpace) . '...';
        }
        
        return $truncated . '...';
    }

    /**
     * Extract keywords from article tags and content
     */
    private function extractKeywords($article) {
        $keywords = [];
        
        // Add tags as keywords
        if (!empty($article['tags'])) {
            $tags = is_array($article['tags']) ? $article['tags'] : explode(',', $article['tags']);
            $keywords = array_merge($keywords, $tags);
        }
        
        // Add some content-based keywords (simplified approach)
        $content = is_string($article['content']) ? $article['content'] : json_encode($article['content']);
        $plainText = strip_tags($content);
        $words = str_word_count($plainText, 1);
        
        // Get most common words (excluding common stop words)
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'a', 'an'];
        
        $wordCounts = array_count_values(array_map('strtolower', $words));
        $filteredWords = array_diff_key($wordCounts, array_flip($stopWords));
        arsort($filteredWords);
        
        // Take top 5 content keywords
        $contentKeywords = array_slice(array_keys($filteredWords), 0, 5);
        $keywords = array_merge($keywords, $contentKeywords);
        
        return array_unique($keywords);
    }

    /**
     * Generate structured data (JSON-LD) for the article
     */
    private function generateStructuredData($article, $articleUrl) {
        $baseUrl = $this->getBaseUrl();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $this->generateMetaDescription($article),
            'image' => $article['featured_image_url'] ? [$article['featured_image_url']] : [],
            'author' => [
                '@type' => 'Person',
                'name' => $article['username'],
                'url' => $baseUrl . '/user/' . $article['username']
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Medium Clone',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $baseUrl . '/logo.png'
                ]
            ],
            'datePublished' => $article['published_at'],
            'dateModified' => $article['updated_at'],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl
            ],
            'keywords' => implode(', ', $this->extractKeywords($article)),
            'wordCount' => $this->getWordCount($article['content']),
            'timeRequired' => 'PT' . $article['reading_time'] . 'M'
        ];
    }

    /**
     * Generate article URL from slug
     */
    private function generateArticleUrl($slug) {
        return $this->getBaseUrl() . '/article/' . $slug;
    }

    /**
     * Get base URL for the application
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Get related articles for a specific article
     */
    public function related() {
        try {
            $articleId = $_GET['id'] ?? null;
            if (!$articleId) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            $limit = (int)($_GET['limit'] ?? 5);
            $relatedArticles = $this->articleModel->getRelatedArticles($articleId, $limit);

            $this->sendResponse($relatedArticles);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch related articles', 500);
        }
    }

    /**
     * Get more articles from the same author
     */
    public function moreFromAuthor() {
        try {
            $authorId = $_GET['author_id'] ?? null;
            if (!$authorId) {
                $this->sendError('Author ID is required', 400);
                return;
            }

            $excludeId = $_GET['exclude_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 5);
            
            $articles = $this->articleModel->getMoreFromAuthor($authorId, $excludeId, $limit);

            $this->sendResponse($articles);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch author articles', 500);
        }
    }

    /**
     * Get recommended articles
     */
    public function recommended() {
        try {
            // Get current user if authenticated (optional)
            $user = null;
            try {
                $user = $this->authMiddleware->authenticate();
            } catch (Exception $e) {
                // User not authenticated, continue with general recommendations
            }

            $limit = (int)($_GET['limit'] ?? 10);
            $userId = $user ? $user['id'] : null;
            
            $articles = $this->articleModel->getRecommendedArticles($userId, $limit);

            $this->sendResponse($articles);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch recommended articles', 500);
        }
    }

    /**
     * Track article view
     */
    public function trackView() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['article_id'])) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Get user ID if authenticated (optional)
            $userId = null;
            try {
                $user = $this->authMiddleware->authenticate();
                $userId = $user['id'];
            } catch (Exception $e) {
                // Anonymous view tracking is allowed
            }

            $result = $this->articleModel->trackView(
                $data['article_id'],
                $userId,
                $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
                $data['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

            if ($result) {
                $this->sendResponse(['tracked' => true], 'View tracked successfully');
            } else {
                $this->sendError('Failed to track view', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to track view', 500);
        }
    }

    /**
     * Track article read (when user actually reads the article)
     */
    public function trackRead() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['article_id'])) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Get user ID if authenticated (optional)
            $userId = null;
            try {
                $user = $this->authMiddleware->authenticate();
                $userId = $user['id'];
            } catch (Exception $e) {
                // Anonymous read tracking is allowed
            }

            $result = $this->articleModel->trackRead(
                $data['article_id'],
                $userId,
                $data['time_spent'] ?? 0,
                $data['scroll_depth'] ?? 0
            );

            if ($result) {
                $this->sendResponse(['tracked' => true], 'Read tracked successfully');
            } else {
                $this->sendError('Failed to track read', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to track read', 500);
        }
    }

    /**
     * Send detailed analytics data
     */
    public function analytics() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['article_id'])) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Get user ID if authenticated (optional)
            $userId = null;
            try {
                $user = $this->authMiddleware->authenticate();
                $userId = $user['id'];
            } catch (Exception $e) {
                // Anonymous analytics is allowed
            }

            $result = $this->articleModel->recordAnalytics(
                $data['article_id'],
                $userId,
                $data['time_spent'] ?? 0,
                $data['scroll_depth'] ?? 0,
                $data['is_read'] ?? false,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

            if ($result) {
                $this->sendResponse(['recorded' => true], 'Analytics recorded successfully');
            } else {
                $this->sendError('Failed to record analytics', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to record analytics', 500);
        }
    }

    /**
     * Get article analytics (for authors)
     */
    public function getAnalytics() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $articleId = $_GET['id'] ?? null;
            if (!$articleId) {
                $this->sendError('Article ID is required', 400);
                return;
            }

            // Verify ownership
            $article = $this->articleModel->findById($articleId);
            if (!$article || $article['author_id'] != $user['id']) {
                $this->sendError('Article not found or permission denied', 404);
                return;
            }

            $analytics = $this->articleModel->getArticleAnalytics($articleId);
            
            $this->sendResponse($analytics);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch analytics', 500);
        }
    }

    /**
     * Get popular articles based on view metrics
     */
    public function popular() {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $timeframe = $_GET['timeframe'] ?? 'week'; // week, month, all
            
            $articles = $this->articleModel->getPopularArticles($limit, $timeframe);
            
            $this->sendResponse($articles);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch popular articles', 500);
        }
    }

    /**
     * Submit article to publication
     * POST /api/articles/submit-to-publication
     */
    public function submitToPublication() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$articleId || !$publicationId) {
                return $this->sendError('Article ID and Publication ID are required', 400);
            }
            
            // Check if user owns the article
            $article = $this->articleModel->findById($articleId);
            if (!$article || $article['author_id'] != $this->currentUser['id']) {
                return $this->sendError('Article not found or insufficient permissions', 403);
            }
            
            // Check if user is a member of the publication
            require_once __DIR__ . '/../models/Publication.php';
            $publicationModel = new Publication();
            
            if (!$publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'writer')) {
                return $this->sendError('You are not a member of this publication', 403);
            }
            
            $success = $this->articleModel->submitToPublication($articleId, $publicationId);
            
            if ($success) {
                // Notify publication admins and editors about the submission
                require_once __DIR__ . '/../models/Publication.php';
                require_once __DIR__ . '/../models/Notification.php';
                
                $publicationModel = new Publication();
                $notificationModel = new Notification();
                
                $publication = $publicationModel->getById($publicationId);
                $members = $publicationModel->getMembers($publicationId);
                
                // Notify admins and editors
                foreach ($members as $member) {
                    if (in_array($member['role'], ['admin', 'editor'])) {
                        $notificationModel->createNotification(
                            $member['id'],
                            'article_submission',
                            "New article submission: \"{$article['title']}\" by {$this->currentUser['username']} in {$publication['name']}",
                            $articleId
                        );
                    }
                }
                
                // Also notify publication owner
                if ($publication['owner_id'] != $this->currentUser['id']) {
                    $notificationModel->createNotification(
                        $publication['owner_id'],
                        'article_submission',
                        "New article submission: \"{$article['title']}\" by {$this->currentUser['username']} in {$publication['name']}",
                        $articleId
                    );
                }
                
                return $this->sendResponse([
                    'article_id' => $articleId,
                    'publication_id' => $publicationId,
                    'status' => 'submitted'
                ], 'Article submitted to publication successfully');
            } else {
                return $this->sendError('Failed to submit article to publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Approve article for publication
     * POST /api/articles/approve
     */
    public function approveArticle() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            
            if (!$articleId) {
                return $this->sendError('Article ID is required', 400);
            }
            
            // Check if user can manage this article
            if (!$this->articleModel->canManageInPublication($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions to approve this article', 403);
            }
            
            // Get article details before approval
            $article = $this->articleModel->findById($articleId);
            
            $success = $this->articleModel->approveForPublication($articleId);
            
            if ($success) {
                // Notify article author about approval
                require_once __DIR__ . '/../models/Notification.php';
                require_once __DIR__ . '/../models/Publication.php';
                
                $notificationModel = new Notification();
                $publicationModel = new Publication();
                
                $publication = $publicationModel->getById($article['publication_id']);
                
                if ($article['author_id'] != $this->currentUser['id']) {
                    $notificationModel->createNotification(
                        $article['author_id'],
                        'article_approved',
                        "Your article \"{$article['title']}\" has been approved and published in {$publication['name']}",
                        $articleId
                    );
                }
                
                return $this->sendResponse([
                    'article_id' => $articleId,
                    'status' => 'published'
                ], 'Article approved and published successfully');
            } else {
                return $this->sendError('Failed to approve article', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Reject article submission
     * POST /api/articles/reject
     */
    public function rejectArticle() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            
            if (!$articleId) {
                return $this->sendError('Article ID is required', 400);
            }
            
            // Check if user can manage this article
            if (!$this->articleModel->canManageInPublication($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions to reject this article', 403);
            }
            
            // Get article details before rejection
            $article = $this->articleModel->findById($articleId);
            
            $success = $this->articleModel->rejectSubmission($articleId);
            
            if ($success) {
                // Notify article author about rejection
                require_once __DIR__ . '/../models/Notification.php';
                require_once __DIR__ . '/../models/Publication.php';
                
                $notificationModel = new Notification();
                $publicationModel = new Publication();
                
                $publication = $publicationModel->getById($article['publication_id']);
                
                if ($article['author_id'] != $this->currentUser['id']) {
                    $notificationModel->createNotification(
                        $article['author_id'],
                        'article_rejected',
                        "Your article submission \"{$article['title']}\" was not accepted for {$publication['name']}",
                        $articleId
                    );
                }
                
                return $this->sendResponse([
                    'article_id' => $articleId,
                    'status' => 'rejected'
                ], 'Article submission rejected successfully');
            } else {
                return $this->sendError('Failed to reject article submission', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get articles pending approval for publication
     * GET /api/articles/pending-approval?publication_id=:id
     */
    public function getPendingApproval() {
        try {
            $this->requireAuth();
            
            $publicationId = $_GET['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            // Check if user can manage this publication
            require_once __DIR__ . '/../models/Publication.php';
            $publicationModel = new Publication();
            
            if (!$publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'editor')) {
                return $this->sendError('Insufficient permissions to view pending articles', 403);
            }
            
            $articles = $this->articleModel->getPendingApproval($publicationId);
            
            return $this->sendResponse($articles);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get word count from content
     */
    private function getWordCount($content) {
        $plainText = is_string($content) ? strip_tags($content) : strip_tags(json_encode($content));
        return str_word_count($plainText);
    }
}