<?php
/**
 * Error Handler Class
 * Centralized error handling and logging
 */

class ErrorHandler {
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        // Log the error
        error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Set appropriate HTTP status code
        $statusCode = 500;
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        }
        
        http_response_code($statusCode);
        
        // Return JSON error response
        $response = [
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => APP_DEBUG ? $exception->getMessage() : 'Internal server error'
            ]
        ];
        
        if (APP_DEBUG) {
            $response['error']['file'] = $exception->getFile();
            $response['error']['line'] = $exception->getLine();
            $response['error']['trace'] = $exception->getTraceAsString();
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Convert error to exception
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleException(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        http_response_code(422);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $errors
            ]
        ]);
        exit();
    }
    
    /**
     * Send authentication error response
     */
    public static function authenticationError($message = 'Authentication required') {
        http_response_code(401);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'AUTHENTICATION_ERROR',
                'message' => $message
            ]
        ]);
        exit();
    }
    
    /**
     * Send authorization error response
     */
    public static function authorizationError($message = 'Access denied') {
        http_response_code(403);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'AUTHORIZATION_ERROR',
                'message' => $message
            ]
        ]);
        exit();
    }
    
    /**
     * Send not found error response
     */
    public static function notFoundError($message = 'Resource not found') {
        http_response_code(404);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message
            ]
        ]);
        exit();
    }
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $pagination = null) {
        http_response_code(200);
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
}

// Set error handlers
set_exception_handler(['ErrorHandler', 'handleException']);
set_error_handler(['ErrorHandler', 'handleError']);
register_shutdown_function(['ErrorHandler', 'handleFatalError']);