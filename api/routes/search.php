<?php

require_once __DIR__ . '/../controllers/SearchController.php';

$searchController = new SearchController();

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove the /api prefix if present
$path = preg_replace('#^/api#', '', $path);

switch ($method) {
    case 'GET':
        if ($path === '/search') {
            // General search
            $searchController->search();
        } elseif ($path === '/search/articles') {
            // Search articles only
            $searchController->searchArticles();
        } elseif ($path === '/search/suggestions') {
            // Get search suggestions
            $searchController->getSuggestions();
        } elseif ($path === '/search/popular') {
            // Get popular searches
            $searchController->getPopularSearches();
        } elseif ($path === '/search/analytics') {
            // Get search analytics
            $searchController->getSearchAnalytics();
        } elseif ($path === '/search/saved') {
            // Get saved searches
            $searchController->getSavedSearches();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Search endpoint not found']);
        }
        break;

    case 'POST':
        if ($path === '/search/advanced') {
            // Advanced search
            $searchController->advancedSearch();
        } elseif ($path === '/search/save') {
            // Save search
            $searchController->saveSearch();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Search endpoint not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}