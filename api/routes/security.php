<?php

require_once __DIR__ . '/../controllers/SecurityController.php';

$securityController = new SecurityController();

// Handle different HTTP methods and routes
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Remove 'api' and 'security' from path segments
$pathSegments = array_slice($pathSegments, 2);
$route = implode('/', $pathSegments);

switch ($method) {
    case 'GET':
        switch (true) {
            case $route === 'dashboard':
                $securityController->getSecurityDashboard();
                break;
            case $route === 'events':
                $securityController->getSecurityEvents();
                break;
            case $route === 'audit':
                $securityController->getAuditLog();
                break;
            case $route === 'health':
                $securityController->getSystemHealth();
                break;
            case $route === 'report':
                $securityController->generateSecurityReport();
                break;
            case $route === 'export/audit':
                $securityController->exportAuditLog();
                break;
            case $route === 'scan':
                $securityController->runSecurityScan();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'POST':
        switch (true) {
            case $route === 'block-ip':
                $securityController->blockIP();
                break;
            case $route === 'alerts':
                $securityController->createAlert();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'PUT':
        switch (true) {
            case preg_match('/^alerts\/(\d+)\/resolve$/', $route, $matches):
                $_GET['id'] = $matches[1];
                $securityController->resolveAlert();
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