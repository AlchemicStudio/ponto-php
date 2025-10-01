# Ponto PHP Library - Development Documentation

## Overview and Goals

The **ponto-php** library is a modern PHP SDK for integrating with the Ponto API, which provides:
- **Account Information Services (AIS)**: Retrieve account details, balances, and transaction history
- **Payment Initiation Services (PIS)**: Create and manage single payments, bulk payments, and payment requests

### Key Features
- Full OAuth2 client credentials authentication with automatic token management
- Type-safe models and responses (PHP 8.4+)
- PSR-compliant architecture (PSR-4 autoloading, PSR-12 code style)
- Comprehensive error handling with custom exceptions
- Built-in retry logic with exponential backoff
- Support for pagination, filtering, and synchronization
- Idempotency support for payment operations
- Sandbox and production environment support

### Design Principles
- **Developer Experience**: Intuitive API with fluent interfaces
- **Type Safety**: Full PHP 8.4 type declarations
- **Standards Compliance**: PSR-4, PSR-12, and REST best practices
- **Security First**: No credential logging, secure token storage
- **Testability**: Dependency injection and mockable interfaces

---

## Prerequisites

### System Requirements
- **PHP**: 8.4 or higher
- **Extensions**: 
  - `ext-json` (JSON encoding/decoding)
  - `ext-mbstring` (String manipulation)
  - `ext-curl` or `ext-openssl` (HTTPS requests)
  
### Dependencies
- **Guzzle HTTP Client**: ^7.8 (HTTP requests with retry support)
- **PSR-7/PSR-18**: HTTP message interfaces

### Development Tools
- **Composer**: 2.x for dependency management
- **Pest**: 4.1 for testing
- **PHPStan**: 2.1 for static analysis (level max)
- **PHP CS Fixer**: 3.21 for code style

---

## Installation

### Via Composer

```bash
composer require alchemic-studio/ponto-php
```

### Manual Installation

Add to your `composer.json`:

```json
{
    "require": {
        "php": "^8.4",
        "alchemic-studio/ponto-php": "^1.0",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "pestphp/pest": "^4.1",
        "phpstan/phpstan": "^2.1",
        "friendsofphp/php-cs-fixer": "^3.21"
    },
    "autoload": {
        "psr-4": {
            "AlchemicStudio\\Ponto\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "AlchemicStudio\\Ponto\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest",
        "test-coverage": "pest --coverage",
        "stan": "phpstan analyse src tests --level=max",
        "format": "php-cs-fixer fix"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then run:

```bash
composer install
```

---

## Project Layout

### Directory Structure

```
ponto-php/
├── src/
│   ├── Client.php                      # Main client entry point
│   ├── Config.php                      # Configuration container
│   ├── Auth/
│   │   ├── AuthProvider.php            # OAuth2 token management
│   │   ├── TokenStorage.php            # Token storage interface
│   │   └── FileTokenStorage.php        # File-based token cache
│   ├── Http/
│   │   ├── HttpClient.php              # HTTP client wrapper
│   │   ├── HttpClientInterface.php     # Client contract
│   │   └── Response.php                # HTTP response wrapper
│   ├── Services/
│   │   ├── AccountService.php          # Account operations
│   │   ├── TransactionService.php      # Transaction operations
│   │   ├── PaymentService.php          # Payment operations
│   │   └── SynchronizationService.php  # Sync operations
│   ├── Models/
│   │   ├── Account.php                 # Account resource
│   │   ├── Transaction.php             # Transaction resource
│   │   ├── Payment.php                 # Payment resource
│   │   ├── Synchronization.php         # Synchronization resource
│   │   ├── FinancialInstitution.php    # Financial institution
│   │   └── PaginatedCollection.php     # Paginated results
│   ├── Exceptions/
│   │   ├── PontoException.php          # Base exception
│   │   ├── AuthenticationException.php # Auth failures
│   │   ├── ValidationException.php     # Validation errors
│   │   ├── NotFoundException.php       # 404 errors
│   │   ├── RateLimitException.php      # Rate limit errors
│   │   └── ApiException.php            # API errors
│   └── Utils/
│       ├── Validator.php               # Input validation
│       └── DateHelper.php              # Date formatting
├── tests/
│   ├── Unit/                           # Unit tests
│   ├── Integration/                    # Integration tests
│   └── Pest.php                        # Pest configuration
├── examples/
│   ├── authenticate.php
│   ├── list-accounts.php
│   ├── list-transactions.php
│   ├── create-payment.php
│   └── handle-synchronization.php
├── docs/
    └── developpement.md               # This file
```

---
