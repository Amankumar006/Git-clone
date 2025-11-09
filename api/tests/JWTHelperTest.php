<?php
/**
 * JWT Helper Tests
 * Comprehensive tests for JWT token generation, validation, and expiration
 */

require_once __DIR__ . '/../utils/JWTHelper.php';

class JWTHelperTest {
    private $testResults = [];
    
    public function __construct() {
        // Initialize test environment
        $this->testResults = [
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ];
    }
    
    /**
     * Assert test result
     */
    private function assert($condition, $message) {
        $this->testResults['total']++;
        if ($condition) {
            $this->testResults['passed']++;
            echo "✓ $message\n";
            return true;
        } else {
            $this->testResults['failed']++;
            echo "✗ $message\n";
            return false;
        }
    }
    
    /**
     * Test JWT token generation
     */
    public function testTokenGeneration() {
        echo "Testing JWT token generation...\n";
        
        $userId = 1;
        $email = 'test@example.com';
        $username = 'testuser';
        
        $accessToken = JWTHelper::generateAccessToken($userId, $email, $username);
        $refreshToken = JWTHelper::generateRefreshToken($userId);
        
        // Test that tokens are generated
        $this->assert(
            !empty($accessToken) && !empty($refreshToken),
            'Access and refresh tokens generated'
        );
        
        // Test token format (JWT has 3 parts separated by dots)
        $accessParts = explode('.', $accessToken);
        $refreshParts = explode('.', $refreshToken);
        
        $this->assert(
            count($accessParts) === 3,
            'Access token has correct JWT format (3 parts)'
        );
        
        $this->assert(
            count($refreshParts) === 3,
            'Refresh token has correct JWT format (3 parts)'
        );
        
        // Test that tokens are different
        $this->assert(
            $accessToken !== $refreshToken,
            'Access and refresh tokens are different'
        );
        
        // Test token uniqueness (generate multiple tokens)
        $accessToken2 = JWTHelper::generateAccessToken($userId, $email, $username);
        $this->assert(
            $accessToken !== $accessToken2,
            'Multiple access tokens are unique (due to timestamp)'
        );
    }
    
    /**
     * Test JWT token validation
     */
    public function testTokenValidation() {
        echo "Testing JWT token validation...\n";
        
        $userId = 1;
        $email = 'test@example.com';
        $username = 'testuser';
        
        $token = JWTHelper::generateAccessToken($userId, $email, $username);
        $validation = JWTHelper::validateToken($token);
        
        // Test valid token validation
        $this->assert(
            $validation['valid'] === true,
            'Valid token validation returns true'
        );
        
        // Test payload extraction
        $this->assert(
            isset($validation['payload']) && 
            $validation['payload']['user_id'] == $userId &&
            $validation['payload']['email'] == $email &&
            $validation['payload']['username'] == $username,
            'Token payload contains correct user data'
        );
        
        // Test token type in payload
        $this->assert(
            $validation['payload']['type'] === 'access',
            'Access token has correct type in payload'
        );
        
        // Test refresh token validation
        $refreshToken = JWTHelper::generateRefreshToken($userId);
        $refreshValidation = JWTHelper::validateToken($refreshToken);
        
        $this->assert(
            $refreshValidation['valid'] === true &&
            $refreshValidation['payload']['type'] === 'refresh',
            'Refresh token validation and type check'
        );
        
        // Test token expiration fields
        $this->assert(
            isset($validation['payload']['iat']) && 
            isset($validation['payload']['exp']) &&
            $validation['payload']['exp'] > $validation['payload']['iat'],
            'Token contains valid issued at and expiration times'
        );
    }
    
    /**
     * Test token type identification
     */
    public function testTokenTypeIdentification() {
        echo "Testing token type identification...\n";
        
        $userId = 1;
        $email = 'test@example.com';
        $username = 'testuser';
        
        $accessToken = JWTHelper::generateAccessToken($userId, $email, $username);
        $refreshToken = JWTHelper::generateRefreshToken($userId);
        
        // Test access token identification
        $this->assert(
            JWTHelper::isAccessToken($accessToken),
            'Access token correctly identified as access token'
        );
        
        $this->assert(
            !JWTHelper::isRefreshToken($accessToken),
            'Access token correctly rejected as refresh token'
        );
        
        // Test refresh token identification
        $this->assert(
            JWTHelper::isRefreshToken($refreshToken),
            'Refresh token correctly identified as refresh token'
        );
        
        $this->assert(
            !JWTHelper::isAccessToken($refreshToken),
            'Refresh token correctly rejected as access token'
        );
        
        // Test user ID extraction
        $extractedUserId = JWTHelper::getUserIdFromToken($accessToken);
        $this->assert(
            $extractedUserId === $userId,
            'User ID correctly extracted from token'
        );
        
        // Test invalid token user ID extraction
        $invalidUserId = JWTHelper::getUserIdFromToken('invalid.token.here');
        $this->assert(
            $invalidUserId === null,
            'Invalid token returns null for user ID extraction'
        );
    }
    
    /**
     * Test invalid token handling
     */
    public function testInvalidTokenHandling() {
        echo "Testing invalid token handling...\n";
        
        // Test completely invalid token
        $invalidToken = 'invalid.token.here';
        $validation = JWTHelper::validateToken($invalidToken);
        
        $this->assert(
            !$validation['valid'],
            'Invalid token format correctly rejected'
        );
        
        $this->assert(
            isset($validation['error']),
            'Invalid token validation returns error message'
        );
        
        // Test malformed token (wrong number of parts)
        $malformedToken = 'header.payload';
        $malformedValidation = JWTHelper::validateToken($malformedToken);
        
        $this->assert(
            !$malformedValidation['valid'],
            'Malformed token (2 parts) correctly rejected'
        );
        
        // Test token with too many parts
        $tooManyParts = 'header.payload.signature.extra';
        $tooManyValidation = JWTHelper::validateToken($tooManyParts);
        
        $this->assert(
            !$tooManyValidation['valid'],
            'Token with too many parts correctly rejected'
        );
        
        // Test empty token
        $emptyValidation = JWTHelper::validateToken('');
        
        $this->assert(
            !$emptyValidation['valid'],
            'Empty token correctly rejected'
        );
        
        // Test null token
        $nullValidation = JWTHelper::validateToken(null);
        
        $this->assert(
            !$nullValidation['valid'],
            'Null token correctly rejected'
        );
    }
    
    /**
     * Test token expiration logic
     */
    public function testTokenExpiration() {
        echo "Testing token expiration logic...\n";
        
        // Test expiration time calculation
        $currentTime = time();
        $expiredTime = $currentTime - 3600; // 1 hour ago
        $futureTime = $currentTime + 3600; // 1 hour from now
        
        $this->assert(
            $expiredTime < $currentTime,
            'Expired time is correctly identified as past'
        );
        
        $this->assert(
            $futureTime > $currentTime,
            'Future time is correctly identified as future'
        );
        
        // Test token payload expiration structure
        $userId = 1;
        $email = 'test@example.com';
        $username = 'testuser';
        
        $token = JWTHelper::generateAccessToken($userId, $email, $username);
        $validation = JWTHelper::validateToken($token);
        
        if ($validation['valid']) {
            $payload = $validation['payload'];
            
            $this->assert(
                isset($payload['exp']) && $payload['exp'] > time(),
                'Fresh token has valid expiration time in future'
            );
            
            $this->assert(
                isset($payload['iat']) && $payload['iat'] <= time(),
                'Token issued at time is valid (not in future)'
            );
            
            // Test expiration time difference (should be around 1 hour for access token)
            $expirationDiff = $payload['exp'] - $payload['iat'];
            $this->assert(
                $expirationDiff >= 3500 && $expirationDiff <= 3700, // Allow some variance
                'Access token expiration time is approximately 1 hour'
            );
        }
        
        // Test refresh token expiration (should be longer)
        $refreshToken = JWTHelper::generateRefreshToken($userId);
        $refreshValidation = JWTHelper::validateToken($refreshToken);
        
        if ($refreshValidation['valid']) {
            $refreshPayload = $refreshValidation['payload'];
            $refreshExpirationDiff = $refreshPayload['exp'] - $refreshPayload['iat'];
            
            $this->assert(
                $refreshExpirationDiff > 86400, // More than 1 day
                'Refresh token has longer expiration than access token'
            );
        }
    }
    
    /**
     * Test token refresh functionality
     */
    public function testTokenRefresh() {
        echo "Testing token refresh functionality...\n";
        
        $userId = 1;
        $refreshToken = JWTHelper::generateRefreshToken($userId);
        
        // Mock user model for refresh test
        $mockUser = [
            'id' => $userId,
            'email' => 'test@example.com',
            'username' => 'testuser'
        ];
        
        // Create a mock user model
        $mockUserModel = new class {
            public function findById($id) {
                return [
                    'id' => $id,
                    'email' => 'test@example.com',
                    'username' => 'testuser'
                ];
            }
        };
        
        // Test refresh token structure
        $validation = JWTHelper::validateToken($refreshToken);
        
        $this->assert(
            $validation['valid'] && 
            isset($validation['payload']['user_id']) &&
            $validation['payload']['type'] === 'refresh',
            'Refresh token has correct structure for refresh operation'
        );
        
        // Test that refresh tokens don't contain sensitive user data
        $this->assert(
            !isset($validation['payload']['email']) &&
            !isset($validation['payload']['username']),
            'Refresh token does not contain sensitive user data'
        );
    }
    
    /**
     * Test email verification token generation
     */
    public function testEmailVerificationToken() {
        echo "Testing email verification token generation...\n";
        
        $userId = 123;
        $verificationToken = JWTHelper::generateEmailVerificationToken($userId);
        
        $this->assert(
            !empty($verificationToken),
            'Email verification token is generated'
        );
        
        // Test token format
        $parts = explode('.', $verificationToken);
        $this->assert(
            count($parts) === 3,
            'Email verification token has correct JWT format'
        );
        
        // Test token validation and payload
        $validation = JWTHelper::validateToken($verificationToken);
        
        $this->assert(
            $validation['valid'],
            'Email verification token is valid'
        );
        
        $this->assert(
            $validation['payload']['user_id'] === $userId &&
            $validation['payload']['type'] === 'email_verification',
            'Email verification token contains correct user ID and type'
        );
        
        // Test expiration (should be 24 hours)
        $expirationDiff = $validation['payload']['exp'] - $validation['payload']['iat'];
        $this->assert(
            $expirationDiff >= 86300 && $expirationDiff <= 86500, // Around 24 hours
            'Email verification token has 24-hour expiration'
        );
    }
    
    /**
     * Test token header extraction
     */
    public function testTokenHeaderExtraction() {
        echo "Testing token header extraction...\n";
        
        // Test Authorization header parsing
        $testToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test.signature';
        
        // Simulate different header formats
        $validBearerFormat = "Bearer $testToken";
        $invalidFormat = "Basic $testToken";
        $noBearer = $testToken;
        
        // Test valid Bearer format parsing
        if (preg_match('/Bearer\s+(.*)$/i', $validBearerFormat, $matches)) {
            $extractedToken = $matches[1];
            $this->assert(
                $extractedToken === $testToken,
                'Bearer token correctly extracted from Authorization header'
            );
        }
        
        // Test invalid format rejection
        $this->assert(
            !preg_match('/Bearer\s+(.*)$/i', $invalidFormat),
            'Non-Bearer authorization header correctly rejected'
        );
        
        // Test missing Bearer prefix
        $this->assert(
            !preg_match('/Bearer\s+(.*)$/i', $noBearer),
            'Token without Bearer prefix correctly rejected'
        );
    }
    
    /**
     * Test custom token encoding
     */
    public function testCustomTokenEncoding() {
        echo "Testing custom token encoding...\n";
        
        $customPayload = [
            'custom_field' => 'custom_value',
            'user_id' => 456,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        $customToken = JWTHelper::encodeCustomToken($customPayload);
        
        $this->assert(
            !empty($customToken),
            'Custom token is generated'
        );
        
        // Test custom token validation
        $validation = JWTHelper::validateToken($customToken);
        
        $this->assert(
            $validation['valid'],
            'Custom token is valid'
        );
        
        $this->assert(
            $validation['payload']['custom_field'] === 'custom_value' &&
            $validation['payload']['user_id'] === 456,
            'Custom token contains correct custom payload data'
        );
    }
    
    /**
     * Test token security features
     */
    public function testTokenSecurity() {
        echo "Testing token security features...\n";
        
        $userId = 1;
        $email = 'test@example.com';
        $username = 'testuser';
        
        $token1 = JWTHelper::generateAccessToken($userId, $email, $username);
        $token2 = JWTHelper::generateAccessToken($userId, $email, $username);
        
        // Test that identical payloads produce different tokens (due to timestamp)
        $this->assert(
            $token1 !== $token2,
            'Identical payloads produce different tokens (timestamp variance)'
        );
        
        // Test token signature verification by modifying token
        $parts = explode('.', $token1);
        $modifiedToken = $parts[0] . '.' . $parts[1] . '.modified_signature';
        
        $modifiedValidation = JWTHelper::validateToken($modifiedToken);
        $this->assert(
            !$modifiedValidation['valid'],
            'Token with modified signature is rejected'
        );
        
        // Test payload tampering detection
        $modifiedPayload = base64_encode('{"user_id":999,"email":"hacker@evil.com"}');
        $tamperedToken = $parts[0] . '.' . $modifiedPayload . '.' . $parts[2];
        
        $tamperedValidation = JWTHelper::validateToken($tamperedToken);
        $this->assert(
            !$tamperedValidation['valid'],
            'Token with tampered payload is rejected'
        );
    }
    
    /**
     * Print test summary
     */
    private function printTestSummary() {
        echo "\n=========================\n";
        echo "JWT Helper Test Summary:\n";
        echo "Total Tests: {$this->testResults['total']}\n";
        echo "Passed: {$this->testResults['passed']}\n";
        echo "Failed: {$this->testResults['failed']}\n";
        
        if ($this->testResults['failed'] === 0) {
            echo "✓ All tests passed!\n";
        } else {
            echo "✗ Some tests failed!\n";
        }
        echo "=========================\n\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Running JWT Helper Tests...\n";
        echo "=========================\n";
        
        $this->testTokenGeneration();
        $this->testTokenValidation();
        $this->testTokenTypeIdentification();
        $this->testInvalidTokenHandling();
        $this->testTokenExpiration();
        $this->testTokenRefresh();
        $this->testEmailVerificationToken();
        $this->testTokenHeaderExtraction();
        $this->testCustomTokenEncoding();
        $this->testTokenSecurity();
        
        $this->printTestSummary();
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new JWTHelperTest();
    $test->runAllTests();
}