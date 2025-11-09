<?php

require_once __DIR__ . '/BaseRepository.php';

class PublicationTemplate extends BaseRepository {
    protected $table = 'publication_templates';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new template
     */
    public function create($data) {
        try {
            // If this is set as default, unset other defaults
            if ($data['is_default']) {
                $this->unsetDefaultTemplates($data['publication_id']);
            }
            
            $sql = "INSERT INTO {$this->table} 
                    (publication_id, name, description, template_content, is_default, is_active, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['publication_id'],
                $data['name'],
                $data['description'] ?? null,
                json_encode($data['template_content']),
                $data['is_default'] ? 1 : 0,
                $data['is_active'] ?? 1,
                $data['created_by']
            ]);
            
            if ($result) {
                return $this->getById($this->db->lastInsertId());
            }
            
            return false;
        } catch (Exception $e) {
            error_log("PublicationTemplate create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get template by ID
     */
    public function getById($id) {
        $sql = "SELECT t.*, u.username as created_by_username
                FROM {$this->table} t
                JOIN users u ON t.created_by = u.id
                WHERE t.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $template = $stmt->fetch();
        
        if ($template && $template['template_content']) {
            $template['template_content'] = json_decode($template['template_content'], true);
        }
        
        return $template;
    }
    
    /**
     * Get templates for publication
     */
    public function getByPublication($publicationId, $activeOnly = true) {
        $whereClause = "WHERE t.publication_id = ?";
        $params = [$publicationId];
        
        if ($activeOnly) {
            $whereClause .= " AND t.is_active = 1";
        }
        
        $sql = "SELECT t.*, u.username as created_by_username
                FROM {$this->table} t
                JOIN users u ON t.created_by = u.id
                {$whereClause}
                ORDER BY t.is_default DESC, t.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $templates = $stmt->fetchAll();
        
        // Decode template content for each template
        foreach ($templates as &$template) {
            if ($template['template_content']) {
                $template['template_content'] = json_decode($template['template_content'], true);
            }
        }
        
        return $templates;
    }
    
    /**
     * Get default template for publication
     */
    public function getDefaultTemplate($publicationId) {
        $sql = "SELECT t.*, u.username as created_by_username
                FROM {$this->table} t
                JOIN users u ON t.created_by = u.id
                WHERE t.publication_id = ? AND t.is_default = 1 AND t.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId]);
        
        $template = $stmt->fetch();
        
        if ($template && $template['template_content']) {
            $template['template_content'] = json_decode($template['template_content'], true);
        }
        
        return $template;
    }
    
    /**
     * Update template
     */
    public function update($id, $data) {
        try {
            // If this is set as default, unset other defaults
            if (isset($data['is_default']) && $data['is_default']) {
                $template = $this->getById($id);
                if ($template) {
                    $this->unsetDefaultTemplates($template['publication_id']);
                }
            }
            
            $sql = "UPDATE {$this->table} 
                    SET name = ?, description = ?, template_content = ?, 
                        is_default = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                json_encode($data['template_content']),
                isset($data['is_default']) ? ($data['is_default'] ? 1 : 0) : 0,
                isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
                $id
            ]);
            
            if ($result) {
                return $this->getById($id);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("PublicationTemplate update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete template
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Set template as default
     */
    public function setAsDefault($id) {
        try {
            $template = $this->getById($id);
            if (!$template) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Unset other defaults
            $this->unsetDefaultTemplates($template['publication_id']);
            
            // Set this as default
            $sql = "UPDATE {$this->table} SET is_default = 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("PublicationTemplate setAsDefault error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unset default templates for publication
     */
    private function unsetDefaultTemplates($publicationId) {
        $sql = "UPDATE {$this->table} SET is_default = 0 WHERE publication_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$publicationId]);
    }
    
    /**
     * Apply template to article
     */
    public function applyToArticle($templateId, $articleData) {
        $template = $this->getById($templateId);
        if (!$template) {
            return false;
        }
        
        $templateContent = $template['template_content'];
        
        // Merge template structure with article data
        $appliedContent = $this->mergeTemplateWithData($templateContent, $articleData);
        
        return [
            'template' => $template,
            'content' => $appliedContent,
            'structure' => $templateContent
        ];
    }
    
    /**
     * Merge template structure with article data
     */
    private function mergeTemplateWithData($templateContent, $articleData) {
        // This is a simplified implementation
        // In a real application, you might want more sophisticated template processing
        
        $merged = $templateContent;
        
        // Replace placeholders with actual data
        if (isset($merged['sections'])) {
            foreach ($merged['sections'] as &$section) {
                if (isset($section['placeholder']) && isset($articleData[$section['placeholder']])) {
                    $section['content'] = $articleData[$section['placeholder']];
                }
            }
        }
        
        // Set title and subtitle if provided in template
        if (isset($articleData['title'])) {
            $merged['title'] = $articleData['title'];
        }
        
        if (isset($articleData['subtitle'])) {
            $merged['subtitle'] = $articleData['subtitle'];
        }
        
        return $merged;
    }
    
    /**
     * Get template usage statistics
     */
    public function getUsageStats($templateId) {
        // This would track how many articles use this template
        // For now, return basic stats
        return [
            'total_uses' => 0,
            'recent_uses' => 0,
            'popular_sections' => []
        ];
    }
    
    /**
     * Duplicate template
     */
    public function duplicate($templateId, $newName, $createdBy) {
        $template = $this->getById($templateId);
        if (!$template) {
            return false;
        }
        
        $duplicateData = [
            'publication_id' => $template['publication_id'],
            'name' => $newName,
            'description' => $template['description'] . ' (Copy)',
            'template_content' => $template['template_content'],
            'is_default' => false,
            'is_active' => true,
            'created_by' => $createdBy
        ];
        
        return $this->create($duplicateData);
    }
    
    /**
     * Get predefined template types
     */
    public function getPredefinedTemplates() {
        return [
            'article' => [
                'name' => 'Standard Article',
                'description' => 'Basic article template with introduction, body, and conclusion',
                'template_content' => [
                    'sections' => [
                        [
                            'type' => 'introduction',
                            'title' => 'Introduction',
                            'placeholder' => 'intro_content',
                            'required' => true,
                            'description' => 'Brief introduction to the topic'
                        ],
                        [
                            'type' => 'body',
                            'title' => 'Main Content',
                            'placeholder' => 'main_content',
                            'required' => true,
                            'description' => 'Main article content'
                        ],
                        [
                            'type' => 'conclusion',
                            'title' => 'Conclusion',
                            'placeholder' => 'conclusion_content',
                            'required' => false,
                            'description' => 'Summary and final thoughts'
                        ]
                    ],
                    'formatting' => [
                        'max_title_length' => 100,
                        'min_content_length' => 500,
                        'required_tags' => 1,
                        'max_tags' => 5
                    ]
                ]
            ],
            'tutorial' => [
                'name' => 'Tutorial/How-to',
                'description' => 'Step-by-step tutorial template',
                'template_content' => [
                    'sections' => [
                        [
                            'type' => 'overview',
                            'title' => 'Overview',
                            'placeholder' => 'overview_content',
                            'required' => true,
                            'description' => 'What will readers learn?'
                        ],
                        [
                            'type' => 'prerequisites',
                            'title' => 'Prerequisites',
                            'placeholder' => 'prerequisites_content',
                            'required' => false,
                            'description' => 'What readers need to know beforehand'
                        ],
                        [
                            'type' => 'steps',
                            'title' => 'Step-by-step Instructions',
                            'placeholder' => 'steps_content',
                            'required' => true,
                            'description' => 'Detailed instructions'
                        ],
                        [
                            'type' => 'conclusion',
                            'title' => 'Conclusion & Next Steps',
                            'placeholder' => 'conclusion_content',
                            'required' => false,
                            'description' => 'Summary and what to do next'
                        ]
                    ]
                ]
            ],
            'review' => [
                'name' => 'Product/Service Review',
                'description' => 'Template for reviewing products or services',
                'template_content' => [
                    'sections' => [
                        [
                            'type' => 'introduction',
                            'title' => 'Introduction',
                            'placeholder' => 'intro_content',
                            'required' => true,
                            'description' => 'What are you reviewing?'
                        ],
                        [
                            'type' => 'pros',
                            'title' => 'Pros',
                            'placeholder' => 'pros_content',
                            'required' => true,
                            'description' => 'What works well?'
                        ],
                        [
                            'type' => 'cons',
                            'title' => 'Cons',
                            'placeholder' => 'cons_content',
                            'required' => true,
                            'description' => 'What could be better?'
                        ],
                        [
                            'type' => 'verdict',
                            'title' => 'Final Verdict',
                            'placeholder' => 'verdict_content',
                            'required' => true,
                            'description' => 'Overall recommendation'
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Check if user can manage templates for publication
     */
    public function canUserManage($publicationId, $userId) {
        require_once __DIR__ . '/Publication.php';
        $publicationModel = new Publication();
        
        return $publicationModel->hasPermission($publicationId, $userId, 'admin');
    }
}