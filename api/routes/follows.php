<?php

require_once __DIR__ . '/../controllers/FollowController.php';

$followController = new FollowController();

// Handle follow routes
switch ($method) {
    case 'POST':
        if ($endpoint === 'follows/follow') {
            $followController->followUser();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if ($endpoint === 'follows/unfollow') {
            $followController->unfollowUser();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'GET':
        if (preg_match('/^follows\/followers\/(\d+)$/', $endpoint, $matches)) {
            $followController->getFollowers($matches[1]);
        } elseif (preg_match('/^follows\/following\/(\d+)$/', $endpoint, $matches)) {
            $followController->getFollowing($matches[1]);
        } elseif (preg_match('/^follows\/status\/(\d+)$/', $endpoint, $matches)) {
            $followController->getFollowStatus($matches[1]);
        } elseif ($endpoint === 'follows/feed') {
            $followController->getFollowingFeed();
        } elseif ($endpoint === 'follows/suggestions') {
            $followController->getSuggestedFollows();
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