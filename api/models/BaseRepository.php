<?php
/**
 * Base Repository Class
 * Provides common database operations for all repositories
 */

require_once __DIR__ . '/../config/database.php';

abstract class BaseRepository {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Find record by ID
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find all records with optional conditions
     */
    public function findAll($conditions = [], $orderBy = 'id', $order = 'ASC', $limit = null, $offset = 0) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    $whereClause[] = "$column = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $sql .= " ORDER BY $orderBy $order";
            
            if ($limit) {
                $sql .= " LIMIT $limit OFFSET $offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in findAll: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(array_values($data));
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update record by ID
     */
    public function update($id, $data) {
        try {
            $columns = array_keys($data);
            $setClause = array_map(function($col) { return "$col = ?"; }, $columns);
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = ?";
            
            $params = array_values($data);
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete record by ID
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count records with optional conditions
     */
    public function count($conditions = []) {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    $whereClause[] = "$column = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in count: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute custom query
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error in query: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->rollBack();
    }
}