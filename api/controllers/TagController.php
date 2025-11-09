<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class TagController extends BaseController {
    private $tagModel;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->tagModel = new Tag();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Get all tags with article counts
     */
    public function index() {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $tags = $this->tagModel->getAllTags($limit);
            
            $this->sendResponse($tags);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tags', 500);
        }
    }

    /**
     * Get popular tags
     */
    public function popular() {
        try {
            $limit = (int)($_GET['limit'] ?? 20);
            $tags = $this->tagModel->getPopularTags($limit);
            
            $this->sendResponse($tags);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch popular tags', 500);
        }
    }

    /**
     * Get trending tags
     */
    public function trending() {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $tags = $this->tagModel->getTrendingTags($limit);
            
            $this->sendResponse($tags);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch trending tags', 500);
        }
    }

    /**
     * Search tags
     */
    public function search() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);

            if (empty($query)) {
                $this->sendError('Search query is required', 400);
                return;
            }

            $tags = $this->tagModel->searchTags($query, $limit);
            
            $this->sendResponse([
                'tags' => $tags,
                'query' => $query
            ]);

        } catch (Exception $e) {
            $this->sendError('Tag search failed', 500);
        }
    }

    /**
     * Get tag suggestions for autocomplete
     */
    public function suggestions() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 5);

            if (empty($query)) {
                $this->sendResponse([]);
                return;
            }

            $suggestions = $this->tagModel->getSuggestions($query, $limit);
            
            $this->sendResponse($suggestions);

        } catch (Exception $e) {
            $this->sendError('Failed to get tag suggestions', 500);
        }
    }

    /**
     * Get single tag by slug with articles
     */
    public function show() {
        try {
            $slug = $_GET['slug'] ?? null;
            
            if (!$slug) {
                $this->sendError('Tag slug is required', 400);
                return;
            }

            $tag = $this->tagModel->getBySlug($slug);
            
            if (!$tag) {
                $this->sendError('Tag not found', 404);
                return;
            }

            // Get articles for this tag
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $articles = $this->tagModel->getArticlesByTag($slug, $page, $limit);

            $this->sendResponse([
                'tag' => $tag,
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tag', 500);
        }
    }

    /**
     * Get related tags
     */
    public function related() {
        try {
            $tagId = $_GET['tag_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 5);
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $relatedTags = $this->tagModel->getRelatedTags($tagId, $limit);
            
            $this->sendResponse($relatedTags);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch related tags', 500);
        }
    }

    /**
     * Create new tag (admin only)
     */
    public function create() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            // For now, allow any authenticated user to create tags
            // In production, you might want to restrict this to admins
            
            $data = $this->getJsonInput();
            
            // Validate tag name
            $errors = $this->tagModel->validateTagName($data['name'] ?? '');
            if (!empty($errors)) {
                $this->sendError('Validation failed', 400, $errors);
                return;
            }

            $tag = $this->tagModel->create($data['name'], $data['description'] ?? '');
            
            if ($tag) {
                $this->sendResponse($tag, 'Tag created successfully', 201);
            } else {
                $this->sendError('Failed to create tag or tag already exists', 400);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to create tag', 500);
        }
    }

    /**
     * Update tag (admin only)
     */
    public function update() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $data = $this->getJsonInput();
            
            // Validate tag name if provided
            if (isset($data['name'])) {
                $errors = $this->tagModel->validateTagName($data['name']);
                if (!empty($errors)) {
                    $this->sendError('Validation failed', 400, $errors);
                    return;
                }
            }

            $tag = $this->tagModel->update($id, $data);
            
            if ($tag) {
                $this->sendResponse($tag, 'Tag updated successfully');
            } else {
                $this->sendError('Failed to update tag', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to update tag', 500);
        }
    }

    /**
     * Delete tag (admin only)
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
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $result = $this->tagModel->delete($id);
            
            if ($result) {
                $this->sendResponse(null, 'Tag deleted successfully');
            } else {
                $this->sendError('Failed to delete tag or tag is in use', 400);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to delete tag', 500);
        }
    }

    /**
     * Get tag cloud
     */
    public function cloud() {
        try {
            $limit = (int)($_GET['limit'] ?? 50);
            $tagCloud = $this->tagModel->getTagCloud($limit);
            
            $this->sendResponse([
                'tags' => $tagCloud,
                'total_count' => count($tagCloud)
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tag cloud', 500);
        }
    }

    /**
     * Get tags organized by categories
     */
    public function categories() {
        try {
            $categories = $this->tagModel->getTagsByCategory();
            
            $this->sendResponse([
                'categories' => $categories,
                'total_categories' => count($categories)
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tag categories', 500);
        }
    }

    /**
     * Follow a tag
     */
    public function follow() {
        try {
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $tagId = $_POST['tag_id'] ?? $_GET['tag_id'] ?? null;
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $result = $this->tagModel->followTag($userId, $tagId);
            
            if ($result) {
                $this->sendResponse([
                    'following' => true,
                    'tag_id' => $tagId
                ], 'Tag followed successfully');
            } else {
                $this->sendError('Failed to follow tag', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to follow tag', 500);
        }
    }

    /**
     * Unfollow a tag
     */
    public function unfollow() {
        try {
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $tagId = $_POST['tag_id'] ?? $_GET['tag_id'] ?? null;
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $result = $this->tagModel->unfollowTag($userId, $tagId);
            
            if ($result) {
                $this->sendResponse([
                    'following' => false,
                    'tag_id' => $tagId
                ], 'Tag unfollowed successfully');
            } else {
                $this->sendError('Failed to unfollow tag', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Failed to unfollow tag', 500);
        }
    }

    /**
     * Get user's followed tags
     */
    public function following() {
        try {
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $followedTags = $this->tagModel->getUserFollowedTags($userId);
            
            $this->sendResponse([
                'tags' => $followedTags,
                'total_count' => count($followedTags)
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch followed tags', 500);
        }
    }

    /**
     * Get tag statistics and details
     */
    public function stats() {
        try {
            $tagId = $_GET['tag_id'] ?? null;
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $stats = $this->tagModel->getTagStats($tagId);
            $similarTags = $this->tagModel->getSimilarTags($tagId, 5);
            $topAuthors = $this->tagModel->getTopAuthorsForTag($tagId, 5);
            $activity = $this->tagModel->getTagActivity($tagId, 30);

            $this->sendResponse([
                'stats' => $stats,
                'similar_tags' => $similarTags,
                'top_authors' => $topAuthors,
                'activity' => $activity
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tag statistics', 500);
        }
    }

    /**
     * Get similar tags
     */
    public function similar() {
        try {
            $tagId = $_GET['tag_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $similarTags = $this->tagModel->getSimilarTags($tagId, $limit);
            
            $this->sendResponse([
                'tags' => $similarTags,
                'total_count' => count($similarTags)
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch similar tags', 500);
        }
    }

    /**
     * Get top authors for a tag
     */
    public function authors() {
        try {
            $tagId = $_GET['tag_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $authors = $this->tagModel->getTopAuthorsForTag($tagId, $limit);
            
            $this->sendResponse([
                'authors' => $authors,
                'total_count' => count($authors)
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to fetch tag authors', 500);
        }
    }

    /**
     * Advanced tag search
     */
    public function advancedSearch() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 20);
            
            $filters = [];
            if (!empty($_GET['min_articles'])) {
                $filters['min_articles'] = (int)$_GET['min_articles'];
            }
            if (!empty($_GET['created_after'])) {
                $filters['created_after'] = $_GET['created_after'];
            }

            $tags = $this->tagModel->advancedTagSearch($query, $filters, $limit);
            
            $this->sendResponse([
                'tags' => $tags,
                'query' => $query,
                'filters' => $filters,
                'total_count' => count($tags)
            ]);

        } catch (Exception $e) {
            $this->sendError('Advanced tag search failed', 500);
        }
    }

    /**
     * Check if user is following a tag
     */
    public function checkFollowing() {
        try {
            $authResult = $this->authMiddleware->authenticate();
            if (!$authResult['success']) {
                $this->sendError($authResult['message'], 401);
                return;
            }

            $userId = $authResult['user']['id'];
            $tagId = $_GET['tag_id'] ?? null;
            
            if (!$tagId) {
                $this->sendError('Tag ID is required', 400);
                return;
            }

            $isFollowing = $this->tagModel->isFollowingTag($userId, $tagId);
            
            $this->sendResponse([
                'following' => $isFollowing,
                'tag_id' => $tagId
            ]);

        } catch (Exception $e) {
            $this->sendError('Failed to check tag following status', 500);
        }
    }
}