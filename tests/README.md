# Appeal Prospect MVP - Testing Framework

## Overview

This directory contains the comprehensive testing suite for the Appeal Prospect MVP application. The tests are organized into multiple test suites to ensure thorough coverage of all application components.

## Test Structure

```
tests/
├── Unit/                   # Unit tests for individual components
│   ├── SecurityTest.php    # Security functions testing
│   ├── PDFParserTest.php   # PDF processing testing  
│   └── FormValidatorTest.php # Form validation testing
├── Integration/            # Integration tests for system workflows
│   ├── AuthenticationTest.php # Authentication system testing
│   └── AIAnalysisTest.php     # AI analysis workflow testing
├── Functional/             # End-to-end functional tests
│   └── UserWorkflowTest.php   # Complete user journey testing
├── bootstrap.php           # Test environment setup
├── TestCase.php            # Base test case class
└── README.md              # This file
```

## Running Tests

### Prerequisites

- PHP 8.0+ with required extensions (PDO, SQLite, JSON, mbstring)
- PHPUnit 10.5+ (install via Composer: `composer require --dev phpunit/phpunit`)

### Test Execution

```bash
# Run all tests
php run-tests.php

# Run specific test suite
php run-tests.php unit
php run-tests.php integration
php run-tests.php functional

# Run specific test file
php run-tests.php SecurityTest.php
php run-tests.php Unit/SecurityTest.php
```

### Test Coverage

Coverage reports are generated in `tests/coverage/` directory. Open `tests/coverage/index.html` in a browser to view detailed coverage reports.

## Test Categories

### Unit Tests (@group unit)

Test individual functions and classes in isolation:

- **SecurityTest**: Validates all security functions including input validation, sanitization, rate limiting, and session security
- **PDFParserTest**: Tests PDF text extraction, file upload processing, and text cleaning
- **FormValidatorTest**: Validates form validation rules, error handling, and helper functions

### Integration Tests (@group integration)

Test interactions between system components:

- **AuthenticationTest**: Tests complete authentication workflows including registration, login, logout, session management, and access control
- **AIAnalysisTest**: Tests AI analysis data flow, storage, retrieval, and statistics

### Functional Tests (@group functional)  

Test complete end-to-end user workflows:

- **UserWorkflowTest**: Tests complete user journeys from registration through analysis, including error scenarios, multi-user workflows, and admin operations

## Test Database

Tests use an in-memory SQLite database that is:
- Created fresh for each test run
- Pre-populated with test data
- Automatically cleaned up after tests

## Test Utilities

The `TestCase` base class provides helpful utilities:

- `createTestUser()`: Creates test users with customizable attributes
- `createTestCase()`: Creates test cases with sample data
- `loginUser()`: Simulates user login
- `mockFileUpload()`: Creates mock file uploads for testing
- `assertValidationPasses/Fails()`: Tests validation results
- `assertExecutionTime()`: Performance testing helpers

## Security Testing

Security tests cover:

- Input validation and sanitization
- Authentication and authorization
- Session security and CSRF protection  
- File upload security
- Rate limiting
- XSS and injection prevention

## Performance Testing

Performance tests ensure:
- Individual functions execute within time limits
- Database operations are optimized
- Complete workflows perform acceptably
- Memory usage remains reasonable

## Best Practices

1. **Isolation**: Each test is independent and doesn't affect others
2. **Descriptive Names**: Test method names clearly describe what's being tested
3. **Arrange-Act-Assert**: Tests follow AAA pattern for clarity
4. **Group Tags**: Tests use @group annotations for selective running
5. **Error Testing**: Both success and failure scenarios are tested
6. **Performance**: Critical paths have performance assertions

## Coverage Goals

The testing framework aims for:
- 90%+ code coverage for security functions
- 80%+ code coverage for business logic
- 100% coverage of critical authentication flows
- Complete coverage of user-facing workflows

## Continuous Integration

Tests can be integrated into CI/CD pipelines:

```bash
# CI/CD command
php run-tests.php --stop-on-failure --no-coverage
```

## Troubleshooting

### Common Issues

1. **Missing Extensions**: Install required PHP extensions
2. **Database Permissions**: Ensure write access for SQLite
3. **PHPUnit Not Found**: Install via Composer or system package manager
4. **File Permissions**: Check test directory write permissions

### Debug Mode

For detailed test output, tests can be run with verbose flag or by examining individual test output.

## Contributing

When adding new features:

1. Add corresponding unit tests
2. Update integration tests if needed
3. Add functional tests for user-facing features
4. Maintain test coverage above target thresholds
5. Follow existing test patterns and naming conventions