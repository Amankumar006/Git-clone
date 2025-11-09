<?php

require_once __DIR__ . '/../controllers/NotificationController.php';

$notificationController = new NotificationController();

// Handle notification routes
switch ($method) {
    case 'GET':
        if ($endpoint === 'notifications') {
            $notificationController->getUserNotifications();
        } elseif ($endpoint === 'notifications/unread-count') {
            $notificationController->getUnreadCount();
        } elseif ($endpoint === 'notifications/stats') {
            $notificationController->getNotificationStats();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'PUT':
        if (preg_match('/^notifications\/read\/(\d+)$/', $endpoint, $matches)) {
            $notificationController->markAsRead($matches[1]);
        } elseif ($endpoint === 'notifications/read-all') {
            $notificationController->markAllAsRead();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/^notifications\/(\d+)$/', $endpoint, $matches)) {
            $notificationController->deleteNotification($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}