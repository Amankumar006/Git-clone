<?php

require_once __DIR__ . '/../controllers/CommentController.php';

$commentController = new CommentController();

// Handle comment routes
switch ($method) {
    case 'POST':
        if ($endpoint === 'comments/create') {
            $commentController->createComment();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'PUT':
        if (preg_match('/^comments\/update\/(\d+)$/', $endpoint, $matches)) {
            $commentController->updateComment($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/^comments\/delete\/(\d+)$/', $endpoint, $matches)) {
            $commentController->deleteComment($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'GET':
        if (preg_match('/^comments\/article\/(\d+)$/', $endpoint, $matches)) {
            $commentController->getArticleComments($matches[1]);
        } elseif (preg_match('/^comments\/show\/(\d+)$/', $endpoint, $matches)) {
            $commentController->getComment($matches[1]);
        } elseif (preg_match('/^comments\/user\/(\d+)$/', $endpoint, $matches)) {
            $commentController->getUserComments($matches[1]);
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