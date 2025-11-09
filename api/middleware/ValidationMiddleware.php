<?php
/**
 * Validation Middleware
 * Handles request validation and sanitization
 */

require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/ErrorHandler.php';

class ValidationMiddleware {
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            // Remove null bytes
            $data = str_replace(chr(0), '', $data);
            
            // Trim whitespace
            $data = trim($data);
            
            // Convert special characters to HTML entities
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validate JSON input
     */
    public static function validateJson() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                ErrorHandler::validationError([], 'Invalid JSON format');
            }
            
            return $data;
        }
        
        return $_POST;
    }
    
    /**
     * Validate request method
     */
    public static function validateMethod($allowedMethods) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowedMethods));
            ErrorHandler::validationError([], 'Method not allowed');
        }
    }
    
    /**
     * Validate content type
     */
    public static function validateContentType($allowedTypes) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        $isValid = false;
        foreach ($allowedTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                $isValid = true;
                break;
            }
        }
        
        if (!$isValid) {
            ErrorHandler::validationError([], 'Invalid content type');
        }
    }
    
    /**
     * Enhanced input sanitization
     */
    public static function sanitizeInputAdvanced($data, $options = []) {
        $allowHtml = $options['allow_html'] ?? false;
        $maxLength = $options['max_length'] ?? null;
        
        if (is_array($data)) {
            return array_map(function($item) use ($options) {
                return self::sanitizeInputAdvanced($item, $options);
            }, $data);
        }
        
        if (is_string($data)) {
            // Remove null bytes and control characters
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            
            // Trim whitespace
            $data = trim($data);
            
            // Apply length limit if specified
            if ($maxLength && strlen($data) > $maxLength) {
                $data = substr($data, 0, $maxLength);
            }
            
            // Handle HTML based on options
            if (!$allowHtml) {
                $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            } else {
                // Allow only safe HTML tags
                $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
                $data = strip_tags($data, $allowedTags);
            }
        }
        
        return $data;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $options = []) {
        $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
        $allowedTypes = $options['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = $options['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            ErrorHandler::validationError([], 'No valid file uploaded');
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            ErrorHandler::validationError([], 'File size exceeds maximum allowed size');
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            ErrorHandler::validationError([], 'File type not allowed');
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            ErrorHandler::validationError([], 'File extension not allowed');
        }
        
        // Additional security checks for images
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                ErrorHandler::validationError([], 'Invalid image file');
            }
        }
        
        return true;
    }
    
    /**
     * Validate API key or token in header
     */
    public static function validateApiKey($requiredKey = null) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_API_KEY'] ?? '';
        
        if (empty($apiKey)) {
            ErrorHandler::validationError([], 'API key required');
        }
        
        if ($requiredKey && $apiKey !== $requiredKey) {
            ErrorHandler::validationError([], 'Invalid API key');
        }
        
        return $apiKey;
    }
    
    /**
     * Validate request origin for CSRF protection
     */
    public static function validateOrigin($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        if (empty($allowedOrigins)) {
            return true; // Skip validation if no origins specified
        }
        
        if (empty($origin)) {
            ErrorHandler::validationError([], 'Origin header required');
        }
        
        $originHost = parse_url($origin, PHP_URL_HOST);
        $allowed = false;
        
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($originHost === $allowedOrigin || 
                (strpos($allowedOrigin, '*.') === 0 && 
                 str_ends_with($originHost, substr($allowedOrigin, 1)))) {
                $allowed = true;
                break;
            }
        }
        
        if (!$allowed) {
            ErrorHandler::validationError([], 'Origin not allowed');
        }
        
        return true;
    }
    
    /**
     * Clean up old rate limit files
     */
    private static function cleanupRateLimitFiles() {
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/rate_limit_*');
        $currentWindow = floor(time() / RATE_LIMIT_WINDOW) * RATE_LIMIT_WINDOW;
        
        foreach ($files as $file) {
            $parts = explode('_', basename($file));
            if (count($parts) >= 3) {
                $fileWindow = intval(end($parts));
                if ($fileWindow < $currentWindow - RATE_LIMIT_WINDOW) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * CSRF token validation
     */
    public static function validateCsrfToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'HEAD') {
            return; // Skip CSRF for safe methods
        }
        
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
            ErrorHandler::validationError([], 'CSRF token mismatch');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}