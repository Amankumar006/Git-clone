<?php

require_once __DIR__ . '/../controllers/ModerationController.php';

$moderationController = new ModerationController();

// Handle different HTTP methods and routes
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Remove 'api' and 'moderation' from path segments
$pathSegments = array_slice($pathSegments, 2);
$route = implode('/', $pathSegments);

switch ($method) {
    case 'POST':
        switch ($route) {
            case 'reports':
                $moderationController->createReport();
                break;
            case 'approve':
                $moderationController->approveContent();
                break;
            case 'remove':
                $moderationController->removeContent();
                break;
            case 'warn':
                $moderationController->warnUser();
                break;
            case 'suspend':
                $moderationController->suspendUser();
                break;
            case 'scan':
                $moderationController->scanContent();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'GET':
        switch (true) {
            case $route === 'reports':
                $moderationController->getPendingReports();
                break;
            case $route === 'history':
                $moderationController->getModerationHistory();
                break;
            case $route === 'flagged':
                $moderationController->getFlaggedContent();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'PUT':
        switch (true) {
            case preg_match('/^reports\/(\d+)$/', $route, $matches):
                $_GET['id'] = $matches[1];
                $moderationController->updateReportStatus();
                break;
            case preg_match('/^flags\/(\d+)\/reviewed$/', $route, $matches):
                $_GET['id'] = $matches[1];
                $moderationController->markFlagReviewed();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}