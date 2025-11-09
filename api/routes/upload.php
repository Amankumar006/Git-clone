<?php
/**
 * File upload routes
 */

require_once __DIR__ . '/../controllers/UploadController.php';

$uploadController = new UploadController();
$method = $_SERVER['REQUEST_METHOD'];

// Get URI segments
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Remove 'api' and 'upload' from path parts
if ($pathParts[0] === 'api') {
    array_shift($pathParts);
}
if ($pathParts[0] === 'upload') {
    array_shift($pathParts);
}

$uploadType = $pathParts[0] ?? '';

switch ($method) {
    case 'POST':
        switch ($uploadType) {
            case 'image':
                $uploadController->uploadImage();
                break;
                
            case 'featured-image':
                $uploadController->uploadFeaturedImage();
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Upload type not found'
                    ]
                ]);
        }
        break;
        
    case 'DELETE':
        if ($uploadType === 'image') {
            $uploadController->deleteImage();
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Upload type not found'
                ]
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Method not allowed'
            ]
        ]);
}
?>