<?php
/**
 * Dashboard Routes
 * Handles writer and reader dashboard functionality
 */

require_once __DIR__ . '/../controllers/DashboardController.php';

$controller = new DashboardController();
$method = $_SERVER['REQUEST_METHOD'];

// Parse the endpoint
$endpoint = $GLOBALS['endpoint'] ?? '';
$segments = explode('/', trim($endpoint, '/'));
$action = $segments[1] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'writer-stats':
                $controller->writerStats();
                break;
                
            case 'writer-analytics':
                $controller->writerAnalytics();
                break;
                
            case 'user-articles':
                $controller->userArticles();
                break;
                
            case 'reader-stats':
                $controller->readerStats();
                break;
                
            case 'bookmarks':
                $controller->getBookmarks();
                break;
                
            case 'following-feed':
                $controller->getFollowingFeed();
                break;
                
            case 'reading-history':
                $controller->getReadingHistory();
                break;
                
            case 'advanced-analytics':
                $controller->advancedAnalytics();
                break;
                
            case 'export-analytics':
                $controller->exportAnalytics();
                break;
                
            default:
                ErrorHandler::notFoundError('Dashboard endpoint not found');
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'bulk-operations':
                $controller->bulkOperations();
                break;
                
            default:
                ErrorHandler::notFoundError('Dashboard endpoint not found');
        }
        break;
        
    default:
        ErrorHandler::methodNotAllowedError();
}