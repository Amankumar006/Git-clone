# Publication System Tests Documentation

This document provides comprehensive documentation for the publication system tests, covering all aspects of publication creation, management, member invitation, role management, article submission workflow, and public pages.

## Test Coverage Overview

The publication system tests are organized into several test files that cover different aspects of the system:

### 1. Backend Tests

#### PublicationSystemTest.php
- **Publication Creation and Management**
  - Publication creation with validation
  - Publication updates and branding
  - Publication deletion and ownership
  - Publication retrieval methods

- **Member Management**
  - Member invitation system
  - Role assignment and updates (writer, editor, admin)
  - Member removal
  - Permission validation

- **Article Submission Workflow**
  - Article submission to publications
  - Article approval process
  - Article rejection handling
  - Pending articles management

- **Publication Statistics**
  - Member count tracking
  - Article statistics (published, draft, archived)
  - Engagement metrics (views, claps, comments)
  - Activity tracking

- **Publication Following**
  - Follow/unfollow functionality
  - Followers count tracking
  - Followed publications feed

- **Search and Discovery**
  - Publication search by name and description
  - Search result structure validation
  - Pagination support

- **Edge Cases and Error Handling**
  - Invalid input validation
  - Non-existent resource handling
  - Permission boundary testing
  - Database constraint validation

#### PublicationControllerTest.php
- **API Endpoint Testing**
  - Authentication requirements
  - Authorization validation
  - Request/response format validation
  - Error response handling

- **CRUD Operations**
  - Create publication endpoint
  - Get publication endpoint
  - Update publication endpoint
  - Delete publication endpoint

- **Member Management APIs**
  - Invite member endpoint
  - Update member role endpoint
  - Remove member endpoint
  - Get members endpoint

- **Publication Following APIs**
  - Follow publication endpoint
  - Unfollow publication endpoint
  - Get followed publications endpoint

- **Article Management APIs**
  - Get publication articles endpoint
  - Get filtered articles endpoint
  - Article submission endpoints

- **Search and Discovery APIs**
  - Search publications endpoint
  - Query validation
  - Result formatting

### 2. Frontend Tests

#### PublicationManagement.test.tsx
- **PublicationForm Component**
  - Form rendering and validation
  - Create vs edit mode handling
  - Input validation and error display
  - Form submission and API integration
  - Cancel functionality

- **PublicationDashboard Component**
  - Dashboard data display
  - Statistics visualization
  - Recent activity feed
  - Loading and error states
  - Responsive design

- **PublicationMemberList Component**
  - Member list display
  - Role management interface
  - Member invitation form
  - Member removal confirmation
  - Permission-based UI rendering

#### PublicationWorkflow.test.tsx
- **ArticleSubmissionDialog Component**
  - Publication selection interface
  - User role display
  - Submission validation
  - Error handling
  - Dialog state management

- **PendingArticlesList Component**
  - Pending articles display
  - Approval/rejection actions
  - Article metadata display
  - Empty state handling
  - Loading states

- **PublishDialog Integration**
  - Publication selection in publish flow
  - Independent publishing option
  - Publication-specific publishing
  - Workflow integration

## Test Execution

### Running Backend Tests

```bash
# Run all publication tests
php api/tests/PublicationTestRunner.php

# Run individual test files
php api/tests/PublicationSystemTest.php
php api/tests/PublicationControllerTest.php
```

### Running Frontend Tests

```bash
# Run all publication component tests
npm test -- --testPathPattern=Publication

# Run specific test files
npm test PublicationManagement.test.tsx
npm test PublicationWorkflow.test.tsx
```

## Test Data Management

### Test Database Setup
- Tests use isolated test data to prevent interference
- Each test creates and cleans up its own test data
- Database transactions ensure data isolation
- Test users and publications are created with unique identifiers

### Mock Data Structure
```php
// Test User Data
$userData = [
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'testpass123'
];

// Test Publication Data
$publicationData = [
    'name' => 'Test Publication ' . time(),
    'description' => 'Test description',
    'logo_url' => 'https://example.com/logo.png',
    'owner_id' => $testUserId
];

// Test Article Data
$articleData = [
    'author_id' => $testUserId,
    'title' => 'Test Article',
    'content' => 'Test content',
    'status' => 'draft'
];
```

## Key Test Scenarios

### 1. Publication Creation and Management
- ✅ Valid publication creation
- ✅ Publication name validation
- ✅ Owner assignment
- ✅ Publication updates
- ✅ Branding customization
- ✅ Publication deletion

### 2. Member Invitation and Role Management
- ✅ Email-based member invitation
- ✅ Role assignment (writer, editor, admin)
- ✅ Role hierarchy validation
- ✅ Member role updates
- ✅ Member removal
- ✅ Owner privilege protection

### 3. Article Submission Workflow
- ✅ Article submission to publication
- ✅ Pending approval queue
- ✅ Article approval process
- ✅ Article rejection handling
- ✅ Publication-specific article display
- ✅ Collaborative editing permissions

### 4. Publication Public Pages and Branding
- ✅ Publication homepage display
- ✅ Article archive with filtering
- ✅ Member directory
- ✅ Publication statistics
- ✅ Custom branding (logo, description)
- ✅ Recent activity feed

### 5. Permission System
- ✅ Owner permissions (full access)
- ✅ Admin permissions (member management)
- ✅ Editor permissions (content management)
- ✅ Writer permissions (article submission)
- ✅ Non-member restrictions
- ✅ Permission boundary testing

### 6. Following System
- ✅ Publication following/unfollowing
- ✅ Followers count tracking
- ✅ Followed publications feed
- ✅ Publication discovery

### 7. Search and Discovery
- ✅ Publication search by name
- ✅ Publication search by description
- ✅ Search result formatting
- ✅ Pagination support
- ✅ Empty result handling

## Error Handling Test Cases

### Validation Errors
- Empty publication name
- Invalid email format
- Invalid role assignment
- Duplicate member invitation
- Owner as member prevention

### Not Found Errors
- Non-existent publication access
- Non-existent user invitation
- Non-existent article submission
- Invalid publication ID

### Permission Errors
- Unauthorized publication access
- Insufficient role permissions
- Non-member action attempts
- Owner privilege violations

### Database Constraint Errors
- Duplicate publication names
- Foreign key violations
- Invalid status transitions
- Constraint validation

## Performance Considerations

### Database Optimization
- Proper indexing on frequently queried columns
- Efficient JOIN operations for member and article queries
- Pagination for large result sets
- Query optimization for statistics

### API Response Times
- Cached publication data for frequent access
- Optimized member list queries
- Efficient article filtering
- Minimal data transfer

## Security Testing

### Authentication
- JWT token validation
- Session management
- Token expiration handling
- Unauthorized access prevention

### Authorization
- Role-based access control
- Permission boundary enforcement
- Owner privilege protection
- Member action restrictions

### Input Validation
- SQL injection prevention
- XSS protection
- File upload validation
- Data sanitization

## Integration Testing

### Workflow Integration
- End-to-end article submission flow
- Complete member management workflow
- Publication creation to article publishing
- User journey testing

### API Integration
- Frontend-backend communication
- Error propagation
- State synchronization
- Real-time updates

## Maintenance and Updates

### Adding New Tests
1. Identify the feature or bug fix
2. Create appropriate test cases
3. Follow existing test patterns
4. Update documentation
5. Ensure test isolation

### Test Data Cleanup
- Automatic cleanup in test teardown
- Manual cleanup for failed tests
- Database state verification
- Resource leak prevention

### Continuous Integration
- Automated test execution
- Test result reporting
- Coverage analysis
- Performance monitoring

## Requirements Mapping

This test suite validates the following requirements from the specification:

### Requirement 7.1: Publication Creation and Management
- ✅ Publication creation with name, description, and branding
- ✅ Owner assignment and management
- ✅ Publication settings and customization

### Requirement 7.3: Article Submission and Approval Workflow
- ✅ Article submission to publications
- ✅ Approval workflow for publication admins
- ✅ Rejection handling and feedback
- ✅ Collaborative editing features

### Requirement 7.4: Publication Public Pages and Branding
- ✅ Publication homepage with branding
- ✅ Article archive with filtering and search
- ✅ Member directory and profiles
- ✅ Publication following and subscription features

The test suite ensures comprehensive coverage of all publication system functionality, providing confidence in the system's reliability, security, and performance.