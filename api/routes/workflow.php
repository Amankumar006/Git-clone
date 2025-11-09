<?php

require_once __DIR__ . '/../controllers/CollaborativeWorkflowController.php';

$controller = new CollaborativeWorkflowController();

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/workflow', '', $path);

// Route the request
switch ($method) {
    case 'GET':
        switch ($path) {
            case '/pending-submissions':
                $controller->getPendingSubmissions();
                break;
            case '/article-revisions':
                $controller->getArticleRevisions();
                break;
            case '/compare-revisions':
                $controller->compareRevisions();
                break;
            case '/templates':
                $controller->getPublicationTemplates();
                break;
            case '/guidelines':
                $controller->getPublicationGuidelines();
                break;
            case '/my-submissions':
                $controller->getMySubmissions();
                break;
            case '/submission-history':
                $controller->getSubmissionHistory();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'POST':
        switch ($path) {
            case '/submit-article':
                $controller->submitArticle();
                break;
            case '/assign-reviewer':
                $controller->assignReviewer();
                break;
            case '/approve-submission':
                $controller->approveSubmission();
                break;
            case '/reject-submission':
                $controller->rejectSubmission();
                break;
            case '/request-revision':
                $controller->requestRevision();
                break;
            case '/resubmit':
                $controller->resubmitAfterRevision();
                break;
            case '/create-revision':
                $controller->createRevision();
                break;
            case '/restore-revision':
                $controller->restoreRevision();
                break;
            case '/create-template':
                $controller->createTemplate();
                break;
            case '/create-guideline':
                $controller->createGuideline();
                break;
            case '/guidelines/create-defaults':
                $controller->createDefaultGuidelines();
                break;
            case '/guidelines/reorder':
                $controller->reorderGuidelines();
                break;
            case '/check-compliance':
                $controller->checkCompliance();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'PUT':
        switch ($path) {
            default:
                if (preg_match('/^\/templates\/(\d+)$/', $path, $matches)) {
                    $controller->updateTemplate($matches[1]);
                } elseif (preg_match('/^\/guidelines\/(\d+)$/', $path, $matches)) {
                    $controller->updateGuideline($matches[1]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                }
                break;
        }
        break;
        
    case 'DELETE':
        switch ($path) {
            default:
                if (preg_match('/^\/templates\/(\d+)$/', $path, $matches)) {
                    $controller->deleteTemplate($matches[1]);
                } elseif (preg_match('/^\/guidelines\/(\d+)$/', $path, $matches)) {
                    $controller->deleteGuideline($matches[1]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                }
                break;
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}