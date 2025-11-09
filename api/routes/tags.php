<?php

require_once __DIR__ . '/../controllers/TagController.php';

$tagController = new TagController();

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove the /api prefix if present
$path = preg_replace('#^/api#', '', $path);

switch ($method) {
    case 'GET':
        if ($path === '/tags' || $path === '/tags/') {
            // Get all tags
            $tagController->index();
        } elseif ($path === '/tags/popular') {
            // Get popular tags
            $tagController->popular();
        } elseif ($path === '/tags/trending') {
            // Get trending tags
            $tagController->trending();
        } elseif ($path === '/tags/search') {
            // Search tags
            $tagController->search();
        } elseif ($path === '/tags/suggestions') {
            // Get tag suggestions
            $tagController->suggestions();
        } elseif ($path === '/tags/show') {
            // Get single tag with articles
            $tagController->show();
        } elseif ($path === '/tags/related') {
            // Get related tags
            $tagController->related();
        } elseif ($path === '/tags/cloud') {
            // Get tag cloud
            $tagController->cloud();
        } elseif ($path === '/tags/categories') {
            // Get tags by categories
            $tagController->categories();
        } elseif ($path === '/tags/following') {
            // Get user's followed tags
            $tagController->following();
        } elseif ($path === '/tags/stats') {
            // Get tag statistics
            $tagController->stats();
        } elseif ($path === '/tags/similar') {
            // Get similar tags
            $tagController->similar();
        } elseif ($path === '/tags/authors') {
            // Get top authors for tag
            $tagController->authors();
        } elseif ($path === '/tags/advanced-search') {
            // Advanced tag search
            $tagController->advancedSearch();
        } elseif ($path === '/tags/check-following') {
            // Check if user is following tag
            $tagController->checkFollowing();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tag endpoint not found']);
        }
        break;

    case 'POST':
        if ($path === '/tags/create') {
            // Create new tag
            $tagController->create();
        } elseif ($path === '/tags/follow') {
            // Follow a tag
            $tagController->follow();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tag endpoint not found']);
        }
        break;

    case 'PUT':
        if ($path === '/tags/update') {
            // Update tag
            $tagController->update();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tag endpoint not found']);
        }
        break;

    case 'DELETE':
        if ($path === '/tags/delete') {
            // Delete tag
            $tagController->delete();
        } elseif ($path === '/tags/unfollow') {
            // Unfollow a tag
            $tagController->unfollow();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tag endpoint not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}