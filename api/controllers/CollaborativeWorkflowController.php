<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ArticleSubmission.php';
require_once __DIR__ . '/../models/ArticleRevision.php';
require_once __DIR__ . '/../models/PublicationTemplate.php';
require_once __DIR__ . '/../models/PublicationGuideline.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Publication.php';
require_once __DIR__ . '/../models/Notification.php';

class CollaborativeWorkflowController extends BaseController {
    private $submissionModel;
    private $revisionModel;
    private $templateModel;
    private $guidelineModel;
    private $articleModel;
    private $publicationModel;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->submissionModel = new ArticleSubmission();
        $this->revisionModel = new ArticleRevision();
        $this->templateModel = new PublicationTemplate();
        $this->guidelineModel = new PublicationGuideline();
        $this->articleModel = new Article();
        $this->publicationModel = new Publication();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Submit article to publication
     * POST /api/workflow/submit-article
     */
    public function submitArticle() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$articleId || !$publicationId) {
                return $this->sendError('Article ID and Publication ID are required', 400);
            }
            
            // Check if user owns the article
            $article = $this->articleModel->findById($articleId);
            if (!$article || $article['author_id'] != $this->currentUser['id']) {
                return $this->sendError('Article not found or permission denied', 404);
            }
            
            // Check if user can submit to publication
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'writer')) {
                return $this->sendError('You do not have permission to submit to this publication', 403);
            }
            
            // Submit article
            $submission = $this->submissionModel->submitArticle($articleId, $publicationId, $this->currentUser['id']);
            
            if ($submission) {
                // Create notification for publication admins
                $this->notifyPublicationAdmins($publicationId, $submission);
                
                return $this->sendResponse($submission, 'Article submitted successfully');
            } else {
                return $this->sendError('Failed to submit article or article already submitted', 400);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get pending submissions for publication
     * GET /api/workflow/pending-submissions?publication_id=:id
     */
    public function getPendingSubmissions() {
        try {
            $this->requireAuth();
            
            $publicationId = $_GET['publication_id'] ?? null;
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            // Check if user can review submissions
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'editor')) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $submissions = $this->submissionModel->getPendingSubmissions($publicationId, $limit, $offset);
            
            return $this->sendResponse([
                'submissions' => $submissions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Assign reviewer to submission
     * POST /api/workflow/assign-reviewer
     */
    public function assignReviewer() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $submissionId = $data['submission_id'] ?? null;
            $reviewerId = $data['reviewer_id'] ?? null;
            
            if (!$submissionId || !$reviewerId) {
                return $this->sendError('Submission ID and Reviewer ID are required', 400);
            }
            
            // Check if current user can assign reviewers
            $submission = $this->submissionModel->getById($submissionId);
            if (!$submission) {
                return $this->sendError('Submission not found', 404);
            }
            
            if (!$this->publicationModel->hasPermission($submission['publication_id'], $this->currentUser['id'], 'admin')) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            // Assign reviewer
            $result = $this->submissionModel->assignReviewer($submissionId, $reviewerId);
            
            if ($result) {
                // Notify reviewer
                $this->notificationModel->create([
                    'user_id' => $reviewerId,
                    'type' => 'review_assigned',
                    'content' => "You have been assigned to review '{$submission['article_title']}'",
                    'related_id' => $submissionId
                ]);
                
                return $this->sendResponse(null, 'Reviewer assigned successfully');
            } else {
                return $this->sendError('Failed to assign reviewer', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Approve submission
     * POST /api/workflow/approve-submission
     */
    public function approveSubmission() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $submissionId = $data['submission_id'] ?? null;
            $reviewNotes = $data['review_notes'] ?? null;
            
            if (!$submissionId) {
                return $this->sendError('Submission ID is required', 400);
            }
            
            // Check if user can approve
            if (!$this->submissionModel->canUserReview($submissionId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->submissionModel->approveSubmission($submissionId, $this->currentUser['id'], $reviewNotes);
            
            if ($result) {
                $submission = $this->submissionModel->getById($submissionId);
                
                // Notify author
                $this->notificationModel->create([
                    'user_id' => $submission['submitted_by'],
                    'type' => 'approved',
                    'content' => "Your article '{$submission['article_title']}' has been approved and published",
                    'related_id' => $submission['article_id']
                ]);
                
                return $this->sendResponse(null, 'Article approved and published successfully');
            } else {
                return $this->sendError('Failed to approve submission', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Reject submission
     * POST /api/workflow/reject-submission
     */
    public function rejectSubmission() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $submissionId = $data['submission_id'] ?? null;
            $reviewNotes = $data['review_notes'] ?? null;
            
            if (!$submissionId) {
                return $this->sendError('Submission ID is required', 400);
            }
            
            // Check if user can reject
            if (!$this->submissionModel->canUserReview($submissionId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->submissionModel->rejectSubmission($submissionId, $this->currentUser['id'], $reviewNotes);
            
            if ($result) {
                $submission = $this->submissionModel->getById($submissionId);
                
                // Notify author
                $this->notificationModel->create([
                    'user_id' => $submission['submitted_by'],
                    'type' => 'rejected',
                    'content' => "Your article '{$submission['article_title']}' has been rejected",
                    'related_id' => $submission['article_id']
                ]);
                
                return $this->sendResponse(null, 'Article rejected successfully');
            } else {
                return $this->sendError('Failed to reject submission', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Request revision
     * POST /api/workflow/request-revision
     */
    public function requestRevision() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $submissionId = $data['submission_id'] ?? null;
            $revisionNotes = $data['revision_notes'] ?? null;
            
            if (!$submissionId || !$revisionNotes) {
                return $this->sendError('Submission ID and revision notes are required', 400);
            }
            
            // Check if user can request revision
            if (!$this->submissionModel->canUserReview($submissionId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->submissionModel->requestRevision($submissionId, $this->currentUser['id'], $revisionNotes);
            
            if ($result) {
                $submission = $this->submissionModel->getById($submissionId);
                
                // Notify author
                $this->notificationModel->create([
                    'user_id' => $submission['submitted_by'],
                    'type' => 'revision_requested',
                    'content' => "Revision requested for your article '{$submission['article_title']}'",
                    'related_id' => $submission['article_id']
                ]);
                
                return $this->sendResponse(null, 'Revision requested successfully');
            } else {
                return $this->sendError('Failed to request revision', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create article revision
     * POST /api/workflow/create-revision
     */
    public function createRevision() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            $revisionData = $data['revision_data'] ?? null;
            $changeSummary = $data['change_summary'] ?? null;
            $isMajor = $data['is_major'] ?? false;
            
            if (!$articleId || !$revisionData) {
                return $this->sendError('Article ID and revision data are required', 400);
            }
            
            // Check if user can create revision
            if (!$this->revisionModel->canUserCreateRevision($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $revision = $this->revisionModel->createRevision(
                $articleId, 
                $revisionData, 
                $this->currentUser['id'], 
                $changeSummary, 
                $isMajor
            );
            
            if ($revision) {
                return $this->sendResponse($revision, 'Revision created successfully');
            } else {
                return $this->sendError('Failed to create revision', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get article revisions
     * GET /api/workflow/article-revisions?article_id=:id
     */
    public function getArticleRevisions() {
        try {
            $this->requireAuth();
            
            $articleId = $_GET['article_id'] ?? null;
            if (!$articleId) {
                return $this->sendError('Article ID is required', 400);
            }
            
            // Check if user can view revisions
            if (!$this->revisionModel->canUserCreateRevision($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $revisions = $this->revisionModel->getArticleRevisions($articleId, $limit, $offset);
            $stats = $this->revisionModel->getRevisionStats($articleId);
            $contributors = $this->revisionModel->getArticleContributors($articleId);
            
            return $this->sendResponse([
                'revisions' => $revisions,
                'stats' => $stats,
                'contributors' => $contributors,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Compare revisions
     * GET /api/workflow/compare-revisions?article_id=:id&from=:from&to=:to
     */
    public function compareRevisions() {
        try {
            $this->requireAuth();
            
            $articleId = $_GET['article_id'] ?? null;
            $fromRevision = $_GET['from'] ?? null;
            $toRevision = $_GET['to'] ?? null;
            
            if (!$articleId || !$fromRevision || !$toRevision) {
                return $this->sendError('Article ID, from revision, and to revision are required', 400);
            }
            
            // Check permissions
            if (!$this->revisionModel->canUserCreateRevision($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $comparison = $this->revisionModel->compareRevisions($articleId, $fromRevision, $toRevision);
            
            if ($comparison) {
                return $this->sendResponse($comparison);
            } else {
                return $this->sendError('Failed to compare revisions', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication templates
     * GET /api/workflow/templates?publication_id=:id
     */
    public function getPublicationTemplates() {
        try {
            $publicationId = $_GET['publication_id'] ?? null;
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            $templates = $this->templateModel->getByPublication($publicationId);
            $predefined = $this->templateModel->getPredefinedTemplates();
            
            return $this->sendResponse([
                'templates' => $templates,
                'predefined' => $predefined
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create publication template
     * POST /api/workflow/create-template
     */
    public function createTemplate() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            // Check permissions
            if (!$this->templateModel->canUserManage($publicationId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $data['created_by'] = $this->currentUser['id'];
            $template = $this->templateModel->create($data);
            
            if ($template) {
                return $this->sendResponse($template, 'Template created successfully');
            } else {
                return $this->sendError('Failed to create template', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication guidelines
     * GET /api/workflow/guidelines?publication_id=:id
     */
    public function getPublicationGuidelines() {
        try {
            $publicationId = $_GET['publication_id'] ?? null;
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            $category = $_GET['category'] ?? null;
            $grouped = $_GET['grouped'] ?? false;
            
            if ($grouped) {
                $guidelines = $this->guidelineModel->getByPublicationGrouped($publicationId);
            } else {
                $guidelines = $this->guidelineModel->getByPublication($publicationId, $category);
            }
            
            $summary = $this->guidelineModel->getWriterSummary($publicationId);
            $categories = $this->guidelineModel->getCategories();
            
            return $this->sendResponse([
                'guidelines' => $guidelines,
                'summary' => $summary,
                'categories' => $categories
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create publication guideline
     * POST /api/workflow/create-guideline
     */
    public function createGuideline() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            // Check permissions
            if (!$this->guidelineModel->canUserManage($publicationId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $data['created_by'] = $this->currentUser['id'];
            $guideline = $this->guidelineModel->create($data);
            
            if ($guideline) {
                return $this->sendResponse($guideline, 'Guideline created successfully');
            } else {
                return $this->sendError('Failed to create guideline', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Check article compliance with guidelines
     * POST /api/workflow/check-compliance
     */
    public function checkCompliance() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            $articleData = $data['article_data'] ?? null;
            
            if (!$publicationId || !$articleData) {
                return $this->sendError('Publication ID and article data are required', 400);
            }
            
            $compliance = $this->guidelineModel->checkCompliance($publicationId, $articleData);
            
            return $this->sendResponse($compliance);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's submissions
     * GET /api/workflow/my-submissions
     */
    public function getMySubmissions() {
        try {
            $this->requireAuth();
            
            $status = $_GET['status'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $submissions = $this->submissionModel->getUserSubmissions(
                $this->currentUser['id'], 
                $status, 
                $limit, 
                $offset
            );
            
            return $this->sendResponse([
                'submissions' => $submissions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get submission workflow history
     * GET /api/workflow/submission-history?submission_id=:id
     */
    public function getSubmissionHistory() {
        try {
            $this->requireAuth();
            
            $submissionId = $_GET['submission_id'] ?? null;
            if (!$submissionId) {
                return $this->sendError('Submission ID is required', 400);
            }
            
            // Check if user can view this submission
            $submission = $this->submissionModel->getById($submissionId);
            if (!$submission) {
                return $this->sendError('Submission not found', 404);
            }
            
            // Check permissions
            $canView = ($submission['submitted_by'] == $this->currentUser['id']) ||
                      $this->publicationModel->hasPermission($submission['publication_id'], $this->currentUser['id'], 'editor');
            
            if (!$canView) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $history = $this->submissionModel->getWorkflowHistory($submissionId);
            
            return $this->sendResponse([
                'submission' => $submission,
                'history' => $history
            ]);
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Resubmit after revision
     * POST /api/workflow/resubmit
     */
    public function resubmitAfterRevision() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $submissionId = $data['submission_id'] ?? null;
            
            if (!$submissionId) {
                return $this->sendError('Submission ID is required', 400);
            }
            
            // Check if user owns the submission
            $submission = $this->submissionModel->getById($submissionId);
            if (!$submission || $submission['submitted_by'] != $this->currentUser['id']) {
                return $this->sendError('Submission not found or permission denied', 404);
            }
            
            // Check if submission is in revision_requested status
            if ($submission['status'] !== 'revision_requested') {
                return $this->sendError('Submission is not in revision requested status', 400);
            }
            
            $result = $this->submissionModel->resubmitAfterRevision($submissionId);
            
            if ($result) {
                // Notify reviewers
                $this->notifyPublicationAdmins($submission['publication_id'], $submission);
                
                return $this->sendResponse(null, 'Article resubmitted successfully');
            } else {
                return $this->sendError('Failed to resubmit article', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Restore article to revision
     * POST /api/workflow/restore-revision
     */
    public function restoreRevision() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $articleId = $data['article_id'] ?? null;
            $revisionNumber = $data['revision_number'] ?? null;
            
            if (!$articleId || !$revisionNumber) {
                return $this->sendError('Article ID and revision number are required', 400);
            }
            
            // Check if user can restore revision
            if (!$this->revisionModel->canUserCreateRevision($articleId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->revisionModel->restoreToRevision($articleId, $revisionNumber, $this->currentUser['id']);
            
            if ($result) {
                return $this->sendResponse(null, 'Revision restored successfully');
            } else {
                return $this->sendError('Failed to restore revision', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update publication template
     * PUT /api/workflow/templates/:id
     */
    public function updateTemplate($templateId) {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            // Check permissions
            $template = $this->templateModel->getById($templateId);
            if (!$template) {
                return $this->sendError('Template not found', 404);
            }
            
            if (!$this->templateModel->canUserManage($template['publication_id'], $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $updatedTemplate = $this->templateModel->update($templateId, $data);
            
            if ($updatedTemplate) {
                return $this->sendResponse($updatedTemplate, 'Template updated successfully');
            } else {
                return $this->sendError('Failed to update template', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete publication template
     * DELETE /api/workflow/templates/:id
     */
    public function deleteTemplate($templateId) {
        try {
            $this->requireAuth();
            
            // Check permissions
            $template = $this->templateModel->getById($templateId);
            if (!$template) {
                return $this->sendError('Template not found', 404);
            }
            
            if (!$this->templateModel->canUserManage($template['publication_id'], $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->templateModel->delete($templateId);
            
            if ($result) {
                return $this->sendResponse(null, 'Template deleted successfully');
            } else {
                return $this->sendError('Failed to delete template', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update publication guideline
     * PUT /api/workflow/guidelines/:id
     */
    public function updateGuideline($guidelineId) {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            // Check permissions
            $guideline = $this->guidelineModel->getById($guidelineId);
            if (!$guideline) {
                return $this->sendError('Guideline not found', 404);
            }
            
            if (!$this->guidelineModel->canUserManage($guideline['publication_id'], $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $updatedGuideline = $this->guidelineModel->update($guidelineId, $data);
            
            if ($updatedGuideline) {
                return $this->sendResponse($updatedGuideline, 'Guideline updated successfully');
            } else {
                return $this->sendError('Failed to update guideline', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete publication guideline
     * DELETE /api/workflow/guidelines/:id
     */
    public function deleteGuideline($guidelineId) {
        try {
            $this->requireAuth();
            
            // Check permissions
            $guideline = $this->guidelineModel->getById($guidelineId);
            if (!$guideline) {
                return $this->sendError('Guideline not found', 404);
            }
            
            if (!$this->guidelineModel->canUserManage($guideline['publication_id'], $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->guidelineModel->delete($guidelineId);
            
            if ($result) {
                return $this->sendResponse(null, 'Guideline deleted successfully');
            } else {
                return $this->sendError('Failed to delete guideline', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create default guidelines for publication
     * POST /api/workflow/guidelines/create-defaults
     */
    public function createDefaultGuidelines() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->sendError('Publication ID is required', 400);
            }
            
            // Check permissions
            if (!$this->guidelineModel->canUserManage($publicationId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $guidelines = $this->guidelineModel->createDefaultGuidelines($publicationId, $this->currentUser['id']);
            
            if ($guidelines) {
                return $this->sendResponse($guidelines, 'Default guidelines created successfully');
            } else {
                return $this->sendError('Failed to create default guidelines', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Reorder guidelines
     * POST /api/workflow/guidelines/reorder
     */
    public function reorderGuidelines() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            $guidelines = $data['guidelines'] ?? null;
            
            if (!$publicationId || !$guidelines) {
                return $this->sendError('Publication ID and guidelines order are required', 400);
            }
            
            // Check permissions
            if (!$this->guidelineModel->canUserManage($publicationId, $this->currentUser['id'])) {
                return $this->sendError('Insufficient permissions', 403);
            }
            
            $result = $this->guidelineModel->reorder($publicationId, $guidelines);
            
            if ($result) {
                return $this->sendResponse(null, 'Guidelines reordered successfully');
            } else {
                return $this->sendError('Failed to reorder guidelines', 500);
            }
            
        } catch (Exception $e) {
            return $this->sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Notify publication admins about new submission
     */
    private function notifyPublicationAdmins($publicationId, $submission) {
        try {
            // Get publication admins and editors
            $members = $this->publicationModel->getMembers($publicationId);
            
            foreach ($members as $member) {
                if (in_array($member['role'], ['admin', 'editor'])) {
                    $this->notificationModel->create([
                        'user_id' => $member['id'],
                        'type' => 'submission_received',
                        'content' => "New article submission: '{$submission['article_title']}'",
                        'related_id' => $submission['id']
                    ]);
                }
            }
            
            // Also notify publication owner
            $publication = $this->publicationModel->getById($publicationId);
            if ($publication) {
                $this->notificationModel->create([
                    'user_id' => $publication['owner_id'],
                    'type' => 'submission_received',
                    'content' => "New article submission: '{$submission['article_title']}'",
                    'related_id' => $submission['id']
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Failed to notify publication admins: " . $e->getMessage());
        }
    }
}