<?php
/**
 * Medium Clone API Entry Point
 * Routes all API requests to appropriate handlers
 */

// Load configuration and dependencies
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/utils/ErrorHandler.php';

// Set CORS and security headers
CorsHandler::setCorsHeaders();
CorsHandler::setSecurityHeaders();

// Set content type to JSON
header('Content-Type: application/json');

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove API base path from URI
$basePath = '/medium-clone/api';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
} elseif (strpos($uri, '/api') === 0) {
    // Handle case when running PHP built-in server from api directory
    $uri = substr($uri, 4); // Remove '/api'
}

// Remove leading slash
$uri = ltrim($uri, '/');

// Split URI into segments
$segments = explode('/', $uri);
$resource = $segments[0] ?? '';
$action = $segments[1] ?? '';
$id = $segments[2] ?? '';

// Basic routing
try {
    switch ($resource) {
        case '':
        case 'health':
            // Health check endpoint
            ErrorHandler::success([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0'
            ], 'API is running');
            break;
            
        case 'auth':
            // Make variables available to the route file
            $GLOBALS['action'] = $action;
            $GLOBALS['method'] = $method;
            $GLOBALS['segments'] = $segments;
            require_once __DIR__ . '/routes/auth.php';
            break;
            
        case 'users':
            // Make variables available to the route file
            $GLOBALS['action'] = $action;
            $GLOBALS['method'] = $method;
            $GLOBALS['segments'] = $segments;
            require_once __DIR__ . '/routes/users.php';
            break;
            
        case 'articles':
            require_once __DIR__ . '/routes/articles.php';
            break;
            
        case 'search':
            require_once __DIR__ . '/routes/search.php';
            break;
            
        case 'feed':
            require_once __DIR__ . '/routes/feed.php';
            break;
            
        case 'tags':
            require_once __DIR__ . '/routes/tags.php';
            break;
            
        case 'publications':
            require_once __DIR__ . '/routes/publications.php';
            break;
            
        case 'claps':
            $endpoint = $uri; // Pass full endpoint to claps router
            require_once __DIR__ . '/routes/claps.php';
            break;
            
        case 'comments':
            $endpoint = $uri; // Pass full endpoint to comments router
            require_once __DIR__ . '/routes/comments.php';
            break;
            
        case 'bookmarks':
            $endpoint = $uri; // Pass full endpoint to bookmarks router
            require_once __DIR__ . '/routes/bookmarks.php';
            break;
            
        case 'follows':
            $endpoint = $uri; // Pass full endpoint to follows router
            require_once __DIR__ . '/routes/follows.php';
            break;
            
        case 'notifications':
            $endpoint = $uri; // Pass full endpoint to notifications router
            require_once __DIR__ . '/routes/notifications.php';
            break;
            
        case 'dashboard':
            $endpoint = $uri; // Pass full endpoint to dashboard router
            require_once __DIR__ . '/routes/dashboard.php';
            break;
            
        case 'workflow':
            require_once __DIR__ . '/routes/workflow.php';
            break;
            
        case 'moderation':
            require_once __DIR__ . '/routes/moderation.php';
            break;
            
        case 'admin':
            require_once __DIR__ . '/routes/admin.php';
            break;
            
        case 'analytics':
            require_once __DIR__ . '/routes/analytics.php';
            break;
            
        case 'security':
            require_once __DIR__ . '/routes/security.php';
            break;
            
        case 'seo':
            $endpoint = $action; // Pass the action as endpoint
            require_once __DIR__ . '/routes/seo.php';
            break;
            
        case 'upload':
            // Handle file uploads
            require_once __DIR__ . '/routes/upload.php';
            break;
            
        case 'uploads':
            // Serve uploaded files
            require_once __DIR__ . '/routes/uploads.php';
            break;
            
        default:
            ErrorHandler::notFoundError('API endpoint not found');
    }
    
} catch (Exception $e) {
    ErrorHandler::handleException($e);
}