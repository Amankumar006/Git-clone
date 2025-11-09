<?php
/**
 * Debug Routing Script
 * Shows how the routing is being parsed
 */

echo "🔍 Routing Debug Information\n";
echo "============================\n\n";

// Simulate the same logic as index.php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/api/claps/status/36', PHP_URL_PATH);

echo "Original URI: " . ($_SERVER['REQUEST_URI'] ?? '/api/claps/status/36') . "\n";
echo "Parsed Path: $uri\n";

// Remove API base path from URI
$basePath = '/medium-clone/api';
echo "Base Path: $basePath\n";

if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
    echo "After removing base path: $uri\n";
} else {
    echo "Base path not found in URI\n";
    
    // Try alternative base paths
    $altBasePath = '/api';
    if (strpos($uri, $altBasePath) === 0) {
        $uri = substr($uri, strlen($altBasePath));
        echo "After removing alternative base path '/api': $uri\n";
    }
}

// Remove leading slash
$uri = ltrim($uri, '/');
echo "After removing leading slash: '$uri'\n";

// Split URI into segments
$segments = explode('/', $uri);
$resource = $segments[0] ?? '';
$action = $segments[1] ?? '';
$id = $segments[2] ?? '';

echo "\nSegments:\n";
echo "- Resource: '$resource'\n";
echo "- Action: '$action'\n";
echo "- ID: '$id'\n";

echo "\nFull segments array:\n";
print_r($segments);

echo "\nEndpoint variable for claps route: '$uri'\n";