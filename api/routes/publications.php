<?php

require_once __DIR__ . '/../controllers/PublicationController.php';

$controller = new PublicationController();

// Get the request method and extract action from URI
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

// Remove leading slash and split into segments
$uri = ltrim($uri, '/');
$segments = explode('/', $uri);
$action = $segments[1] ?? '';

// Route the request
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'show':
                $controller->show();
                break;
            case 'my':
                $controller->getUserPublications();
                break;
            case 'articles':
                $controller->getArticles();
                break;
            case 'search':
                $controller->search();
                break;
            case 'invitations':
                $controller->getInvitations();
                break;
            case 'followed':
                $controller->getFollowedPublications();
                break;
            case 'followed-articles':
                $controller->getFollowedPublicationsArticles();
                break;
            case 'filtered-articles':
                $controller->getFilteredArticles();
                break;
            case 'workflow-status':
                $controller->getWorkflowStatus();
                break;
            case '':
                // List all publications (public)
                $controller->search();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'create':
                $controller->create();
                break;
            case 'invite':
                $controller->inviteMember();
                break;
            case 'invite-bulk':
                $controller->inviteBulkMembers();
                break;
            case 'remove-member':
                $controller->removeMember();
                break;
            case 'update-role':
                $controller->updateMemberRole();
                break;
            case 'accept-invitation':
                $controller->acceptInvitation();
                break;
            case 'decline-invitation':
                $controller->declineInvitation();
                break;
            case 'follow':
                $controller->follow();
                break;
            case 'unfollow':
                $controller->unfollow();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'PUT':
        switch ($action) {
            case 'update':
                $controller->update();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'DELETE':
        switch ($action) {
            case 'delete':
                $controller->delete();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}