<?php

require_once __DIR__ . '/BaseRepository.php';

class PublicationGuideline extends BaseRepository {
    protected $table = 'publication_guidelines';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new guideline
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (publication_id, title, content, category, is_required, display_order, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['publication_id'],
                $data['title'],
                $data['content'],
                $data['category'] ?? 'general',
                $data['is_required'] ? 1 : 0,
                $data['display_order'] ?? 0,
                $data['created_by']
            ]);
            
            if ($result) {
                return $this->getById($this->db->lastInsertId());
            }
            
            return false;
        } catch (Exception $e) {
            error_log("PublicationGuideline create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get guideline by ID
     */
    public function getById($id) {
        $sql = "SELECT g.*, u.username as created_by_username
                FROM {$this->table} g
                JOIN users u ON g.created_by = u.id
                WHERE g.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get guidelines for publication
     */
    public function getByPublication($publicationId, $category = null) {
        $whereClause = "WHERE g.publication_id = ?";
        $params = [$publicationId];
        
        if ($category) {
            $whereClause .= " AND g.category = ?";
            $params[] = $category;
        }
        
        $sql = "SELECT g.*, u.username as created_by_username
                FROM {$this->table} g
                JOIN users u ON g.created_by = u.id
                {$whereClause}
                ORDER BY g.category, g.display_order ASC, g.title ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get guidelines grouped by category
     */
    public function getByPublicationGrouped($publicationId) {
        $guidelines = $this->getByPublication($publicationId);
        
        $grouped = [];
        foreach ($guidelines as $guideline) {
            $category = $guideline['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $guideline;
        }
        
        return $grouped;
    }
    
    /**
     * Get required guidelines for publication
     */
    public function getRequiredGuidelines($publicationId) {
        $sql = "SELECT g.*, u.username as created_by_username
                FROM {$this->table} g
                JOIN users u ON g.created_by = u.id
                WHERE g.publication_id = ? AND g.is_required = 1
                ORDER BY g.category, g.display_order ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update guideline
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET title = ?, content = ?, category = ?, is_required = ?, 
                        display_order = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['title'],
                $data['content'],
                $data['category'] ?? 'general',
                $data['is_required'] ? 1 : 0,
                $data['display_order'] ?? 0,
                $id
            ]);
            
            if ($result) {
                return $this->getById($id);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("PublicationGuideline update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete guideline
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Reorder guidelines
     */
    public function reorder($publicationId, $guidelineOrders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($guidelineOrders as $order) {
                $sql = "UPDATE {$this->table} 
                        SET display_order = ? 
                        WHERE id = ? AND publication_id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$order['display_order'], $order['id'], $publicationId]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("PublicationGuideline reorder error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get guideline categories
     */
    public function getCategories() {
        return [
            'writing_style' => [
                'name' => 'Writing Style',
                'description' => 'Guidelines about tone, voice, and writing style'
            ],
            'content_policy' => [
                'name' => 'Content Policy',
                'description' => 'Rules about what content is acceptable'
            ],
            'submission_process' => [
                'name' => 'Submission Process',
                'description' => 'How to submit articles and what to expect'
            ],
            'formatting' => [
                'name' => 'Formatting',
                'description' => 'Guidelines for formatting articles'
            ],
            'general' => [
                'name' => 'General',
                'description' => 'General guidelines and information'
            ]
        ];
    }
    
    /**
     * Create default guidelines for publication
     */
    public function createDefaultGuidelines($publicationId, $createdBy) {
        $defaultGuidelines = [
            [
                'title' => 'Writing Style Guidelines',
                'content' => 'Please maintain a professional and engaging tone throughout your articles. Use clear, concise language and avoid jargon unless necessary. Write in active voice when possible.',
                'category' => 'writing_style',
                'is_required' => true,
                'display_order' => 1
            ],
            [
                'title' => 'Content Standards',
                'content' => 'All content must be original and properly attributed. Ensure your articles provide value to readers and align with our publication\'s mission and values.',
                'category' => 'content_policy',
                'is_required' => true,
                'display_order' => 1
            ],
            [
                'title' => 'Submission Process',
                'content' => 'Submit your articles through the publication dashboard. Include a compelling title, subtitle, and relevant tags. Articles will be reviewed within 7 days.',
                'category' => 'submission_process',
                'is_required' => false,
                'display_order' => 1
            ],
            [
                'title' => 'Formatting Requirements',
                'content' => 'Use proper headings (H2, H3) to structure your content. Include a featured image when relevant. Keep paragraphs concise and use bullet points for lists.',
                'category' => 'formatting',
                'is_required' => false,
                'display_order' => 1
            ]
        ];
        
        $created = [];
        foreach ($defaultGuidelines as $guideline) {
            $guideline['publication_id'] = $publicationId;
            $guideline['created_by'] = $createdBy;
            
            $result = $this->create($guideline);
            if ($result) {
                $created[] = $result;
            }
        }
        
        return $created;
    }
    
    /**
     * Get guidelines summary for writers
     */
    public function getWriterSummary($publicationId) {
        $guidelines = $this->getByPublicationGrouped($publicationId);
        
        $summary = [
            'total_guidelines' => 0,
            'required_guidelines' => 0,
            'categories' => [],
            'key_points' => []
        ];
        
        foreach ($guidelines as $category => $categoryGuidelines) {
            $summary['categories'][$category] = count($categoryGuidelines);
            $summary['total_guidelines'] += count($categoryGuidelines);
            
            foreach ($categoryGuidelines as $guideline) {
                if ($guideline['is_required']) {
                    $summary['required_guidelines']++;
                    $summary['key_points'][] = [
                        'title' => $guideline['title'],
                        'category' => $category,
                        'content_preview' => substr(strip_tags($guideline['content']), 0, 100) . '...'
                    ];
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Check compliance of article against guidelines
     */
    public function checkCompliance($publicationId, $articleData) {
        $guidelines = $this->getRequiredGuidelines($publicationId);
        $compliance = [
            'overall_score' => 0,
            'checks' => [],
            'recommendations' => []
        ];
        
        foreach ($guidelines as $guideline) {
            $check = $this->performComplianceCheck($guideline, $articleData);
            $compliance['checks'][] = $check;
            
            if (!$check['passed']) {
                $compliance['recommendations'][] = $check['recommendation'];
            }
        }
        
        // Calculate overall score
        $totalChecks = count($compliance['checks']);
        $passedChecks = count(array_filter($compliance['checks'], function($check) {
            return $check['passed'];
        }));
        
        $compliance['overall_score'] = $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 100;
        
        return $compliance;
    }
    
    /**
     * Perform individual compliance check
     */
    private function performComplianceCheck($guideline, $articleData) {
        // This is a simplified compliance check
        // In a real application, you might want more sophisticated analysis
        
        $check = [
            'guideline_id' => $guideline['id'],
            'guideline_title' => $guideline['title'],
            'category' => $guideline['category'],
            'passed' => true,
            'score' => 100,
            'recommendation' => null
        ];
        
        // Basic checks based on category
        switch ($guideline['category']) {
            case 'formatting':
                if (empty($articleData['title']) || strlen($articleData['title']) < 10) {
                    $check['passed'] = false;
                    $check['score'] = 0;
                    $check['recommendation'] = 'Title should be at least 10 characters long';
                }
                break;
                
            case 'content_policy':
                if (empty($articleData['content']) || strlen(strip_tags($articleData['content'])) < 300) {
                    $check['passed'] = false;
                    $check['score'] = 0;
                    $check['recommendation'] = 'Article content should be at least 300 words';
                }
                break;
                
            case 'writing_style':
                // Check for basic writing style issues
                $content = strip_tags($articleData['content']);
                $sentences = explode('.', $content);
                $avgSentenceLength = strlen($content) / max(count($sentences), 1);
                
                if ($avgSentenceLength > 200) {
                    $check['passed'] = false;
                    $check['score'] = 50;
                    $check['recommendation'] = 'Consider breaking up long sentences for better readability';
                }
                break;
        }
        
        return $check;
    }
    
    /**
     * Check if user can manage guidelines for publication
     */
    public function canUserManage($publicationId, $userId) {
        require_once __DIR__ . '/Publication.php';
        $publicationModel = new Publication();
        
        return $publicationModel->hasPermission($publicationId, $userId, 'admin');
    }
}