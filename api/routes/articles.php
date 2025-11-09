<?php

require_once __DIR__ . '/../controllers/ArticleController.php';
require_once __DIR__ . '/../controllers/TagController.php';
require_once __DIR__ . '/../controllers/UploadController.php';

$articleController = new ArticleController();
$tagController = new TagController();
$uploadController = new UploadController();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Remove 'api' from path parts if present
if ($pathParts[0] === 'api') {
    array_shift($pathParts);
}

// Article routes
if ($pathParts[0] === 'articles') {
    switch ($method) {
        case 'GET':
            if (isset($pathParts[1])) {
                if ($pathParts[1] === 'drafts') {
                    $articleController->drafts();
                } elseif ($pathParts[1] === 'trending') {
                    $articleController->trending();
                } elseif ($pathParts[1] === 'search') {
                    $articleController->search();
                } elseif ($pathParts[1] === 'status') {
                    $articleController->getByStatus();
                } elseif ($pathParts[1] === 'preview' && isset($pathParts[2])) {
                    $_GET['id'] = $pathParts[2];
                    $articleController->preview();
                } elseif ($pathParts[1] === 'related') {
                    $articleController->related();
                } elseif ($pathParts[1] === 'recommended') {
                    $articleController->recommended();
                } elseif ($pathParts[1] === 'more-from-author') {
                    $articleController->moreFromAuthor();
                } elseif ($pathParts[1] === 'popular') {
                    $articleController->popular();
                } elseif ($pathParts[1] === 'pending-approval') {
                    $articleController->getPendingApproval();
                } elseif ($pathParts[1] === 'analytics' && isset($pathParts[2])) {
                    $_GET['id'] = $pathParts[2];
                    $articleController->getAnalytics();
                } elseif (is_numeric($pathParts[1])) {
                    $_GET['id'] = $pathParts[1];
                    $articleController->show();
                }
            } else {
                $articleController->index();
            }
            break;
            
        case 'POST':
            if (isset($pathParts[1])) {
                if ($pathParts[1] === 'publish' && isset($pathParts[2])) {
                    $_GET['id'] = $pathParts[2];
                    $articleController->publish();
                } elseif ($pathParts[1] === 'unpublish' && isset($pathParts[2])) {
                    $_GET['id'] = $pathParts[2];
                    $articleController->unpublish();
                } elseif ($pathParts[1] === 'archive' && isset($pathParts[2])) {
                    $_GET['id'] = $pathParts[2];
                    $articleController->archive();
                } elseif ($pathParts[1] === 'track-view') {
                    $articleController->trackView();
                } elseif ($pathParts[1] === 'track-read') {
                    $articleController->trackRead();
                } elseif ($pathParts[1] === 'analytics') {
                    $articleController->analytics();
                } elseif ($pathParts[1] === 'submit-to-publication') {
                    $articleController->submitToPublication();
                } elseif ($pathParts[1] === 'approve') {
                    $articleController->approveArticle();
                } elseif ($pathParts[1] === 'reject') {
                    $articleController->rejectArticle();
                }
            } else {
                $articleController->create();
            }
            break;
            
        case 'PUT':
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                $_GET['id'] = $pathParts[1];
                $articleController->update();
            }
            break;
            
        case 'DELETE':
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                $_GET['id'] = $pathParts[1];
                $articleController->delete();
            }
            break;
    }
}

// Tag routes
elseif ($pathParts[0] === 'tags') {
    switch ($method) {
        case 'GET':
            if (isset($pathParts[1])) {
                if ($pathParts[1] === 'popular') {
                    $tagController->popular();
                } elseif ($pathParts[1] === 'trending') {
                    $tagController->trending();
                } elseif ($pathParts[1] === 'search') {
                    $tagController->search();
                } elseif ($pathParts[1] === 'suggestions') {
                    $tagController->suggestions();
                } elseif ($pathParts[1] === 'related') {
                    $tagController->related();
                } elseif (is_numeric($pathParts[1])) {
                    $_GET['id'] = $pathParts[1];
                    $tagController->show();
                } else {
                    // Treat as slug
                    $_GET['slug'] = $pathParts[1];
                    $tagController->show();
                }
            } else {
                $tagController->index();
            }
            break;
            
        case 'POST':
            $tagController->create();
            break;
            
        case 'PUT':
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                $_GET['id'] = $pathParts[1];
                $tagController->update();
            }
            break;
            
        case 'DELETE':
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                $_GET['id'] = $pathParts[1];
                $tagController->delete();
            }
            break;
    }
}

// Upload routes
elseif ($pathParts[0] === 'upload') {
    switch ($method) {
        case 'POST':
            if (isset($pathParts[1])) {
                if ($pathParts[1] === 'image') {
                    $uploadController->uploadImage();
                } elseif ($pathParts[1] === 'featured-image') {
                    $uploadController->uploadFeaturedImage();
                }
            }
            break;
            
        case 'DELETE':
            if (isset($pathParts[1]) && $pathParts[1] === 'image') {
                $uploadController->deleteImage();
            }
            break;
    }
}