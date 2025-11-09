<?php

require_once __DIR__ . '/BaseRepository.php';

class SystemSettings extends BaseRepository {
    protected $table = 'system_settings';

    public function getSetting($key, $default = null) {
        try {
            $sql = "SELECT setting_value, setting_type FROM {$this->table} WHERE setting_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return $default;
            }
            
            return $this->castValue($result['setting_value'], $result['setting_type']);
        } catch (Exception $e) {
            error_log("Error getting setting {$key}: " . $e->getMessage());
            return $default;
        }
    }

    public function getAllSettings() {
        try {
            $sql = "SELECT setting_key, setting_value, setting_type, description FROM {$this->table} ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                    'type' => $row['setting_type'],
                    'description' => $row['description']
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("Error getting all settings: " . $e->getMessage());
            return [];
        }
    }

    public function updateSetting($key, $value, $type = null) {
        try {
            // If type is not provided, try to get existing type
            if ($type === null) {
                $sql = "SELECT setting_type FROM {$this->table} WHERE setting_key = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$key]);
                $result = $stmt->fetch();
                $type = $result ? $result['setting_type'] : 'string';
            }
            
            // Convert value to string for storage
            $stringValue = $this->valueToString($value, $type);
            
            $sql = "INSERT INTO {$this->table} (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value), 
                    setting_type = VALUES(setting_type),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key, $stringValue, $type]);
        } catch (Exception $e) {
            error_log("Error updating setting {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSetting($key) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE setting_key = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key]);
        } catch (Exception $e) {
            error_log("Error deleting setting {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function getSettingsByCategory($prefix) {
        try {
            $sql = "SELECT setting_key, setting_value, setting_type, description 
                    FROM {$this->table} 
                    WHERE setting_key LIKE ? 
                    ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
            $results = $stmt->fetchAll();
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                    'type' => $row['setting_type'],
                    'description' => $row['description']
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("Error getting settings by category {$prefix}: " . $e->getMessage());
            return [];
        }
    }

    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            case 'json':
                return json_decode($value, true) ?: [];
            case 'string':
            default:
                return (string)$value;
        }
    }

    private function valueToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'number':
                return (string)$value;
            case 'json':
                return json_encode($value);
            case 'string':
            default:
                return (string)$value;
        }
    }
}