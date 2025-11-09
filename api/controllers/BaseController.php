<?php
/**
 * Base Controller Class
 * Provides common functionality for all controllers
 */

require_once __DIR__ . '/../utils/ErrorHandler.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

abstract class BaseController {
    
    protected $requestMethod;
    protected $requestData;
    protected $queryParams;
    protected $db;
    
    public function __construct() {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->queryParams = $_GET;
        $this->db = Database::getInstance()->getConnection();
        
        // Get request body data
        $this->requestData = $this->getRequestData();
    }
    
    /**
     * Get request data from body
     */
    protected function getRequestData() {
        $input = file_get_contents('php://input');
        
        // Try to decode JSON
        $data = json_decode($input, true);
        
        // If not JSON, try form data
        if ($data === null) {
            $data = $_POST;
        }
        
        return $data ?? [];
    }
    
    /**
     * Validate request data
     */
    protected function validate($rules) {
        $validator = new Validator($this->requestData);
        
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule => $params) {
                if (is_numeric($rule)) {
                    // Simple rule without parameters
                    $rule = $params;
                    $params = [];
                }
                
                switch ($rule) {
                    case 'required':
                        $validator->required($field, $params['message'] ?? null);
                        break;
                    case 'email':
                        $validator->email($field, $params['message'] ?? null);
                        break;
                    case 'min':
                        $validator->minLength($field, $params['length'], $params['message'] ?? null);
                        break;
                    case 'max':
                        $validator->maxLength($field, $params['length'], $params['message'] ?? null);
                        break;
                    case 'matches':
                        $validator->matches($field, $params['field'], $params['message'] ?? null);
                        break;
                    case 'numeric':
                        $validator->numeric($field, $params['message'] ?? null);
                        break;
                    case 'integer':
                        $validator->integer($field, $params['message'] ?? null);
                        break;
                    case 'in':
                        $validator->in($field, $params['values'], $params['message'] ?? null);
                        break;
                    case 'url':
                        $validator->url($field, $params['message'] ?? null);
                        break;
                }
            }
        }
        
        if ($validator->fails()) {
            ErrorHandler::validationError($validator->getErrors());
        }
        
        return $this->requestData;
    }
    
    /**
     * Get authenticated user
     */
    protected function getAuthenticatedUser() {
        return AuthMiddleware::authenticate();
    }
    
    /**
     * Get optional authenticated user
     */
    protected function getOptionalAuthenticatedUser() {
        return AuthMiddleware::optionalAuth();
    }
    
    /**
     * Check authorization
     */
    protected function authorize($requiredRole = null, $resourceOwnerId = null) {
        return AuthMiddleware::authorize($requiredRole, null, $resourceOwnerId);
    }
    
    /**
     * Get pagination parameters
     */
    protected function getPaginationParams() {
        $page = max(1, intval($this->queryParams['page'] ?? 1));
        $limit = min(MAX_PAGE_SIZE, max(1, intval($this->queryParams['limit'] ?? DEFAULT_PAGE_SIZE)));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Create pagination response
     */
    protected function createPaginationResponse($totalItems, $currentPage, $limit) {
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'per_page' => $limit,
            'has_next' => $currentPage < $totalPages,
            'has_prev' => $currentPage > 1
        ];
    }
    
    /**
     * Send success response
     */
    protected function sendResponse($data = null, $message = 'Success', $code = 200, $pagination = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }
        
        echo json_encode($response);
        exit();
    }
    
    /**
     * Send error response
     */
    protected function sendError($message, $code = 400, $details = []) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $this->getErrorCode($code),
                'message' => $message
            ]
        ];
        
        if (!empty($details)) {
            $response['error']['details'] = $details;
        }
        
        echo json_encode($response);
        exit();
    }
    
    /**
     * Get JSON input from request body
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }
        
        return $data ?? [];
    }
    
    /**
     * Get error code based on HTTP status
     */
    private function getErrorCode($httpCode) {
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_ERROR'
        ];
        
        return $codes[$httpCode] ?? 'UNKNOWN_ERROR';
    }
    
    /**
     * Send success response (alias)
     */
    protected function success($data = null, $message = 'Success', $pagination = null) {
        $this->sendResponse($data, $message, 200, $pagination);
    }
    
    /**
     * Send error response (alias)
     */
    protected function error($message, $code = 400) {
        $this->sendError($message, $code);
    }
}