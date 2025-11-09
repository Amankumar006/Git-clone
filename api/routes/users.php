<?php
/**
 * User Routes
 */

require_once __DIR__ . '/../controllers/UserController.php';

$userController = new UserController();
$method = $_SERVER['REQUEST_METHOD'];

// Get the action from the global segments set by index.php
$action = $GLOBALS['action'] ?? '';
$segments = $GLOBALS['segments'] ?? [];

// The route is the action (e.g., 'profile', 'articles', etc.)
$route = '/' . $action;

switch ($method) {
    case 'GET':
        switch ($route) {
            case '/profile':
                $userController->getProfile();
                break;
            case '/articles':
                $userController->getUserArticles();
                break;
            case '/followers':
                $userController->getFollowers();
                break;
            case '/following':
                $userController->getFollowing();
                break;
            case '/notification-preferences':
                $userController->getNotificationPreferences();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
                break;
        }
        break;
    
    case 'PUT':
        switch ($route) {
            case '/profile':
                $userController->updateProfile();
                break;
            case '/password':
                $userController->updatePassword();
                break;
            case '/notification-preferences':
                $userController->updateNotificationPreferences();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
                break;
        }
        break;
    
    case 'POST':
        switch ($route) {
            case '/upload-avatar':
                $userController->uploadAvatar();
                break;
            case '/follow':
                $userController->follow();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
                break;
        }
        break;
    
    case 'DELETE':
        switch ($route) {
            case '/follow':
                $userController->unfollow();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
                break;
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}