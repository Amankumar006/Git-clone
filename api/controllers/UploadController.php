<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class UploadController extends BaseController {
    private $fileUpload;
    private $authMiddleware;

    public function __construct() {
        parent::__construct();
        $this->fileUpload = new FileUpload();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    /**
     * Get token from Authorization header
     */
    private function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Upload image for articles
     */
    public function uploadImage() {
        try {
            // Check if we're in development mode
            $isDevelopment = !defined('APP_ENV') || APP_ENV !== 'production';
            
            $user = null;
            if ($isDevelopment) {
                // Development: check for token but don't require it
                $token = $this->getTokenFromHeader();
                if ($token) {
                    try {
                        $user = $this->authMiddleware->authenticate();
                    } catch (Exception $e) {
                        // Token exists but is invalid, continue without auth
                        error_log('Development mode: Invalid token, continuing without auth');
                        $user = null;
                    }
                } else {
                    // No token provided, continue without auth in development
                    error_log('Development mode: No token provided, continuing without auth');
                    $user = null;
                }
            } else {
                // Production: require authentication
                $user = $this->authMiddleware->authenticate();
                if (!$user) {
                    $this->sendError('Authentication required', 401);
                    return;
                }
            }

            if (!isset($_FILES['image'])) {
                $this->sendError('No image file provided', 400);
                return;
            }

            $file = $_FILES['image'];
            
            // Validate file
            $validation = $this->fileUpload->validateImage($file);
            if (!$validation['valid']) {
                $this->sendError($validation['message'], 400);
                return;
            }

            // Upload file
            $result = $this->fileUpload->uploadImage($file, 'articles/');
            
            if ($result['success']) {
                $this->sendResponse([
                    'url' => $result['url'],
                    'filename' => $result['filename'],
                    'size' => $result['size']
                ], 'Image uploaded successfully');
            } else {
                $this->sendError($result['message'], 500);
            }

        } catch (Exception $e) {
            $this->sendError('Upload failed', 500);
        }
    }

    /**
     * Upload featured image for articles
     */
    public function uploadFeaturedImage() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            if (!isset($_FILES['featured_image'])) {
                $this->sendError('No featured image file provided', 400);
                return;
            }

            $file = $_FILES['featured_image'];
            
            // Validate file
            $validation = $this->fileUpload->validateImage($file);
            if (!$validation['valid']) {
                $this->sendError($validation['message'], 400);
                return;
            }

            // Upload file with specific dimensions for featured images
            $result = $this->fileUpload->uploadImage($file, 'featured/', [
                'max_width' => 1200,
                'max_height' => 630,
                'quality' => 85
            ]);
            
            if ($result['success']) {
                $this->sendResponse([
                    'url' => $result['url'],
                    'filename' => $result['filename'],
                    'size' => $result['size'],
                    'dimensions' => $result['dimensions'] ?? null
                ], 'Featured image uploaded successfully');
            } else {
                $this->sendError($result['message'], 500);
            }

        } catch (Exception $e) {
            $this->sendError('Upload failed', 500);
        }
    }

    /**
     * Delete uploaded image
     */
    public function deleteImage() {
        try {
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                $this->sendError('Authentication required', 401);
                return;
            }

            $data = $this->getJsonInput();
            
            if (empty($data['filename'])) {
                $this->sendError('Filename is required', 400);
                return;
            }

            $result = $this->fileUpload->deleteFile($data['filename']);
            
            if ($result) {
                $this->sendResponse(null, 'Image deleted successfully');
            } else {
                $this->sendError('Failed to delete image', 500);
            }

        } catch (Exception $e) {
            $this->sendError('Delete failed', 500);
        }
    }
}