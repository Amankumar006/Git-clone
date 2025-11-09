<?php

require_once __DIR__ . '/../controllers/AnalyticsController.php';
require_once __DIR__ . '/../controllers/AnalyticsTrackingController.php';

$analyticsController = new AnalyticsController();
$trackingController = new AnalyticsTrackingController();

// Handle different HTTP methods and routes
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Remove 'api' and 'analytics' from path segments
$pathSegments = array_slice($pathSegments, 2);
$route = implode('/', $pathSegments);

switch ($method) {
    case 'GET':
        switch (true) {
            case $route === '' || $route === 'platform':
                $analyticsController->getPlatformAnalytics();
                break;
            case $route === 'comparative':
                $analyticsController->getComparativeAnalytics();
                break;
            case $route === 'health':
                $analyticsController->getHealthMetrics();
                break;
            case $route === 'retention':
                $analyticsController->getRetentionAnalytics();
                break;
            case $route === 'patterns':
                $analyticsController->getEngagementPatterns();
                break;
            case $route === 'export':
                $analyticsController->exportAnalytics();
                break;
            case $route === 'tracking-dashboard':
                $trackingController->getDashboardData();
                break;
            case $route === 'realtime':
                $trackingController->getRealTimeAnalytics();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'POST':
        switch (true) {
            case $route === 'track':
                $trackingController->trackEvent();
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