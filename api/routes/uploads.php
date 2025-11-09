<?php
/**
 * File serving route for uploaded files
 */

// Get the file path from the URI
$filePath = implode('/', array_slice($segments, 1)); // Remove 'uploads' from path

if (empty($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Get the configured upload path
require_once __DIR__ . '/../config/config.php';

// Construct full file path using configured upload path
$uploadPath = rtrim(UPLOAD_PATH, '/') . '/';
$fullPath = $uploadPath . $filePath;

// Security check - ensure the path is within uploads directory
$realPath = realpath($fullPath);
$uploadsPath = realpath($uploadPath);

if (!$realPath || strpos($realPath, $uploadsPath) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Check if file exists
if (!file_exists($realPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Get file info
$fileInfo = pathinfo($realPath);
$extension = strtolower($fileInfo['extension'] ?? '');

// Set appropriate content type
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
    'txt' => 'text/plain',
];

$contentType = $contentTypes[$extension] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// For images, add additional headers
if (strpos($contentType, 'image/') === 0) {
    header('Accept-Ranges: bytes');
}

// Output the file
readfile($realPath);
exit;