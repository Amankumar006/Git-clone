<?php

require_once __DIR__ . '/../controllers/ClapController.php';

$clapController = new ClapController();

// Handle clap routes
switch ($method) {
    case 'POST':
        if ($endpoint === 'claps/add') {
            $clapController->addClap();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if ($endpoint === 'claps/remove') {
            $clapController->removeClap();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'GET':
        if (preg_match('/^claps\/article\/(\d+)$/', $endpoint, $matches)) {
            $clapController->getArticleClaps($matches[1]);
        } elseif (preg_match('/^claps\/status\/(\d+)$/', $endpoint, $matches)) {
            $clapController->getClapStatus($matches[1]);
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