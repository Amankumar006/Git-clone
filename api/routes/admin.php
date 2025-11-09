<?php

require_once __DIR__ . '/../controllers/AdminController.php';

$adminController = new AdminController();

// Handle different HTTP methods and routes
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Remove 'api' and 'admin' from path segments
$pathSegments = array_slice($pathSegments, 2);
$route = implode('/', $pathSegments);

switch ($method) {
    case 'GET':
        switch (true) {
            case $route === 'dashboard':
                $adminController->getDashboardStats();
                break;
            case $route === 'users':
                $adminController->getUsers();
                break;
            case $route === 'content':
                $adminController->getContentManagement();
                break;
            case $route === 'featured':
                $adminController->getFeaturedContent();
                break;
            case $route === 'settings':
                $adminController->getSystemSettings();
                break;
            case $route === 'homepage':
                $adminController->getHomepageManagement();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'POST':
        switch (true) {
            case $route === 'featured':
                $adminController->updateFeaturedContent();
                break;
            case $route === 'content/action':
                $adminController->updateContentStatus();
                break;
            case $route === 'homepage/reorder':
                $adminController->reorderHomepageSections();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
        }
        break;
        
    case 'PUT':
        switch (true) {
            case preg_match('/^users\/(\d+)$/', $route, $matches):
                $_GET['id'] = $matches[1];
                $adminController->updateUserStatus();
                break;
            case $route === 'settings':
                $adminController->updateSystemSettings();
                break;
            case preg_match('/^homepage\/sections\/(\d+)$/', $route, $matches):
                $_GET['section_id'] = $matches[1];
                $adminController->updateHomepageSection();
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