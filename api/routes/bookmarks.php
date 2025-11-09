<?php

require_once __DIR__ . '/../controllers/BookmarkController.php';

$bookmarkController = new BookmarkController();

// Handle bookmark routes
switch ($method) {
    case 'POST':
        if ($endpoint === 'bookmarks/add') {
            $bookmarkController->addBookmark();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if ($endpoint === 'bookmarks/remove') {
            $bookmarkController->removeBookmark();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'GET':
        if ($endpoint === 'bookmarks/user' || preg_match('/^bookmarks\/user\/(\d+)$/', $endpoint, $matches)) {
            $userId = isset($matches[1]) ? $matches[1] : null;
            $bookmarkController->getUserBookmarks($userId);
        } elseif (preg_match('/^bookmarks\/status\/(\d+)$/', $endpoint, $matches)) {
            $bookmarkController->getBookmarkStatus($matches[1]);
        } elseif ($endpoint === 'bookmarks/popular') {
            $bookmarkController->getPopularBookmarks();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}