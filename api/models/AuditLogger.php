<?php

class AuditLogger extends BaseRepository {
    protected $table = 'audit_log';
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Log an admin action
     */
    public function logAction($adminId, $actionType, $targetType, $targetId = null, $oldValues = null, $newValues = null, $ipAddress = null, $userAgent = null) {
        try {
            $sql = "INSERT INTO {$this->table} (admin_id, action_type, target_type, target_id, old_values, new_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $adminId,
                $actionType,
                $targetType,
                $targetId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $ipAddress,
                $userAgent
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Failed to log audit action: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit log entries
     */
    public function getAuditLog($filters = [], $limit = 50, $offset = 0) {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['admin_id'])) {
            $whereConditions[] = "al.admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        
        if (!empty($filters['action_type'])) {
            $whereConditions[] = "al.action_type = ?";
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['target_type'])) {
            $whereConditions[] = "al.target_type = ?";
            $params[] = $filters['target_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT al.*, u.username as admin_username
                FROM {$this->table} al
                LEFT JOIN users u ON al.admin_id = u.id
                WHERE {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get audit statistics
     */
    public function getAuditStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT admin_id) as active_admins,
                    COUNT(DISTINCT action_type) as action_types,
                    action_type,
                    COUNT(*) as action_count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action_type
                ORDER BY action_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $actionStats = $stmt->fetchAll();
        
        // Get summary stats
        $sql = "SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT admin_id) as active_admins,
                    COUNT(DISTINCT DATE(created_at)) as active_days
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $summary = $stmt->fetch();
        
        return [
            'summary' => $summary,
            'actions_by_type' => $actionStats
        ];
    }

    /**
     * Get admin activity summary
     */
    public function getAdminActivity($days = 30) {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.role,
                    COUNT(al.id) as total_actions,
                    COUNT(DISTINCT DATE(al.created_at)) as active_days,
                    MAX(al.created_at) as last_action,
                    GROUP_CONCAT(DISTINCT al.action_type ORDER BY al.action_type) as action_types
                FROM users u
                LEFT JOIN {$this->table} al ON u.id = al.admin_id 
                    AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                WHERE u.role IN ('admin', 'moderator')
                GROUP BY u.id, u.username, u.role
                ORDER BY total_actions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Track changes to sensitive data
     */
    public function trackDataChange($adminId, $table, $recordId, $field, $oldValue, $newValue, $ipAddress = null) {
        $changeData = [
            'table' => $table,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ];
        
        return $this->logAction(
            $adminId,
            'data_change',
            'system',
            $recordId,
            ['field' => $field, 'old_value' => $oldValue],
            ['field' => $field, 'new_value' => $newValue],
            $ipAddress
        );
    }

    /**
     * Get data change history for a record
     */
    public function getDataChangeHistory($targetType, $targetId, $limit = 20) {
        $sql = "SELECT al.*, u.username as admin_username
                FROM {$this->table} al
                LEFT JOIN users u ON al.admin_id = u.id
                WHERE al.target_type = ? AND al.target_id = ?
                AND al.action_type IN ('create', 'update', 'delete', 'data_change')
                ORDER BY al.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$targetType, $targetId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Export audit log
     */
    public function exportAuditLog($filters = [], $format = 'csv') {
        $auditData = $this->getAuditLog($filters, 10000, 0); // Large limit for export
        
        if ($format === 'csv') {
            return $this->formatAsCSV($auditData);
        } else {
            return $auditData;
        }
    }

    private function formatAsCSV($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        $headers = ['ID', 'Admin', 'Action', 'Target Type', 'Target ID', 'IP Address', 'Created At'];
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            $csvRow = [
                $row['id'],
                $row['admin_username'] ?? 'Unknown',
                $row['action_type'],
                $row['target_type'],
                $row['target_id'] ?? '',
                $row['ip_address'] ?? '',
                $row['created_at']
            ];
            fputcsv($output, $csvRow);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}