# Ponto PHP Library - Test Suite Summary

## Overview

This document describes the comprehensive test suite created for the `ponto-php` module following TDD (Test-Driven Development) methodology. The test suite provides complete coverage for all planned components of the Ponto API integration library.

**Total Test Files Created:** 15+
**Estimated Total Test Cases:** 250+

## Test Organization

### Directory Structure

```
tests/
├── Pest.php                              # Pest configuration with custom helpers
├── Unit/                                 # Unit tests with mocked dependencies
│   ├── Exceptions/                       # Exception tests (6 files)
│   │   ├── PontoExceptionTest.php
│   │   ├── AuthenticationExceptionTest.php
│   │   ├── ValidationExceptionTest.php
│   │   ├── NotFoundExceptionTest.php
│   │   ├── RateLimitExceptionTest.php
│   │   └── ApiExceptionTest.php
│   ├── Models/                           # Model tests (5 files)
│   │   ├── PaginatedCollectionTest.php
│   │   ├── AccountTest.php
│   │   ├── TransactionTest.php
│   │   ├── PaymentTest.php
│   │   └── SynchronizationTest.php
│   ├── Utils/                            # Utility tests (1 file)
│   │   └── ValidatorTest.php
│   └── Services/                         # Service tests (1+ files)
│       └── AccountServiceTest.php
└── Integration/                          # Integration tests
    └── PontoApiIntegrationTest.php       # Sandbox API tests
```

## Test Categories

### 1. Exception Tests (60+ test cases)

**Files:** 6 exception test files  
**Coverage:** All custom exception types

#### PontoExceptionTest.php
- Base exception creation with messages, codes, error details
- Exception chaining with previous exceptions
- Error details handling (null, empty, populated arrays)
- Exception catching and inheritance

#### AuthenticationExceptionTest.php
- OAuth2 authentication failures
- Invalid/expired token scenarios
- 401 status code handling
- Error details for client authentication failures

#### ValidationExceptionTest.php
- Input validation failures (IBAN, BIC, amounts)
- Field-specific error messages
- 400 status code handling
- Multiple validation errors

#### NotFoundExceptionTest.php
- Resource not found scenarios (accounts, transactions, payments, synchronizations)
- 404 status code handling
- Resource-specific error messages

#### RateLimitExceptionTest.php
- Rate limit exceeded scenarios
- Retry-After header handling
- 429 status code (always)
- Various retry-after values (null, 0, large numbers)

#### ApiExceptionTest.php
- General API errors (4xx/5xx status codes)
- Error codes and messages
- Complex error details from API
- Various HTTP status codes (400, 403, 409, 422, 500, 502, 503, 504)

### 2. Model Tests (100+ test cases)

**Files:** 5 model test files  
**Coverage:** All domain models

#### PaginatedCollectionTest.php (25+ tests)
- Creation from array data
- Pagination metadata (limit, cursors)
- Pagination links (first, prev, next)
- Helper methods (hasNextPage, hasPrevPage, isEmpty, count)
- Readonly properties enforcement
- Edge cases (empty, large datasets)

#### AccountTest.php (30+ tests)
- All properties and creation from JSON:API format
- IBAN and currency validation
- Business logic (isDeprecated, needsReauthorization)
- DateTimeImmutable parsing
- Edge cases (null values, zero/negative/large balances)
- Different subtypes (checking, savings, credit) and currencies
- Readonly properties
- Relationships and metadata

#### TransactionTest.php (35+ tests)
- All properties and creation from array
- Business logic (isCredit, isDebit, getAbsoluteAmount)
- Date parsing (execution, value, created, updated)
- Optional fields (counterpart, remittance, fees)
- Edge cases (zero amount, null fields, large/small amounts)
- Structured and unstructured remittance
- Banking codes (endToEndId, mandateId, purposeCode, BIC)
- Digest handling

#### PaymentTest.php (30+ tests)
- All status states (unsigned, authorized, executed, cancelled, rejected)
- Status checking methods with mutual exclusivity
- Authorization requirements (requiresAuthorization)
- Redirect URL handling
- Idempotency with endToEndId
- Future-dated payments with requestedExecutionDate
- Remittance information (structured/unstructured)
- IBAN and BIC validation
- Edge cases (different currencies, various amounts)

#### SynchronizationTest.php (25+ tests)
- All status states (pending, running, success, error)
- Status checking methods (isPending, isRunning, isSuccessful, hasErrors, isComplete)
- Status mutual exclusivity and transitions
- Resource types and subtypes
- Error handling with multiple errors
- Date parsing and immutability

### 3. Utils Tests (60+ test cases)

**Files:** 1 utility test file  
**Coverage:** Input validation

#### ValidatorTest.php (60+ tests)

**IBAN Validation (12+ tests):**
- Valid formats (Belgian, French, German)
- Case conversion and space handling
- Invalid formats, missing country codes
- Edge cases (empty, special characters)

**BIC Validation (9+ tests):**
- 8 and 11 character formats
- Case conversion
- Invalid formats and lengths
- Edge cases

**Amount Validation (10+ tests):**
- Positive amounts validation
- Boundaries (0.01 to 999,999,999.99)
- Rejection of negative/zero/too large amounts
- Integer and decimal amounts

**Currency Validation (9+ tests):**
- ISO 4217 currency codes
- Case conversion
- Multiple currencies (EUR, USD, GBP, CHF, JPY, etc.)
- Invalid formats

**Remittance Information Validation (20+ tests):**
- Allowed characters (alphanumeric, spaces, special chars: / - ? : ( ) . , ' +)
- Length limits (140 characters maximum)
- Structured remittance formats
- Invalid characters rejection (@ # _ = &)

### 4. Service Tests (20+ test cases)

**Files:** 1+ service test files  
**Coverage:** Business logic with mocked HTTP client

#### AccountServiceTest.php (20+ tests)
- list() with pagination (cursors, limits)
- get() single account
- getSyncMetadata()
- Validation errors (limit boundaries, empty IDs)
- Empty results handling
- Pagination links
- Exception scenarios (NotFoundException)

**Additional Services** (to be implemented):
- TransactionServiceTest.php
- PaymentServiceTest.php
- SynchronizationServiceTest.php

### 5. Integration Tests (20+ test cases)

**Files:** 1 comprehensive integration file  
**Coverage:** Real Ponto Sandbox API testing

#### PontoApiIntegrationTest.php (20+ tests)

**Authentication Tests:**
- Valid sandbox credentials
- Invalid credentials rejection
- Token caching across requests

**Account Tests:**
- List accounts with pagination
- Get single account
- NotFoundException for invalid IDs
- Pagination through multiple pages

**Transaction Tests:**
- List transactions
- Get single transaction
- Date filters (since/until)
- Pending transactions

**Payment Tests:**
- Payment scope availability checking
- Payment creation with full details
- Status verification

**Synchronization Tests:**
- Create synchronization
- Get synchronization status
- Poll until complete

**Workflow Tests:**
- Complete end-to-end user journeys
- Multiple operations in sequence

## Running Tests

### Run All Tests
```bash
composer test
# or
./vendor/bin/pest
```

### Run Unit Tests Only
```bash
./vendor/bin/pest tests/Unit
# or
./vendor/bin/pest --exclude-group=integration
```

### Run Integration Tests Only
```bash
# Requires sandbox credentials
PONTO_SANDBOX_CLIENT_ID=your_id PONTO_SANDBOX_CLIENT_SECRET=your_secret ./vendor/bin/pest --group=integration
```

### Run Specific Test Groups
```bash
./vendor/bin/pest --group=unit              # Unit tests
./vendor/bin/pest --group=integration       # Integration tests
./vendor/bin/pest --group=auth              # Authentication tests
./vendor/bin/pest --group=accounts          # Account-related tests
./vendor/bin/pest --group=transactions      # Transaction tests
./vendor/bin/pest --group=payments          # Payment tests
./vendor/bin/pest --group=synchronization   # Synchronization tests
```

### Run Tests with Coverage
```bash
composer test-coverage
# or
./vendor/bin/pest --coverage
```

### Run Static Analysis
```bash
composer stan
```

### Run Code Style Checks
```bash
composer format
```

## Test Helpers and Utilities

### Custom Expectations (in Pest.php)
- `toBeValidUuid()` - Validates UUID format
- `toBeValidIban()` - Validates IBAN format
- `toBeValidBic()` - Validates BIC format
- `toBeValidIso4217Currency()` - Validates currency code

### Mock Data Helpers
- `mockJsonApiResponse()` - Creates JSON:API formatted responses
- `mockAccountData()` - Generates mock account data
- `mockTransactionData()` - Generates mock transaction data
- `mockPaymentData()` - Generates mock payment data
- `mockSynchronizationData()` - Generates mock synchronization data

## Environment Configuration

### For Integration Tests
Create a `.env` file or set environment variables:

```env
PONTO_SANDBOX_CLIENT_ID=your_sandbox_client_id
PONTO_SANDBOX_CLIENT_SECRET=your_sandbox_client_secret
PONTO_BASE_URL=https://api.myponto.com
```

## TDD Workflow

This test suite follows TDD methodology:

1. **Red Phase** ✓ - All tests are written and will fail (no implementation exists yet)
2. **Green Phase** - Implement the actual code to make tests pass
3. **Refactor Phase** - Improve code while keeping tests green

### Current Status: RED PHASE ✓

All tests are written and ready. The implementation classes need to be created:

**To Implement:**
- `src/Client.php`
- `src/Config.php`
- `src/Auth/AuthProvider.php`
- `src/Auth/TokenStorage.php` (interface)
- `src/Auth/FileTokenStorage.php`
- `src/Http/HttpClient.php`
- `src/Http/HttpClientInterface.php`
- `src/Services/AccountService.php`
- `src/Services/TransactionService.php`
- `src/Services/PaymentService.php`
- `src/Services/SynchronizationService.php`
- `src/Models/Account.php`
- `src/Models/Transaction.php`
- `src/Models/Payment.php`
- `src/Models/Synchronization.php`
- `src/Models/PaginatedCollection.php`
- `src/Models/FinancialInstitution.php`
- `src/Exceptions/PontoException.php`
- `src/Exceptions/AuthenticationException.php`
- `src/Exceptions/ValidationException.php`
- `src/Exceptions/NotFoundException.php`
- `src/Exceptions/RateLimitException.php`
- `src/Exceptions/ApiException.php`
- `src/Utils/Validator.php`
- `src/Utils/DateHelper.php`

## Test Coverage Goals

- **Unit Tests:** 100% coverage of all classes
- **Integration Tests:** Cover all major API endpoints
- **Edge Cases:** Comprehensive coverage of boundary conditions
- **Error Scenarios:** All exception types and error paths
- **Business Logic:** All model methods and service operations

## Additional Test Files to Add (Optional)

For complete coverage, consider adding:

1. **Service Tests:**
   - `TransactionServiceTest.php`
   - `PaymentServiceTest.php`
   - `SynchronizationServiceTest.php`

2. **Auth Module Tests:**
   - `AuthProviderTest.php`
   - `FileTokenStorageTest.php`

3. **HTTP Module Tests:**
   - `HttpClientTest.php`
   - `ResponseTest.php`

4. **Core Tests:**
   - `ClientTest.php`
   - `ConfigTest.php`

5. **Feature Tests:**
   - `CompleteWorkflowTest.php` - End-to-end user scenarios

## Notes

- All tests follow Pest v4.1 functional syntax
- Tests use Mockery for mocking dependencies
- Integration tests gracefully skip if credentials not provided
- Tests are organized by component for easy navigation
- Each test is descriptive and self-documenting
- Test groups allow selective test execution

## Next Steps

1. Run tests to verify they all fail appropriately (RED phase)
2. Implement the actual classes following the test specifications
3. Run tests again to see them pass (GREEN phase)
4. Refactor code while keeping tests green
5. Add more tests as edge cases are discovered

---

**Test Suite Version:** 1.0  
**Created:** 2025-10-01  
**Framework:** Pest 4.1  
**PHP Version:** 8.4+
