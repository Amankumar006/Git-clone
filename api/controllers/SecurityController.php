<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/SecurityMonitor.php';
require_once __DIR__ . '/../models/AuditLogger.php';
require_once __DIR__ . '/../models/SystemHealth.php';

class SecurityController extends BaseController {
    private $securityMonitor;
    private $auditLogger;
    private $systemHealth;

    public function __construct() {
        parent::__construct();
        $this->securityMonitor = new SecurityMonitor();
        $this->auditLogger = new AuditLogger();
        $this->systemHealth = new SystemHealth();
    }

    public function getSecurityDashboard() {
        try {
            $this->requireAdmin();
            
            $days = (int)($_GET['days'] ?? 30);
            
            $dashboard = [
                'security_stats' => $this->securityMonitor->getSecurityStats($days),
                'top_threats' => $this->securityMonitor->getTopThreats($days, 10),
                'suspicious_logins' => $this->securityMonitor->detectSuspiciousLogins(3600),
                'breach_attempts' => $this->securityMonitor->detectDataBreachAttempts(),
                'system_health' => $this->systemHealth->getCurrentHealthStatus(),
                'active_alerts' => $this->systemHealth->getActiveAlerts(10),
                'audit_summary' => $this->auditLogger->getAuditStats($days)
            ];
            
            $this->sendResponse($dashboard);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getSecurityEvents() {
        try {
            $this->requireAdmin();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'event_type' => $_GET['event_type'] ?? '',
                'severity' => $_GET['severity'] ?? '',
                'user_id' => $_GET['user_id'] ?? '',
                'ip_address' => $_GET['ip_address'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);
            
            $events = $this->securityMonitor->getSecurityEvents($filters, $limit, $offset);
            
            $this->sendResponse([
                'events' => $events,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function blockIP() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['ip_address', 'reason']);
            
            $ipAddress = $input['ip_address'];
            $reason = $input['reason'];
            $duration = $input['duration'] ?? null; // Duration in minutes
            
            // Validate IP address format
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                throw new Exception('Invalid IP address format');
            }
            
            $success = $this->securityMonitor->blockIP(
                $ipAddress,
                $reason,
                $this->currentUser['id'],
                $duration
            );
            
            if ($success) {
                // Log the action
                $this->auditLogger->logAction(
                    $this->currentUser['id'],
                    'ip_block',
                    'system',
                    null,
                    null,
                    ['ip_address' => $ipAddress, 'reason' => $reason, 'duration' => $duration],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );
                
                $this->sendResponse(['message' => 'IP address blocked successfully']);
            } else {
                throw new Exception('Failed to block IP address');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getAuditLog() {
        try {
            $this->requireAdmin();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'admin_id' => $_GET['admin_id'] ?? '',
                'action_type' => $_GET['action_type'] ?? '',
                'target_type' => $_GET['target_type'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);
            
            $auditLog = $this->auditLogger->getAuditLog($filters, $limit, $offset);
            $auditStats = $this->auditLogger->getAuditStats(30);
            
            $this->sendResponse([
                'audit_log' => $auditLog,
                'stats' => $auditStats,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getSystemHealth() {
        try {
            $this->requireAdmin();
            
            $healthStatus = $this->systemHealth->getCurrentHealthStatus();
            $healthHistory = $this->systemHealth->getHealthHistory(null, 24);
            
            $this->sendResponse([
                'current_status' => $healthStatus,
                'history' => $healthHistory
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function createAlert() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['type', 'severity', 'title', 'message']);
            
            $alertId = $this->systemHealth->createAlert(
                $input['type'],
                $input['severity'],
                $input['title'],
                $input['message'],
                $input['metadata'] ?? null
            );
            
            if ($alertId) {
                // Log the action
                $this->auditLogger->logAction(
                    $this->currentUser['id'],
                    'alert_created',
                    'system',
                    $alertId,
                    null,
                    $input,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                $this->sendResponse(['message' => 'Alert created successfully', 'alert_id' => $alertId]);
            } else {
                throw new Exception('Failed to create alert');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function resolveAlert() {
        try {
            $this->requireAdmin();
            
            $alertId = $this->getPathParam('id');
            
            $success = $this->systemHealth->resolveAlert($alertId, $this->currentUser['id']);
            
            if ($success) {
                // Log the action
                $this->auditLogger->logAction(
                    $this->currentUser['id'],
                    'alert_resolved',
                    'system',
                    $alertId,
                    null,
                    ['resolved_by' => $this->currentUser['id']],
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                $this->sendResponse(['message' => 'Alert resolved successfully']);
            } else {
                throw new Exception('Alert not found or already resolved');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function generateSecurityReport() {
        try {
            $this->requireAdmin();
            
            $days = (int)($_GET['days'] ?? 30);
            $format = $_GET['format'] ?? 'json';
            
            $report = $this->securityMonitor->generateSecurityReport($days);
            
            if ($format === 'pdf') {
                $this->generatePDFReport($report);
            } else {
                $this->sendResponse($report);
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function exportAuditLog() {
        try {
            $this->requireAdmin();
            
            $format = $_GET['format'] ?? 'csv';
            $days = (int)($_GET['days'] ?? 30);
            
            $filters = [
                'date_from' => date('Y-m-d', strtotime("-{$days} days"))
            ];
            
            if ($format === 'csv') {
                $csvData = $this->auditLogger->exportAuditLog($filters, 'csv');
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');
                header('Cache-Control: no-cache, must-revalidate');
                
                echo $csvData;
                exit;
            } else {
                $data = $this->auditLogger->exportAuditLog($filters, 'json');
                $this->sendResponse([
                    'data' => $data,
                    'exported_at' => date('Y-m-d H:i:s'),
                    'period_days' => $days
                ]);
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function runSecurityScan() {
        try {
            $this->requireAdmin();
            
            $scanResults = [
                'suspicious_logins' => $this->securityMonitor->detectSuspiciousLogins(3600),
                'breach_attempts' => $this->securityMonitor->detectDataBreachAttempts(),
                'system_health' => $this->systemHealth->getCurrentHealthStatus(),
                'cleanup_results' => $this->securityMonitor->cleanupExpiredData()
            ];
            
            // Check for unusual activity patterns
            $users = $this->getUsersForActivityCheck();
            $unusualActivity = [];
            
            foreach ($users as $user) {
                $anomalies = $this->securityMonitor->detectUnusualActivity($user['id'], 24);
                if (!empty($anomalies)) {
                    $unusualActivity[] = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'anomalies' => $anomalies
                    ];
                }
            }
            
            $scanResults['unusual_activity'] = $unusualActivity;
            
            // Log the scan
            $this->auditLogger->logAction(
                $this->currentUser['id'],
                'security_scan',
                'system',
                null,
                null,
                ['scan_results' => count($scanResults)],
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            $this->sendResponse([
                'message' => 'Security scan completed',
                'results' => $scanResults,
                'scan_time' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    private function getUsersForActivityCheck() {
        $sql = "SELECT id, username FROM users 
                WHERE role IN ('admin', 'moderator') 
                OR id IN (
                    SELECT DISTINCT user_id FROM security_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                )
                LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function generatePDFReport($report) {
        // This would integrate with a PDF library like TCPDF or FPDF
        // For now, return JSON with a note
        $this->sendResponse([
            'message' => 'PDF generation not implemented yet',
            'report' => $report
        ]);
    }

    private function requireAdmin() {
        $this->requireAuth();
        
        if (!in_array($this->currentUser['role'], ['admin', 'moderator'])) {
            throw new Exception('Admin access required');
        }
    }

    private function getPathParam($param) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        $key = array_search($param, $segments);
        if ($key !== false && isset($segments[$key + 1])) {
            return $segments[$key + 1];
        }
        
        return $_GET[$param] ?? null;
    }
}