<?php
/**
 * Authentication Routes
 */

require_once __DIR__ . '/../controllers/AuthController.php';

// Start session for rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$authController = new AuthController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get the action from the global $action variable set in index.php
global $action;
$route = '/' . $action;

switch ($method) {
    case 'POST':
        switch ($route) {
            case '/register':
                $authController->register();
                break;
            case '/login':
                $authController->login();
                break;
            case '/refresh':
                $authController->refresh();
                break;
            case '/logout':
                $authController->logout();
                break;
            case '/forgot-password':
                $authController->forgotPassword();
                break;
            case '/reset-password':
                $authController->resetPassword();
                break;
            case '/verify-email':
                $authController->verifyEmail();
                break;
            case '/resend-verification':
                $authController->resendVerification();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Route not found']);
                break;
        }
        break;
    
    case 'GET':
        switch ($route) {
            case '/me':
                $authController->me();
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