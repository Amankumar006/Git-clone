<?php
/**
 * JWT Helper Class
 * Handles JWT token generation, validation, and management
 */

class JWTHelper {
    private static $secret_key;
    private static $algorithm = 'HS256';
    private static $access_token_expiry = 3600; // 1 hour
    private static $refresh_token_expiry = 604800; // 7 days
    
    public function __construct() {
        self::init();
    }
    
    /**
     * Initialize JWT configuration
     */
    private static function init() {
        if (!self::$secret_key) {
            // Load config if not already loaded
            if (!defined('JWT_SECRET')) {
                require_once __DIR__ . '/../config/config.php';
            }
            self::$secret_key = JWT_SECRET;
        }
    }
    
    /**
     * Generate access token
     */
    public static function generateAccessToken($userId, $email, $username) {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + self::$access_token_expiry,
            'user_id' => $userId,
            'email' => $email,
            'username' => $username,
            'type' => 'access'
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($userId) {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + self::$refresh_token_expiry,
            'user_id' => $userId,
            'type' => 'refresh'
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Validate and decode token
     */
    public static function validateToken($token) {
        try {
            $decoded = self::decode($token);
            
            // Check if token is expired
            if ($decoded['exp'] < time()) {
                return ['valid' => false, 'error' => 'Token expired'];
            }
            
            return ['valid' => true, 'payload' => $decoded];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Invalid token: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract user ID from token
     */
    public static function getUserIdFromToken($token) {
        $validation = self::validateToken($token);
        if ($validation['valid']) {
            return $validation['payload']['user_id'];
        }
        return null;
    }
    
    /**
     * Check if token is access token
     */
    public static function isAccessToken($token) {
        $validation = self::validateToken($token);
        if ($validation['valid']) {
            return $validation['payload']['type'] === 'access';
        }
        return false;
    }
    
    /**
     * Check if token is refresh token
     */
    public static function isRefreshToken($token) {
        $validation = self::validateToken($token);
        if ($validation['valid']) {
            return $validation['payload']['type'] === 'refresh';
        }
        return false;
    }
    
    /**
     * Encode JWT token
     */
    private static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::getSecretKey(), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Decode JWT token
     */
    private static function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Decode header and payload
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Header)), true);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        if (!$header || !$payload) {
            throw new Exception('Invalid token data');
        }
        
        // Verify signature
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Signature));
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::getSecretKey(), true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        return $payload;
    }
    
    /**
     * Get secret key
     */
    private static function getSecretKey() {
        self::init();
        return self::$secret_key;
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        // Handle case when getallheaders() is not available (CLI mode)
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        } else {
            $headers = getallheaders();
        }
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Generate tokens for user
     */
    public static function generateTokens($user) {
        $accessToken = self::generateAccessToken($user['id'], $user['email'], $user['username']);
        $refreshToken = self::generateRefreshToken($user['id']);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::$access_token_expiry
        ];
    }
    
    /**
     * Refresh access token using refresh token
     */
    public static function refreshAccessToken($refreshToken, $userModel) {
        $validation = self::validateToken($refreshToken);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        if (!self::isRefreshToken($refreshToken)) {
            return ['success' => false, 'error' => 'Invalid refresh token'];
        }
        
        $userId = $validation['payload']['user_id'];
        $user = $userModel->findById($userId);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Generate new access token
        $accessToken = self::generateAccessToken($user['id'], $user['email'], $user['username']);
        
        return [
            'success' => true,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::$access_token_expiry
        ];
    }
    
    /**
     * Encode custom token with specific payload
     */
    public static function encodeCustomToken($payload) {
        return self::encode($payload);
    }
    
    /**
     * Generate email verification token
     */
    public static function generateEmailVerificationToken($userId) {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'user_id' => $userId,
            'type' => 'email_verification'
        ];
        
        return self::encode($payload);
    }
}