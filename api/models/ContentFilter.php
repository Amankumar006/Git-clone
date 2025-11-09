<?php

class ContentFilter extends BaseRepository {
    protected $table = 'content_flags';
    
    private $spamKeywords = [
        'buy now', 'click here', 'free money', 'get rich quick', 'make money fast',
        'limited time offer', 'act now', 'guaranteed', 'no risk', 'cash prize'
    ];
    
    private $profanityWords = [
        // Add profanity words here - keeping minimal for demo
        'spam', 'scam', 'fake'
    ];

    public function scanContent($contentType, $contentId, $content) {
        try {
            $flags = [];
            
            // Check for spam
            $spamScore = $this->detectSpam($content);
            if ($spamScore > 0.5) {
                $flags[] = [
                    'type' => 'spam_detected',
                    'score' => $spamScore,
                    'action' => $spamScore > 0.8 ? 'auto_remove' : 'flag_for_review'
                ];
            }
            
            // Check for profanity
            $profanityScore = $this->detectProfanity($content);
            if ($profanityScore > 0.3) {
                $flags[] = [
                    'type' => 'profanity_detected',
                    'score' => $profanityScore,
                    'action' => 'flag_for_review'
                ];
            }
            
            // Check for suspicious links
            $linkScore = $this->detectSuspiciousLinks($content);
            if ($linkScore > 0.6) {
                $flags[] = [
                    'type' => 'suspicious_links',
                    'score' => $linkScore,
                    'action' => 'flag_for_review'
                ];
            }
            
            // Check for duplicate content
            $duplicateScore = $this->detectDuplicateContent($contentType, $content);
            if ($duplicateScore > 0.9) {
                $flags[] = [
                    'type' => 'duplicate_content',
                    'score' => $duplicateScore,
                    'action' => 'flag_for_review'
                ];
            }
            
            // Save flags and take actions
            foreach ($flags as $flag) {
                $this->createFlag($contentType, $contentId, $flag['type'], $flag['score'], $flag['action']);
                
                if ($flag['action'] === 'auto_remove') {
                    $this->autoRemoveContent($contentType, $contentId);
                } elseif ($flag['action'] === 'flag_for_review') {
                    $this->flagForReview($contentType, $contentId);
                }
            }
            
            return $flags;
        } catch (Exception $e) {
            error_log('Content filtering error: ' . $e->getMessage());
            return [];
        }
    }

    public function createFlag($contentType, $contentId, $flagType, $score, $autoAction) {
        try {
            $sql = "INSERT INTO {$this->table} (content_type, content_id, flag_type, confidence_score, auto_action) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    confidence_score = VALUES(confidence_score),
                    auto_action = VALUES(auto_action)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contentType, $contentId, $flagType, $score, $autoAction]);
            
            return $this->db->lastInsertId() ?: $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception('Failed to create content flag: ' . $e->getMessage());
        }
    }

    public function getFlaggedContent($reviewed = false, $limit = 20, $offset = 0) {
        $sql = "SELECT cf.*, 
                       CASE 
                           WHEN cf.content_type = 'article' THEN a.title
                           WHEN cf.content_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                       END as content_preview,
                       CASE 
                           WHEN cf.content_type = 'article' THEN u1.username
                           WHEN cf.content_type = 'comment' THEN u2.username
                       END as author_username
                FROM {$this->table} cf
                LEFT JOIN articles a ON cf.content_type = 'article' AND cf.content_id = a.id
                LEFT JOIN comments c ON cf.content_type = 'comment' AND cf.content_id = c.id
                LEFT JOIN users u1 ON a.author_id = u1.id
                LEFT JOIN users u2 ON c.user_id = u2.id
                WHERE cf.reviewed = ?
                ORDER BY cf.confidence_score DESC, cf.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reviewed ? 1 : 0, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function markFlagAsReviewed($flagId, $adminId) {
        try {
            $sql = "UPDATE {$this->table} SET reviewed = TRUE WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$flagId]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Failed to mark flag as reviewed: ' . $e->getMessage());
        }
    }

    public function getFilterStats() {
        $sql = "SELECT 
                    COUNT(*) as total_flags,
                    SUM(CASE WHEN flag_type = 'spam_detected' THEN 1 ELSE 0 END) as spam_flags,
                    SUM(CASE WHEN flag_type = 'profanity_detected' THEN 1 ELSE 0 END) as profanity_flags,
                    SUM(CASE WHEN flag_type = 'suspicious_links' THEN 1 ELSE 0 END) as link_flags,
                    SUM(CASE WHEN flag_type = 'duplicate_content' THEN 1 ELSE 0 END) as duplicate_flags,
                    SUM(CASE WHEN auto_action = 'auto_remove' THEN 1 ELSE 0 END) as auto_removed,
                    SUM(CASE WHEN reviewed = FALSE THEN 1 ELSE 0 END) as pending_review
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function detectSpam($content) {
        $content = strtolower($content);
        $spamCount = 0;
        $totalWords = str_word_count($content);
        
        foreach ($this->spamKeywords as $keyword) {
            if (strpos($content, strtolower($keyword)) !== false) {
                $spamCount++;
            }
        }
        
        // Calculate spam score based on keyword density
        $keywordDensity = $totalWords > 0 ? $spamCount / $totalWords : 0;
        
        // Additional spam indicators
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
        $capsRatio = $this->calculateCapsRatio($content);
        $exclamationCount = substr_count($content, '!');
        
        $spamScore = min(1.0, $keywordDensity * 2 + ($urlCount > 3 ? 0.3 : 0) + 
                       ($capsRatio > 0.5 ? 0.2 : 0) + ($exclamationCount > 5 ? 0.2 : 0));
        
        return round($spamScore, 2);
    }

    private function detectProfanity($content) {
        $content = strtolower($content);
        $profanityCount = 0;
        
        foreach ($this->profanityWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                $profanityCount++;
            }
        }
        
        $totalWords = str_word_count($content);
        return $totalWords > 0 ? round($profanityCount / $totalWords, 2) : 0;
    }

    private function detectSuspiciousLinks($content) {
        // Count URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/bit\.ly/', '/tinyurl\.com/', '/t\.co/', // URL shorteners
            '/\.tk/', '/\.ml/', '/\.ga/', // Suspicious TLDs
        ];
        
        $suspiciousCount = 0;
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $suspiciousCount++;
            }
        }
        
        $totalWords = str_word_count($content);
        $urlDensity = $totalWords > 0 ? $urlCount / $totalWords : 0;
        
        return min(1.0, $urlDensity * 3 + $suspiciousCount * 0.3);
    }

    private function detectDuplicateContent($contentType, $content) {
        try {
            // Simple duplicate detection based on content hash
            $contentHash = md5(trim(strtolower($content)));
            
            if ($contentType === 'article') {
                $sql = "SELECT COUNT(*) as count FROM articles WHERE MD5(LOWER(TRIM(content))) = ?";
            } else {
                $sql = "SELECT COUNT(*) as count FROM comments WHERE MD5(LOWER(TRIM(content))) = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contentHash]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0 ? 1.0 : 0.0;
        } catch (Exception $e) {
            return 0.0;
        }
    }

    private function calculateCapsRatio($content) {
        $totalChars = strlen(preg_replace('/[^a-zA-Z]/', '', $content));
        $capsChars = strlen(preg_replace('/[^A-Z]/', '', $content));
        
        return $totalChars > 0 ? $capsChars / $totalChars : 0;
    }

    private function autoRemoveContent($contentType, $contentId) {
        try {
            if ($contentType === 'article') {
                $sql = "UPDATE articles SET moderation_status = 'removed' WHERE id = ?";
            } elseif ($contentType === 'comment') {
                $sql = "UPDATE comments SET moderation_status = 'removed' WHERE id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contentId]);
        } catch (Exception $e) {
            error_log('Failed to auto-remove content: ' . $e->getMessage());
        }
    }

    private function flagForReview($contentType, $contentId) {
        try {
            if ($contentType === 'article') {
                $sql = "UPDATE articles SET moderation_status = 'flagged', flagged_at = CURRENT_TIMESTAMP WHERE id = ?";
            } elseif ($contentType === 'comment') {
                $sql = "UPDATE comments SET moderation_status = 'flagged', flagged_at = CURRENT_TIMESTAMP WHERE id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contentId]);
        } catch (Exception $e) {
            error_log('Failed to flag content for review: ' . $e->getMessage());
        }
    }
}