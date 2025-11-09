<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/ModerationAction.php';
require_once __DIR__ . '/../models/ContentFilter.php';

class ModerationController extends BaseController {
    private $reportModel;
    private $moderationModel;
    private $filterModel;
    protected $currentUser;

    public function __construct() {
        parent::__construct();
        $this->reportModel = new Report();
        $this->moderationModel = new ModerationAction();
        $this->filterModel = new ContentFilter();
    }

    public function createReport() {
        try {
            $this->currentUser = $this->getAuthenticatedUser();
            
            $input = $this->getJsonInput();
            
            // Validate required fields
            if (empty($input['content_type']) || empty($input['content_id']) || empty($input['reason'])) {
                $this->sendError('Missing required fields: content_type, content_id, reason', 400);
            }
            
            $validContentTypes = ['article', 'comment', 'user'];
            $validReasons = ['spam', 'harassment', 'inappropriate', 'copyright', 'misinformation', 'other'];
            
            if (!in_array($input['content_type'], $validContentTypes)) {
                $this->sendError('Invalid content type', 400);
            }
            
            if (!in_array($input['reason'], $validReasons)) {
                $this->sendError('Invalid report reason', 400);
            }
            
            $report = $this->reportModel->createReport(
                $this->currentUser['id'],
                $input['content_type'],
                $input['content_id'],
                $input['reason'],
                $input['description'] ?? null
            );
            
            $this->sendResponse($report, 'Report submitted successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getPendingReports() {
        try {
            $this->requireAdmin();
            
            $pagination = $this->getPaginationParams();
            
            $reports = $this->reportModel->getPendingReports($pagination['limit'], $pagination['offset']);
            $stats = $this->reportModel->getReportStats();
            
            $this->sendResponse([
                'reports' => $reports,
                'stats' => $stats
            ], 'Reports retrieved successfully', 200, [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit']
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateReportStatus() {
        try {
            $this->requireAdmin();
            
            $reportId = $this->getPathParam('id');
            $input = $this->getJsonInput();
            
            if (empty($input['status'])) {
                $this->sendError('Status is required', 400);
            }
            
            $validStatuses = ['pending', 'reviewing', 'resolved', 'dismissed'];
            if (!in_array($input['status'], $validStatuses)) {
                $this->sendError('Invalid status', 400);
            }
            
            $success = $this->reportModel->updateReportStatus(
                $reportId,
                $input['status'],
                $this->currentUser['id'],
                $input['admin_notes'] ?? null
            );
            
            if (!$success) {
                $this->sendError('Report not found', 404);
            }
            
            $this->sendResponse(null, 'Report status updated successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function approveContent() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            if (empty($input['content_type']) || empty($input['content_id']) || empty($input['reason'])) {
                $this->sendError('Missing required fields: content_type, content_id, reason', 400);
            }
            
            $this->moderationModel->approveContent(
                $this->currentUser['id'],
                $input['content_type'],
                $input['content_id'],
                $input['reason']
            );
            
            $this->sendResponse(null, 'Content approved successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function removeContent() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            if (empty($input['content_type']) || empty($input['content_id']) || empty($input['reason'])) {
                $this->sendError('Missing required fields: content_type, content_id, reason', 400);
            }
            
            $this->moderationModel->removeContent(
                $this->currentUser['id'],
                $input['content_type'],
                $input['content_id'],
                $input['reason']
            );
            
            $this->sendResponse(null, 'Content removed successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function warnUser() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            if (empty($input['user_id']) || empty($input['reason'])) {
                $this->sendError('Missing required fields: user_id, reason', 400);
            }
            
            $this->moderationModel->warnUser(
                $this->currentUser['id'],
                $input['user_id'],
                $input['reason']
            );
            
            $this->sendResponse(null, 'User warned successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function suspendUser() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            if (empty($input['user_id']) || empty($input['reason'])) {
                $this->sendError('Missing required fields: user_id, reason', 400);
            }
            
            $duration = isset($input['duration']) ? (int)$input['duration'] : null;
            
            $this->moderationModel->suspendUser(
                $this->currentUser['id'],
                $input['user_id'],
                $input['reason'],
                $duration
            );
            
            $action = $duration ? 'suspended' : 'banned';
            $this->sendResponse(null, "User {$action} successfully");
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getModerationHistory() {
        try {
            $this->requireAdmin();
            
            $pagination = $this->getPaginationParams();
            
            $targetType = $this->queryParams['target_type'] ?? null;
            $targetId = $this->queryParams['target_id'] ?? null;
            
            $history = $this->moderationModel->getActionHistory($targetType, $targetId, $pagination['limit'], $pagination['offset']);
            $stats = $this->moderationModel->getModerationStats();
            
            $this->sendResponse([
                'history' => $history,
                'stats' => $stats
            ], 'Moderation history retrieved successfully', 200, [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit']
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getFlaggedContent() {
        try {
            $this->requireAdmin();
            
            $pagination = $this->getPaginationParams();
            $reviewed = isset($this->queryParams['reviewed']) ? (bool)$this->queryParams['reviewed'] : false;
            
            $flaggedContent = $this->filterModel->getFlaggedContent($reviewed, $pagination['limit'], $pagination['offset']);
            $stats = $this->filterModel->getFilterStats();
            
            $this->sendResponse([
                'flagged_content' => $flaggedContent,
                'stats' => $stats
            ], 'Flagged content retrieved successfully', 200, [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit']
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function markFlagReviewed() {
        try {
            $this->requireAdmin();
            
            $flagId = $this->getPathParam('id');
            
            $success = $this->filterModel->markFlagAsReviewed($flagId, $this->currentUser['id']);
            
            if (!$success) {
                $this->sendError('Flag not found', 404);
            }
            
            $this->sendResponse(null, 'Flag marked as reviewed');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function scanContent() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            if (empty($input['content_type']) || empty($input['content_id']) || empty($input['content'])) {
                $this->sendError('Missing required fields: content_type, content_id, content', 400);
            }
            
            $flags = $this->filterModel->scanContent(
                $input['content_type'],
                $input['content_id'],
                $input['content']
            );
            
            $this->sendResponse([
                'flags' => $flags
            ], 'Content scanned successfully');
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    private function requireAdmin() {
        $this->currentUser = $this->getAuthenticatedUser();
        
        if (!in_array($this->currentUser['role'], ['admin', 'moderator'])) {
            $this->sendError('Admin access required', 403);
        }
    }

    private function getPathParam($param) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // Find the parameter in the URL segments
        $key = array_search($param, $segments);
        if ($key !== false && isset($segments[$key + 1])) {
            return $segments[$key + 1];
        }
        
        return $_GET[$param] ?? null;
    }
}