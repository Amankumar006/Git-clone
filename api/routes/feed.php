<?php

require_once __DIR__ . '/../controllers/FeedController.php';

$feedController = new FeedController();

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove the /api prefix if present
$path = preg_replace('#^/api#', '', $path);

switch ($method) {
    case 'GET':
        if ($path === '/feed' || $path === '/feed/') {
            // Homepage feed
            $feedController->getHomepageFeed();
        } elseif ($path === '/feed/trending') {
            // Trending articles
            $feedController->getTrending();
        } elseif ($path === '/feed/popular') {
            // Popular articles
            $feedController->getPopular();
        } elseif ($path === '/feed/latest') {
            // Latest articles
            $feedController->getLatest();
        } elseif ($path === '/feed/recommendations') {
            // Personalized recommendations
            $feedController->getRecommendations();
        } elseif ($path === '/feed/following') {
            // Following feed
            $feedController->getFollowingFeed();
        } elseif ($path === '/feed/filtered') {
            // Filtered feed
            $feedController->getFilteredFeed();
        } elseif ($path === '/feed/stats') {
            // Feed statistics
            $feedController->getFeedStats();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Feed endpoint not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}