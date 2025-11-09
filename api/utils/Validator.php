<?php
/**
 * Input Validation Helper Class
 */

class Validator {
    
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' is required';
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? 'Invalid email format';
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = $message ?? ucfirst($field) . " must be at least {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = $message ?? ucfirst($field) . " must not exceed {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate field matches another field
     */
    public function matches($field, $matchField, $message = null) {
        if (isset($this->data[$field]) && isset($this->data[$matchField]) && 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must match ' . $matchField;
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be numeric';
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be an integer';
        }
        return $this;
    }
    
    /**
     * Validate value is in array of allowed values
     */
    public function in($field, $allowedValues, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowedValues)) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be one of: ' . implode(', ', $allowedValues);
        }
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?? 'Invalid URL format';
        }
        return $this;
    }
    
    /**
     * Custom validation with callback
     */
    public function custom($field, $callback, $message = null) {
        if (isset($this->data[$field]) && !$callback($this->data[$field])) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' is invalid';
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     */
    public function getFirstError($field) {
        return $this->errors[$field][0] ?? null;
    }
    

    
    /**
     * Static password validation method
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } else {
            if (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors['password'] = 'Password must contain at least one uppercase letter';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors['password'] = 'Password must contain at least one lowercase letter';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors['password'] = 'Password must contain at least one number';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors['password'] = 'Password must contain at least one special character';
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate unique field in database
     */
    public function unique($field, $table, $column = null, $excludeId = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?? $field;
        $value = $this->data[$field];
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $this->errors[$field][] = ucfirst($field) . ' already exists';
            }
        } catch (Exception $e) {
            error_log("Database error in unique validation: " . $e->getMessage());
            $this->errors[$field][] = 'Unable to validate uniqueness';
        }
        
        return $this;
    }
    
    /**
     * Validate field against regex pattern
     */
    public function regex($field, $pattern, $message = null) {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' format is invalid';
        }
        return $this;
    }
    
    /**
     * Validate username format
     */
    public function username($field, $message = null) {
        if (isset($this->data[$field])) {
            $username = $this->data[$field];
            
            // Check length
            if (strlen($username) < 3 || strlen($username) > 50) {
                $this->errors[$field][] = 'Username must be between 3 and 50 characters';
            }
            
            // Check format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                $this->errors[$field][] = 'Username can only contain letters, numbers, underscores, and hyphens';
            }
            
            // Check reserved words
            $reserved = ['admin', 'api', 'www', 'mail', 'ftp', 'localhost', 'root', 'test', 'user', 'guest'];
            if (in_array(strtolower($username), $reserved)) {
                $this->errors[$field][] = 'Username is reserved and cannot be used';
            }
        }
        return $this;
    }
    
    /**
     * Validate bio content
     */
    public function bio($field, $message = null) {
        if (isset($this->data[$field])) {
            $bio = trim($this->data[$field]);
            
            if (strlen($bio) > 500) {
                $this->errors[$field][] = 'Bio must be less than 500 characters';
            }
            
            // Check for potentially harmful content
            if (preg_match('/<script|javascript:|data:/i', $bio)) {
                $this->errors[$field][] = 'Bio contains invalid content';
            }
        }
        return $this;
    }
    
    /**
     * Validate social media URLs
     */
    public function socialLinks($field, $message = null) {
        if (isset($this->data[$field])) {
            $links = $this->data[$field];
            
            if (is_string($links)) {
                $links = json_decode($links, true);
            }
            
            if (is_array($links)) {
                $allowedDomains = [
                    'twitter.com', 'x.com', 'linkedin.com', 'github.com', 
                    'instagram.com', 'facebook.com', 'youtube.com', 'medium.com'
                ];
                
                foreach ($links as $platform => $url) {
                    if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                        $this->errors[$field][] = "Invalid URL for {$platform}";
                    }
                    
                    if (!empty($url)) {
                        $domain = parse_url($url, PHP_URL_HOST);
                        $domain = preg_replace('/^www\./', '', $domain);
                        
                        if (!in_array($domain, $allowedDomains)) {
                            $this->errors[$field][] = "Unsupported platform: {$platform}";
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    /**
     * Enhanced password validation with detailed feedback
     */
    public function password($field, $message = null) {
        if (isset($this->data[$field])) {
            $validation = self::validatePasswordDetailed($this->data[$field]);
            if (!$validation['valid']) {
                $this->errors[$field] = array_merge($this->errors[$field] ?? [], $validation['errors']);
            }
        }
        return $this;
    }
    
    /**
     * Detailed password validation with specific error messages
     */
    public static function validatePasswordDetailed($password) {
        $errors = [];
        
        if (empty($password)) {
            $errors[] = 'Password is required';
            return ['valid' => false, 'errors' => $errors];
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check for common weak passwords
        $commonPasswords = [
            'password', '12345678', 'qwerty123', 'abc123456', 'password123',
            'admin123', 'letmein123', 'welcome123', 'monkey123', '123456789'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password is too common and easily guessable';
        }
        
        // Check for sequential characters
        if (preg_match('/(?:abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz|123|234|345|456|567|678|789)/i', $password)) {
            $errors[] = 'Password should not contain sequential characters';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Static method for quick validation
     */
    public static function make($data) {
        return new self($data);
    }
    
    /**
     * Validate data against rules (Laravel-style validation)
     */
    public function validate($data, $rules) {
        $this->data = $data;
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return [
            'valid' => $this->passes(),
            'errors' => $this->getErrors()
        ];
    }
    
    /**
     * Apply a single validation rule
     */
    private function applyRule($field, $rule) {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                $this->required($field);
                break;
            case 'email':
                $this->email($field);
                break;
            case 'integer':
                $this->integer($field);
                break;
            case 'numeric':
                $this->numeric($field);
                break;
            case 'string':
                // String validation - just check if it's not numeric when it should be string
                if (isset($this->data[$field]) && is_numeric($this->data[$field]) && !is_string($this->data[$field])) {
                    $this->errors[$field][] = ucfirst($field) . ' must be a string';
                }
                break;
            case 'min':
                if ($parameter) {
                    $this->minLength($field, (int)$parameter);
                }
                break;
            case 'max':
                if ($parameter) {
                    $this->maxLength($field, (int)$parameter);
                }
                break;
            case 'url':
                $this->url($field);
                break;
        }
    }
}