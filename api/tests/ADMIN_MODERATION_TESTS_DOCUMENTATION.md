# Admin and Moderation System Tests Documentation

## Overview

This document describes the comprehensive test suite for the admin and moderation system of the Medium Clone platform. The tests cover all aspects of content moderation, security monitoring, audit logging, system health, and platform analytics.

## Test Structure

### Test Files

1. **AdminModerationTest.php** - Main test class containing all test methods
2. **AdminModerationTestRunner.php** - Test runner script for command-line execution
3. **ADMIN_MODERATION_TESTS_DOCUMENTATION.md** - This documentation file

### Test Categories

#### 1. Content Reporting System Tests
- **Purpose**: Verify the content reporting functionality works correctly
- **Tests**:
  - Create content reports for articles, comments, and users
  - Retrieve pending reports with pagination
  - Update report status (pending → reviewing → resolved/dismissed)
  - Generate report statistics and analytics
- **Models Tested**: `Report`

#### 2. Moderation Actions Tests
- **Purpose**: Ensure moderation actions are properly logged and executed
- **Tests**:
  - Log various moderation actions (approve, remove, warn, suspend, ban)
  - Approve and remove content with proper status updates
  - Issue user warnings and suspensions
  - Retrieve moderation history and statistics
- **Models Tested**: `ModerationAction`

#### 3. Content Filtering Tests
- **Purpose**: Validate automated content filtering and flagging
- **Tests**:
  - Scan content for spam, profanity, and suspicious links
  - Detect duplicate content
  - Flag content for manual review
  - Mark flags as reviewed by moderators
  - Generate filtering statistics
- **Models Tested**: `ContentFilter`

#### 4. Security Monitoring Tests
- **Purpose**: Test security event logging and threat detection
- **Tests**:
  - Log various security events (failed logins, unauthorized access, etc.)
  - Detect suspicious login patterns from IP addresses
  - Block and unblock IP addresses with expiration times
  - Generate security reports and risk assessments
  - Clean up expired security data
- **Models Tested**: `SecurityMonitor`

#### 5. Audit Logging Tests
- **Purpose**: Verify comprehensive audit trail for admin actions
- **Tests**:
  - Log admin actions with full context (who, what, when, where)
  - Track data changes with before/after values
  - Retrieve audit logs with filtering and pagination
  - Generate audit statistics and admin activity reports
  - Export audit logs in various formats
- **Models Tested**: `AuditLogger`

#### 6. System Health Tests
- **Purpose**: Monitor system performance and health metrics
- **Tests**:
  - Record health metrics (CPU, memory, disk, response time)
  - Check database connectivity and performance
  - Monitor error rates and active user counts
  - Create and manage system alerts
  - Resolve alerts and track resolution history
- **Models Tested**: `SystemHealth`

#### 7. Platform Analytics Tests
- **Purpose**: Validate comprehensive platform analytics and reporting
- **Tests**:
  - Generate user growth analytics over time
  - Track content creation and engagement metrics
  - Identify top-performing articles and trending topics
  - Calculate platform health scores
  - Create comparative analytics between time periods
- **Models Tested**: `PlatformAnalytics`

#### 8. Admin User Management Tests
- **Purpose**: Test administrative user management capabilities
- **Tests**:
  - Retrieve users with filtering and pagination
  - Update user roles (user → moderator → admin)
  - Suspend and unsuspend user accounts
  - Verify user email addresses
  - Track user activity and statistics
- **Models Tested**: `User` (admin methods)

#### 9. IP Blocking System Tests
- **Purpose**: Verify IP address blocking and management
- **Tests**:
  - Block IP addresses with temporary and permanent durations
  - Check if IP addresses are currently blocked
  - Handle block expiration automatically
  - Log IP blocking actions for audit trail
- **Models Tested**: `SecurityMonitor` (IP blocking methods)

#### 10. Alert System Tests
- **Purpose**: Test system alert creation and management
- **Tests**:
  - Create alerts of different types (security, performance, error)
  - Set appropriate severity levels (info, warning, error, critical)
  - Retrieve active alerts with filtering
  - Resolve alerts and track resolution
  - Prevent duplicate alerts for same issues
- **Models Tested**: `SystemHealth` (alert methods)

## Running the Tests

### Prerequisites

1. **Database Setup**: Ensure all required database tables are created:
   ```sql
   -- Run these SQL files in order:
   database/setup.sql
   database/content_moderation.sql
   database/security_monitoring.sql
   ```

2. **Test Data**: The tests create their own test data, but ensure you have:
   - At least one admin user (ID: 1)
   - Some sample articles and comments for testing
   - Proper database permissions for test operations

### Command Line Execution

```bash
# Navigate to the tests directory
cd api/tests

# Run the complete test suite
php AdminModerationTestRunner.php

# Run individual test class
php AdminModerationTest.php
```

### Web Interface Execution

```php
// Include and run tests via web interface
require_once 'api/tests/AdminModerationTest.php';
$tester = new AdminModerationTest();
$tester->runAllTests();
```

## Test Output

### Success Output
```
Admin and Moderation System Test Runner
======================================

✓ Database connection established
✓ All required tables exist

Testing Content Reporting System
-------------------------------
  ✓ Create content report
  ✓ Report has ID
  ✓ Get pending reports
  ✓ Update report status
  ✓ Get report statistics
✓ Content reporting tests passed

[... additional test output ...]

Test Summary
============
✓ PASS - Content Reporting
✓ PASS - Moderation Actions
✓ PASS - Content Filtering
✓ PASS - Security Monitoring
✓ PASS - Audit Logging
✓ PASS - System Health
✓ PASS - Platform Analytics
✓ PASS - Admin User Management
✓ PASS - IP Blocking
✓ PASS - Alert System

Results: 10/10 tests passed

🎉 All admin and moderation tests passed successfully!
```

### Failure Output
```
✗ Content reporting test failed: Database connection error
✗ FAIL - Content Reporting

Results: 9/10 tests passed (1 failed)

⚠️  Some tests failed. Please review the output above.
```

## Test Data Management

### Test Data Creation
- Tests create temporary test data (users, reports, alerts, etc.)
- Each test uses unique identifiers to avoid conflicts
- Test data is designed to be safe for development environments

### Test Data Cleanup
- Some tests include cleanup operations
- Expired security events and blocks are automatically cleaned up
- Consider running cleanup scripts after testing in development

### Production Considerations
- **Never run these tests in production environments**
- Tests may create test users and modify system data
- Use separate test databases for comprehensive testing

## Extending the Tests

### Adding New Test Methods

1. **Create Test Method**:
   ```php
   private function testNewFeature() {
       echo "Testing New Feature\n";
       echo "------------------\n";
       
       try {
           // Test implementation
           $this->assert($condition, "Test description");
           echo "✓ New feature tests passed\n\n";
       } catch (Exception $e) {
           echo "✗ New feature test failed: " . $e->getMessage() . "\n\n";
           $this->testResults['new_feature'] = false;
           return;
       }
       
       $this->testResults['new_feature'] = true;
   }
   ```

2. **Add to Test Runner**:
   ```php
   public function runAllTests() {
       // ... existing tests ...
       $this->testNewFeature();
       // ...
   }
   ```

### Test Assertion Methods

The test class provides an `assert()` method for validating conditions:

```php
// Basic assertion
$this->assert($value === true, "Value should be true");

// Array assertion
$this->assert(is_array($result), "Result should be array");

// Count assertion
$this->assert(count($items) > 0, "Should have items");

// Property assertion
$this->assert(isset($object['property']), "Object should have property");
```

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Check database configuration in `config/database.php`
   - Ensure database server is running
   - Verify user permissions

2. **Missing Tables**:
   - Run database setup scripts
   - Check table creation SQL for errors
   - Verify database schema matches expectations

3. **Permission Errors**:
   - Ensure test user has admin privileges
   - Check file system permissions for logs
   - Verify database user has required permissions

4. **Memory Issues**:
   - Increase PHP memory limit for large test suites
   - Consider running tests in smaller batches
   - Monitor memory usage during test execution

### Debug Mode

Enable debug mode by setting error reporting:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

### Logging

Tests automatically log errors to the PHP error log. Check:
- PHP error log file
- Application-specific log files
- Database query logs for SQL errors

## Performance Considerations

### Test Execution Time
- Complete test suite typically runs in 10-30 seconds
- Individual test categories run in 1-5 seconds
- Database operations are the primary time factor

### Resource Usage
- Tests use minimal system resources
- Peak memory usage typically under 50MB
- Database connections are reused efficiently

### Optimization Tips
- Run tests on local development environments
- Use test-specific database for isolation
- Consider parallel test execution for large suites

## Security Considerations

### Test Environment Security
- Use separate test databases
- Avoid testing with production data
- Ensure test credentials are not production credentials

### Test Data Security
- Test data should not contain real user information
- Use placeholder data for sensitive fields
- Clean up test data after execution

### Access Control Testing
- Tests verify admin access controls work correctly
- Role-based permission testing ensures proper security
- Authentication bypass attempts are properly blocked

## Maintenance

### Regular Test Updates
- Update tests when adding new features
- Modify tests when changing existing functionality
- Add regression tests for bug fixes

### Test Data Maintenance
- Periodically clean up old test data
- Update test scenarios to match current usage patterns
- Ensure test data remains realistic and relevant

### Documentation Updates
- Keep this documentation current with test changes
- Document new test categories and methods
- Update troubleshooting information based on common issues