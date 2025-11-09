# Publication System Tests Documentation

This document describes the comprehensive test suite for the publication system, covering both backend API functionality and frontend component behavior.

## Backend Tests (PHP)

### Test Files
- `PublicationSystemTest.php` - Main test class with comprehensive publication system tests
- `PublicationTestRunner.php` - Test runner script for executing publication tests

### Test Coverage

#### 1. Publication Management Tests
- **Publication Creation**: Tests the creation of new publications with proper data validation
- **Publication Update**: Verifies publication information can be updated correctly
- **Publication Retrieval**: Tests various methods for retrieving publication data
- **Publication Deletion**: Ensures publications can be deleted with proper cleanup

#### 2. Member Management Tests
- **Member Invitation**: Tests adding new members to publications with different roles
- **Role Management**: Verifies member roles can be updated (writer → editor → admin)
- **Member Removal**: Tests removing members from publications
- **Permission Validation**: Ensures proper permission checks for member operations

#### 3. Article Workflow Tests
- **Article Submission**: Tests submitting articles to publications for review
- **Article Approval**: Verifies the approval process for submitted articles
- **Article Rejection**: Tests rejecting submitted articles and cleanup
- **Publication Assignment**: Ensures articles are properly associated with publications

#### 4. Permission System Tests
- **Owner Permissions**: Tests that publication owners have full access
- **Member Permissions**: Verifies role-based permissions (admin, editor, writer)
- **Non-member Permissions**: Ensures non-members cannot access restricted features
- **Article Management Permissions**: Tests who can manage articles in publication context

#### 5. Statistics and Analytics Tests
- **Publication Statistics**: Tests retrieval of publication metrics
- **Member Count Tracking**: Verifies accurate member counting
- **Article Count Tracking**: Tests published vs draft article counting
- **Engagement Metrics**: Tests view, clap, and comment aggregation

### Running Backend Tests

```bash
# Run all publication tests
php api/tests/PublicationTestRunner.php

# Run specific test class
php api/tests/PublicationSystemTest.php
```

### Test Data Management
- Tests create temporary test data (users, publications, articles)
- All test data is automatically cleaned up after test completion
- Tests use unique identifiers to avoid conflicts with existing data

## Frontend Tests (React/TypeScript)

### Test Files
- `PublicationCard.test.tsx` - Tests for the publication card component
- `PublicationForm.test.tsx` - Tests for the publication creation/editing form

### Test Coverage

#### 1. PublicationCard Component Tests
- **Rendering**: Tests proper display of publication information
- **Logo Handling**: Tests both custom logos and fallback display
- **Link Generation**: Verifies correct routing links are generated
- **Manage Button**: Tests conditional display of management controls
- **Data Formatting**: Tests proper formatting of counts and dates

#### 2. PublicationForm Component Tests
- **Form Validation**: Tests required field validation and length limits
- **Data Submission**: Verifies form data is properly formatted and submitted
- **Image Upload**: Tests logo upload and removal functionality
- **Loading States**: Tests proper display of loading indicators
- **Error Handling**: Tests error display and clearing behavior

### Running Frontend Tests

```bash
# Run all tests
npm test

# Run publication-specific tests
npm test -- --testPathPattern=Publication

# Run with coverage
npm test -- --coverage --testPathPattern=Publication
```

## Test Scenarios Covered

### 1. Complete Publication Lifecycle
1. Create publication with owner
2. Add members with different roles
3. Submit articles for review
4. Approve/reject articles
5. Update publication settings
6. Remove members
7. Delete publication

### 2. Permission Edge Cases
- Owner trying to remove themselves
- Non-members attempting restricted actions
- Role hierarchy enforcement
- Cross-publication permission isolation

### 3. Data Integrity
- Cascading deletes when publications are removed
- Article status consistency during workflow
- Member role consistency
- Statistics accuracy

### 4. User Interface Behavior
- Form validation and error display
- Loading states during async operations
- Responsive design elements
- Accessibility compliance

## Test Environment Setup

### Database Requirements
- Test database with all required tables
- Proper foreign key constraints
- Indexes for performance testing

### Dependencies
- PHPUnit for backend testing framework
- Jest and React Testing Library for frontend tests
- Mock implementations for external services

## Continuous Integration

These tests are designed to be run in CI/CD pipelines:

1. **Pre-commit**: Run quick validation tests
2. **Pull Request**: Run full test suite
3. **Deployment**: Run integration tests against staging environment

## Test Maintenance

### Adding New Tests
1. Follow existing naming conventions
2. Include both positive and negative test cases
3. Clean up test data properly
4. Document any special setup requirements

### Updating Tests
1. Update tests when API contracts change
2. Maintain backward compatibility where possible
3. Update documentation when test coverage changes

## Performance Considerations

- Tests use transactions where possible for faster cleanup
- Mock external dependencies to avoid network calls
- Use minimal test data sets for faster execution
- Parallel test execution where safe

## Security Testing

The test suite includes security-focused tests:
- SQL injection prevention
- Authorization bypass attempts
- Input validation edge cases
- Cross-site scripting prevention

## Troubleshooting

### Common Issues
1. **Database Connection**: Ensure test database is accessible
2. **Permission Errors**: Check file permissions for test data
3. **Timeout Issues**: Increase timeout for slow operations
4. **Memory Limits**: Adjust PHP memory limits for large test suites

### Debug Mode
Enable debug output by setting environment variables:
```bash
export TEST_DEBUG=1
php api/tests/PublicationTestRunner.php
```

This comprehensive test suite ensures the publication system is robust, secure, and user-friendly across all supported use cases.