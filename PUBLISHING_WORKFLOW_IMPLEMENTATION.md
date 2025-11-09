# Article Publishing Workflow Implementation

## Overview

This document describes the implementation of the article publishing workflow for the Medium-style publishing platform. The workflow includes publish confirmation dialog with preview functionality, article status management, SEO metadata generation, and URL slug generation with duplicate handling.

## Features Implemented

### 1. Publish Confirmation Dialog with Preview Functionality

**Location**: `frontend/src/components/PublishDialog.tsx`

**Features**:
- **Article Preview**: Shows title, subtitle, content preview, featured image, and tags
- **SEO Preview**: Displays how the article will appear in search results
- **Social Media Preview**: Shows how the article will look when shared on social platforms
- **Article URL Preview**: Displays the generated URL with slug
- **Full Article Preview**: Expandable full content preview
- **Publishing Options**: Configurable options for comments, search inclusion, and follower notifications
- **Loading States**: Shows loading indicators while fetching preview data
- **Real-time Updates**: Preview updates as article data changes

**Key Components**:
```typescript
interface PublishingOptions {
  allowComments: boolean;
  includeInSearch: boolean;
  notifyFollowers: boolean;
}
```

### 2. Article Status Management

**Location**: `api/models/Article.php`, `api/controllers/ArticleController.php`

**Supported Statuses**:
- `draft`: Article is being worked on, not publicly visible
- `published`: Article is live and publicly accessible
- `archived`: Article is hidden from public view but preserved

**Status Transitions**:
- Draft → Published (via publish workflow)
- Published → Draft (unpublish)
- Published → Archived (archive)
- Archived → Published (republish)
- Draft → Archived (archive draft)

**API Endpoints**:
```php
POST /api/articles/publish/{id}    // Publish article
POST /api/articles/unpublish/{id}  // Unpublish article
POST /api/articles/archive/{id}    // Archive article
GET  /api/articles/status?status=draft // Get articles by status
```

### 3. SEO Metadata Generation

**Location**: `api/controllers/ArticleController.php`, `frontend/src/utils/seo.ts`

**Generated Metadata**:
- **Basic Meta Tags**: title, description, keywords
- **Open Graph Tags**: og:title, og:description, og:image, og:url, og:type
- **Twitter Cards**: twitter:card, twitter:title, twitter:description, twitter:image
- **Canonical URL**: Prevents duplicate content issues
- **Structured Data**: JSON-LD markup for search engines

**SEO Features**:
```php
// Backend SEO generation
private function generateSEOMetadata($article) {
    return [
        'title' => $article['title'] . ' | Medium Clone',
        'description' => $this->generateMetaDescription($article),
        'keywords' => $this->extractKeywords($article),
        'canonical_url' => $articleUrl,
        'structured_data' => $this->generateStructuredData($article, $articleUrl)
    ];
}
```

**Frontend SEO Utilities**:
```typescript
// Generate complete SEO metadata
export const generateArticleSEO = (article) => {
    // Returns complete SEO metadata object
};

// Update document head with SEO data
export const updateDocumentSEO = (metadata) => {
    // Updates meta tags, canonical URL, structured data
};
```

### 4. URL Slug Generation and Duplicate Handling

**Location**: `api/models/Article.php`

**Slug Generation Process**:
1. Convert title to lowercase
2. Replace special characters with hyphens
3. Remove multiple consecutive hyphens
4. Check for duplicates
5. Append counter if duplicate exists

**Implementation**:
```php
private function generateUniqueSlug($title, $excludeId = null) {
    $baseSlug = $this->createSlug($title);
    $slug = $baseSlug;
    $counter = 1;

    while ($this->slugExists($slug, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}
```

**Database Schema**:
```sql
ALTER TABLE articles ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title;
CREATE INDEX idx_articles_slug ON articles(slug);
```

## API Endpoints

### Article Preview
```
GET /api/articles/preview/{id}
```
Returns comprehensive preview data including SEO metadata and social sharing information.

### Article Publishing
```
POST /api/articles/publish/{id}
Content-Type: application/json

{
  "allow_comments": true,
  "include_in_search": true,
  "notify_followers": true
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "article": { /* article data */ },
    "seo_metadata": { /* SEO data */ },
    "article_url": "https://example.com/article/article-slug"
  },
  "message": "Article published successfully"
}
```

## Frontend Integration

### Publishing Workflow in ArticleEditor

```typescript
const handlePublishConfirm = async (options: PublishingOptions) => {
  setIsPublishing(true);
  try {
    const publishedArticle = await publishDraft(options);
    setShowPublishDialog(false);
    onPublish?.(publishedArticle);
  } catch (error) {
    console.error('Failed to publish article:', error);
  } finally {
    setIsPublishing(false);
  }
};
```

### Draft Manager Integration

```typescript
const publishDraft = useCallback(async (options?: PublishingOptions) => {
  // Save draft first if not already saved
  if (!draft.id) {
    await saveDraft();
  }

  // Publish with options
  const response = await api.post(`/articles/publish/${draft.id}`, {
    allow_comments: options?.allowComments ?? true,
    include_in_search: options?.includeInSearch ?? true,
    notify_followers: options?.notifyFollowers ?? true
  });

  return response.data.data.article;
}, [draft, saveDraft]);
```

## Database Considerations

### Slug Column Migration

The implementation includes automatic migration handling:

```php
public function ensureSlugColumn() {
    if (!$this->hasSlugColumn()) {
        // Add slug column
        $sql = "ALTER TABLE articles ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title";
        // Create index
        // Update existing articles with slugs
    }
}
```

### Backward Compatibility

The code handles cases where the slug column doesn't exist yet:

```php
private function hasSlugColumn() {
    $sql = "SHOW COLUMNS FROM articles LIKE 'slug'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetch() !== false;
}
```

## Testing

### Unit Tests

**Slug Generation Test**:
```php
$testCases = [
    'Hello World' => 'hello-world',
    'Special Characters @#$%' => 'special-characters',
    'Multiple   Spaces' => 'multiple-spaces'
];
```

**SEO Metadata Test**:
- Validates all required SEO fields are generated
- Tests meta description truncation
- Verifies structured data format

### Integration Tests

**Publishing Workflow Test**:
- Tests complete publish flow from draft to published
- Validates SEO metadata generation
- Checks slug uniqueness
- Verifies notification system

## Security Considerations

### Input Validation
- Article titles are sanitized for slug generation
- Content is validated before publishing
- User permissions are checked for all operations

### SQL Injection Prevention
- All database queries use prepared statements
- Input parameters are properly escaped

### XSS Protection
- Content is properly escaped in preview displays
- Meta tags are sanitized before output

## Performance Optimizations

### Database Indexes
```sql
CREATE INDEX idx_articles_slug ON articles(slug);
CREATE INDEX idx_articles_status_published ON articles(status, published_at DESC);
```

### Caching Strategy
- SEO metadata can be cached after generation
- Slug uniqueness checks are optimized with indexes
- Preview data is cached on the frontend

## Error Handling

### Backend Error Handling
```php
try {
    $article = $this->articleModel->publish($id, $user['id'], $publishingOptions);
    // Success response
} catch (Exception $e) {
    $this->sendError('Failed to publish article', 500);
}
```

### Frontend Error Handling
```typescript
try {
  const publishedArticle = await publishDraft(options);
  // Success handling
} catch (error) {
  console.error('Failed to publish article:', error);
  // Error UI feedback
}
```

## Future Enhancements

### Planned Features
1. **Scheduled Publishing**: Allow articles to be published at a future date
2. **A/B Testing**: Test different titles and descriptions
3. **Analytics Integration**: Track SEO performance
4. **Social Media Auto-posting**: Automatically share to social platforms
5. **Content Optimization**: AI-powered SEO suggestions

### Scalability Considerations
1. **CDN Integration**: Serve static assets from CDN
2. **Database Sharding**: Distribute articles across multiple databases
3. **Caching Layer**: Implement Redis for frequently accessed data
4. **Search Engine**: Integrate Elasticsearch for better search performance

## Conclusion

The article publishing workflow implementation provides a comprehensive solution for content publishing with modern SEO practices, user-friendly interfaces, and robust backend architecture. The system is designed to be scalable, maintainable, and extensible for future enhancements.