<?php
/**
 * CORS Configuration
 */

class CorsHandler {
    
    /**
     * Set CORS headers
     */
    public static function setCorsHeaders() {
        // Allow requests from frontend URL
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            FRONTEND_URL
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // For development, allow localhost origins
            if (strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
                header("Access-Control-Allow-Origin: $origin");
            }
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // 24 hours
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        if (!APP_DEBUG) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}