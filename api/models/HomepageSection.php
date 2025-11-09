<?php

require_once __DIR__ . '/BaseRepository.php';

class HomepageSection extends BaseRepository {
    protected $table = 'homepage_sections';

    public function getAllSections() {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY position ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // Decode JSON configuration
            foreach ($results as &$section) {
                $section['configuration'] = json_decode($section['configuration'], true) ?: [];
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting homepage sections: " . $e->getMessage());
            return [];
        }
    }

    public function getEnabledSections() {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE is_enabled = TRUE ORDER BY position ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // Decode JSON configuration
            foreach ($results as &$section) {
                $section['configuration'] = json_decode($section['configuration'], true) ?: [];
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting enabled homepage sections: " . $e->getMessage());
            return [];
        }
    }

    public function updateSection($id, $data) {
        try {
            $allowedFields = ['section_name', 'section_type', 'is_enabled', 'position', 'configuration'];
            $updateFields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "{$field} = ?";
                    if ($field === 'configuration') {
                        $params[] = json_encode($value);
                    } else {
                        $params[] = $value;
                    }
                }
            }
            
            if (empty($updateFields)) {
                return false;
            }
            
            $params[] = $id;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating homepage section: " . $e->getMessage());
            return false;
        }
    }

    public function createSection($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (section_name, section_type, is_enabled, position, configuration) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['section_name'],
                $data['section_type'],
                $data['is_enabled'] ?? true,
                $data['position'] ?? 0,
                json_encode($data['configuration'] ?? [])
            ]);
        } catch (Exception $e) {
            error_log("Error creating homepage section: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSection($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting homepage section: " . $e->getMessage());
            return false;
        }
    }

    public function reorderSections($sectionIds) {
        try {
            $this->db->beginTransaction();
            
            foreach ($sectionIds as $position => $sectionId) {
                $sql = "UPDATE {$this->table} SET position = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$position + 1, $sectionId]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error reordering homepage sections: " . $e->getMessage());
            return false;
        }
    }
}