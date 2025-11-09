<?php

require_once __DIR__ . '/BaseRepository.php';

class Publication extends BaseRepository {
    protected $table = 'publications';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new publication
     */
    public function create($data) {
        $sql = "INSERT INTO publications (name, description, logo_url, website_url, social_links, theme_color, custom_css, owner_id) 
                VALUES (:name, :description, :logo_url, :website_url, :social_links, :theme_color, :custom_css, :owner_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':logo_url' => $data['logo_url'] ?? null,
            ':website_url' => $data['website_url'] ?? null,
            ':social_links' => isset($data['social_links']) ? json_encode($data['social_links']) : null,
            ':theme_color' => $data['theme_color'] ?? '#3B82F6',
            ':custom_css' => $data['custom_css'] ?? null,
            ':owner_id' => $data['owner_id']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get publication by ID with owner information
     */
    public function getById($id) {
        $sql = "SELECT p.*, u.username as owner_username, u.profile_image_url as owner_avatar
                FROM publications p
                JOIN users u ON p.owner_id = u.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $publication = $stmt->fetch();
        
        if ($publication && $publication['social_links']) {
            $publication['social_links'] = json_decode($publication['social_links'], true);
        }
        
        return $publication;
    }
    
    /**
     * Update publication
     */
    public function update($id, $data) {
        $sql = "UPDATE publications 
                SET name = :name, description = :description, logo_url = :logo_url, 
                    website_url = :website_url, social_links = :social_links, 
                    theme_color = :theme_color, custom_css = :custom_css,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':logo_url' => $data['logo_url'] ?? null,
            ':website_url' => $data['website_url'] ?? null,
            ':social_links' => isset($data['social_links']) ? json_encode($data['social_links']) : null,
            ':theme_color' => $data['theme_color'] ?? '#3B82F6',
            ':custom_css' => $data['custom_css'] ?? null
        ]);
    }
    
    /**
     * Delete publication
     */
    public function delete($id) {
        $sql = "DELETE FROM publications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get publications owned by user
     */
    public function getByOwner($userId) {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM publication_members pm WHERE pm.publication_id = p.id) as member_count,
                       (SELECT COUNT(*) FROM articles a WHERE a.publication_id = p.id AND a.status = 'published') as article_count
                FROM publications p
                WHERE p.owner_id = :user_id
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get publications where user is a member
     */
    public function getByMember($userId) {
        $sql = "SELECT p.*, pm.role, u.username as owner_username,
                       (SELECT COUNT(*) FROM publication_members pm2 WHERE pm2.publication_id = p.id) as member_count,
                       (SELECT COUNT(*) FROM articles a WHERE a.publication_id = p.id AND a.status = 'published') as article_count
                FROM publications p
                JOIN publication_members pm ON p.id = pm.publication_id
                JOIN users u ON p.owner_id = u.id
                WHERE pm.user_id = :user_id
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user has permission for publication
     */
    public function hasPermission($publicationId, $userId, $requiredRole = 'writer') {
        // Owner always has full permissions
        $sql = "SELECT owner_id FROM publications WHERE id = :publication_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':publication_id' => $publicationId]);
        $publication = $stmt->fetch();
        
        if ($publication && $publication['owner_id'] == $userId) {
            return true;
        }
        
        // Check member role
        $sql = "SELECT role FROM publication_members 
                WHERE publication_id = :publication_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId
        ]);
        
        $member = $stmt->fetch();
        if (!$member) {
            return false;
        }
        
        // Role hierarchy: admin > editor > writer
        $roleHierarchy = ['writer' => 1, 'editor' => 2, 'admin' => 3];
        $userRole = $roleHierarchy[$member['role']] ?? 0;
        $requiredRoleLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userRole >= $requiredRoleLevel;
    }
    
    /**
     * Add member to publication
     */
    public function addMember($publicationId, $userId, $role = 'writer') {
        // Validate role
        if (!in_array($role, ['writer', 'editor', 'admin'])) {
            return false;
        }
        
        // Check if publication exists
        $publication = $this->getById($publicationId);
        if (!$publication) {
            return false;
        }
        
        // Don't allow owner to be added as member (they already have full access)
        if ($publication['owner_id'] == $userId) {
            return false;
        }
        
        $sql = "INSERT INTO publication_members (publication_id, user_id, role) 
                VALUES (:publication_id, :user_id, :role)
                ON DUPLICATE KEY UPDATE role = :role2";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId,
            ':role' => $role,
            ':role2' => $role
        ]);
    }
    
    /**
     * Remove member from publication
     */
    public function removeMember($publicationId, $userId) {
        $sql = "DELETE FROM publication_members 
                WHERE publication_id = :publication_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Get publication members
     */
    public function getMembers($publicationId) {
        $sql = "SELECT pm.role, pm.created_at as joined_at, 
                       u.id, u.username, u.email, u.bio, u.profile_image_url
                FROM publication_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.publication_id = :publication_id
                ORDER BY pm.role DESC, pm.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':publication_id' => $publicationId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update member role
     */
    public function updateMemberRole($publicationId, $userId, $role) {
        $sql = "UPDATE publication_members 
                SET role = :role 
                WHERE publication_id = :publication_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId,
            ':role' => $role
        ]);
    }
    
    /**
     * Get publication articles
     */
    public function getArticles($publicationId, $status = null, $limit = 20, $offset = 0) {
        $whereClause = "WHERE a.publication_id = :publication_id";
        $params = [':publication_id' => $publicationId];
        
        if ($status) {
            $whereClause .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        
        $sql = "SELECT a.*, u.username as author_username, u.profile_image_url as author_avatar
                FROM articles a
                JOIN users u ON a.author_id = u.id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get publication statistics
     */
    public function getStats($publicationId) {
        $sql = "SELECT 
                    COUNT(DISTINCT pm.user_id) as member_count,
                    COUNT(DISTINCT CASE WHEN a.status = 'published' THEN a.id END) as published_articles,
                    COUNT(DISTINCT CASE WHEN a.status = 'draft' THEN a.id END) as draft_articles,
                    COALESCE(SUM(a.view_count), 0) as total_views,
                    COALESCE(SUM(a.clap_count), 0) as total_claps,
                    COALESCE(SUM(a.comment_count), 0) as total_comments
                FROM publications p
                LEFT JOIN publication_members pm ON p.id = pm.publication_id
                LEFT JOIN articles a ON p.id = a.publication_id
                WHERE p.id = :publication_id
                GROUP BY p.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':publication_id' => $publicationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get publication submission statistics
     */
    public function getSubmissionStats($publicationId) {
        $sql = "SELECT 
                    COUNT(CASE WHEN a.status = 'draft' AND a.publication_id = ? THEN 1 END) as pending_submissions,
                    COUNT(CASE WHEN a.status = 'published' AND a.publication_id = ? THEN 1 END) as published_articles,
                    COUNT(CASE WHEN a.status = 'archived' AND a.publication_id = ? THEN 1 END) as archived_articles,
                    COUNT(DISTINCT CASE WHEN a.publication_id = ? THEN a.author_id END) as unique_contributors
                FROM articles a
                WHERE a.publication_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId, $publicationId, $publicationId, $publicationId, $publicationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get recent activity for publication
     */
    public function getRecentActivity($publicationId, $limit = 10) {
        $sql = "SELECT 
                    'article_submitted' as activity_type,
                    a.id as article_id,
                    a.title as article_title,
                    u.username as author_username,
                    a.created_at as activity_date
                FROM articles a
                JOIN users u ON a.author_id = u.id
                WHERE a.publication_id = ? AND a.status = 'draft'
                
                UNION ALL
                
                SELECT 
                    'article_published' as activity_type,
                    a.id as article_id,
                    a.title as article_title,
                    u.username as author_username,
                    a.published_at as activity_date
                FROM articles a
                JOIN users u ON a.author_id = u.id
                WHERE a.publication_id = ? AND a.status = 'published'
                
                UNION ALL
                
                SELECT 
                    'member_joined' as activity_type,
                    pm.user_id as article_id,
                    CONCAT(u.username, ' joined as ', pm.role) as article_title,
                    u.username as author_username,
                    pm.created_at as activity_date
                FROM publication_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.publication_id = ?
                
                ORDER BY activity_date DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId, $publicationId, $publicationId, (int)$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Search publications
     */
    public function search($query, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username as owner_username,
                       (SELECT COUNT(*) FROM publication_members pm WHERE pm.publication_id = p.id) as member_count,
                       (SELECT COUNT(*) FROM articles a WHERE a.publication_id = p.id AND a.status = 'published') as article_count
                FROM publications p
                JOIN users u ON p.owner_id = u.id
                WHERE p.name LIKE :query1 OR p.description LIKE :query2
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query1', "%$query%");
        $stmt->bindValue(':query2', "%$query%");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Follow a publication
     */
    public function followPublication($publicationId, $userId) {
        $sql = "INSERT IGNORE INTO publication_follows (publication_id, user_id) 
                VALUES (:publication_id, :user_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Unfollow a publication
     */
    public function unfollowPublication($publicationId, $userId) {
        $sql = "DELETE FROM publication_follows 
                WHERE publication_id = :publication_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Check if user is following publication
     */
    public function isFollowing($publicationId, $userId) {
        $sql = "SELECT 1 FROM publication_follows 
                WHERE publication_id = :publication_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':publication_id' => $publicationId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get publication followers count
     */
    public function getFollowersCount($publicationId) {
        $sql = "SELECT COUNT(*) as count FROM publication_follows 
                WHERE publication_id = :publication_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':publication_id' => $publicationId]);
        
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get publications followed by user
     */
    public function getFollowedByUser($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username as owner_username,
                       (SELECT COUNT(*) FROM publication_members pm WHERE pm.publication_id = p.id) as member_count,
                       (SELECT COUNT(*) FROM articles a WHERE a.publication_id = p.id AND a.status = 'published') as article_count,
                       pf.created_at as followed_at
                FROM publications p
                JOIN users u ON p.owner_id = u.id
                JOIN publication_follows pf ON p.id = pf.publication_id
                WHERE pf.user_id = :user_id
                ORDER BY pf.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get articles from followed publications
     */
    public function getFollowedPublicationsArticles($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT a.*, u.username as author_username, u.profile_image_url as author_avatar,
                       p.name as publication_name, p.logo_url as publication_logo
                FROM articles a
                JOIN users u ON a.author_id = u.id
                JOIN publications p ON a.publication_id = p.id
                JOIN publication_follows pf ON p.id = pf.publication_id
                WHERE pf.user_id = :user_id AND a.status = 'published'
                ORDER BY a.published_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get filtered articles for publication with search and sorting
     */
    public function getFilteredArticles($publicationId, $filters = [], $limit = 20, $offset = 0) {
        $whereClause = "WHERE a.publication_id = :publication_id";
        $params = [':publication_id' => $publicationId];
        
        // Status filter
        if (!empty($filters['status'])) {
            $whereClause .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // Default to published articles for public view
            $whereClause .= " AND a.status = 'published'";
        }
        
        // Search query
        if (!empty($filters['search'])) {
            $whereClause .= " AND (a.title LIKE :search OR a.subtitle LIKE :search2)";
            $params[':search'] = "%{$filters['search']}%";
            $params[':search2'] = "%{$filters['search']}%";
        }
        
        // Author filter
        if (!empty($filters['author_id'])) {
            $whereClause .= " AND a.author_id = :author_id";
            $params[':author_id'] = $filters['author_id'];
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND a.published_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND a.published_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // Tag filter
        if (!empty($filters['tag'])) {
            $whereClause .= " AND EXISTS (
                SELECT 1 FROM article_tags at 
                JOIN tags t ON at.tag_id = t.id 
                WHERE at.article_id = a.id AND t.slug = :tag
            )";
            $params[':tag'] = $filters['tag'];
        }
        
        // Sort options
        $orderBy = "ORDER BY a.published_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popular':
                    $orderBy = "ORDER BY a.clap_count DESC, a.view_count DESC";
                    break;
                case 'views':
                    $orderBy = "ORDER BY a.view_count DESC";
                    break;
                case 'claps':
                    $orderBy = "ORDER BY a.clap_count DESC";
                    break;
                case 'comments':
                    $orderBy = "ORDER BY a.comment_count DESC";
                    break;
                case 'oldest':
                    $orderBy = "ORDER BY a.published_at ASC";
                    break;
                default:
                    $orderBy = "ORDER BY a.published_at DESC";
            }
        }
        
        $sql = "SELECT a.*, u.username as author_username, u.profile_image_url as author_avatar,
                       p.name as publication_name, p.logo_url as publication_logo,
                       (SELECT GROUP_CONCAT(t.name) FROM article_tags at 
                        JOIN tags t ON at.tag_id = t.id 
                        WHERE at.article_id = a.id) as tags
                FROM articles a
                JOIN users u ON a.author_id = u.id
                JOIN publications p ON a.publication_id = p.id
                $whereClause
                $orderBy
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}