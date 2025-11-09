<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class NotificationController extends BaseController {
    private $notificationModel;
    private $authMiddleware;
    
    public function __construct() {
        parent::__construct();
        $this->notificationModel = new Notification();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    /**
     * Get user notifications
     * GET /api/notifications
     */
    public function getUserNotifications() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get query parameters
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get notifications
            $notifications = $this->notificationModel->getUserNotifications(
                $user['id'], 
                $unreadOnly, 
                $limit, 
                $offset
            );
            
            // Get unread count
            $unreadCount = $this->notificationModel->getUnreadCount($user['id']);
            
            return $this->sendResponse([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => count($notifications)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUserNotifications: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Mark notification as read
     * PUT /api/notifications/read/{id}
     */
    public function markAsRead($notificationId) {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $notificationId = (int)$notificationId;
            
            // Mark as read
            $result = $this->notificationModel->markAsRead($notificationId, $user['id']);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'unread_count' => $this->notificationModel->getUnreadCount($user['id'])
                ], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in markAsRead: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Mark all notifications as read
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Mark all as read
            $result = $this->notificationModel->markAllAsRead($user['id']);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'unread_count' => 0
                ], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in markAllAsRead: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Delete a notification
     * DELETE /api/notifications/{id}
     */
    public function deleteNotification($notificationId) {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $notificationId = (int)$notificationId;
            
            // Delete notification
            $result = $this->notificationModel->deleteNotification($notificationId, $user['id']);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'unread_count' => $this->notificationModel->getUnreadCount($user['id'])
                ], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in deleteNotification: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get unread notification count
     * GET /api/notifications/unread-count
     */
    public function getUnreadCount() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $unreadCount = $this->notificationModel->getUnreadCount($user['id']);
            
            return $this->sendResponse([
                'unread_count' => $unreadCount
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUnreadCount: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get notification statistics
     * GET /api/notifications/stats
     */
    public function getNotificationStats() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $stats = $this->notificationModel->getNotificationStats($user['id']);
            
            return $this->sendResponse($stats);
            
        } catch (Exception $e) {
            error_log("Error in getNotificationStats: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
}