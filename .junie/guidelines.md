# Development Guidelines for ponto-php

This document contains project-specific development guidelines for the Ponto PHP API implementation.

---

## PHP Version Requirements

**Critical**: This project requires **PHP 8.4+**. Ensure your development environment meets this requirement before contributing.

```bash
php -v  # Verify your PHP version
```

---

## Testing

### Framework
The project uses **Pest v4.1** for testing with a functional syntax approach.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage
```

### Test Structure
- Tests are located in the `tests/` directory
- Test files must end with `Test.php` suffix
- The project uses Pest's functional syntax (no class declarations needed)
- Tests automatically discover files in `tests/` directory via phpunit.xml configuration

### Writing Tests

Create test files in `tests/Unit/` or other appropriate subdirectories:

```php
<?php

use AlchemicStudio\Ponto\Ponto;

test('can instantiate Ponto class', function () {
    $ponto = new Ponto();
    
    expect($ponto)->toBeInstanceOf(Ponto::class);
});

test('descriptive test name', function () {
    // Your test logic here
    expect($value)->toBe($expected);
});
```

**Key Points:**
- No need for namespace declarations in Pest tests
- Use `test()` function with descriptive names
- Use `expect()` for assertions with fluent API
- Test classes are available via PSR-4 autoloading (`AlchemicStudio\Ponto\Tests` namespace)

---

## Static Analysis

### PHPStan
The project uses **PHPStan v2.1** with maximum strictness level.

```bash
# Run static analysis
composer stan
```

**Configuration:**
- Analyzes both `src/` and `tests/` directories
- Uses `--level=max` (strictest level)
- No custom phpstan.neon configuration file (uses defaults)
- Ensures type safety and catches potential bugs

---

## Code Style

### PHP CS Fixer
The project uses **PHP CS Fixer v3.21.1** for code formatting.

```bash
# Automatically fix code style issues
composer format
```

### Style Rules
The project follows **PSR-12** with additional customizations:

**Key Rules:**
- Short array syntax: `[]` instead of `array()`
- Alphabetically ordered imports
- No unused imports
- Trailing commas in multiline arrays/function calls
- Space after `not` operator
- Binary and unary operator spacing
- Blank lines before control flow statements (break, continue, declare, return, throw, try)
- One blank line between class methods
- Fully multiline method arguments when breaking across lines

**Configuration file:** `.php-cs-fixer.dist.php`
- Applies to `src/` and `tests/` directories
- Uses caching (`.php-cs-fixer.cache`) for performance

---

## Autoloading

### PSR-4 Namespaces
- **Source code:** `AlchemicStudio\Ponto\` → `src/`
- **Test code:** `AlchemicStudio\Ponto\Tests\` → `tests/`

**Note:** The base `TestCase` class in `tests/TestCase.php` uses namespace `Tests` (legacy), but new test code should follow Pest's functional approach without classes.

---

## Development Workflow

### Before Committing
Always run these commands before committing:

```bash
composer format  # Fix code style
composer stan    # Check for type errors
composer test    # Verify all tests pass
```

### Composer Scripts Summary
- `composer test` - Run test suite with Pest
- `composer test-coverage` - Run tests with coverage report
- `composer format` - Auto-fix code style with PHP CS Fixer
- `composer stan` - Run PHPStan static analysis at max level

---

## Project Structure

```
├── src/                     # Source code (AlchemicStudio\Ponto namespace)
├── tests/                   # Test files
│   ├── TestCase.php        # Base test case (legacy, not needed for Pest)
│   └── Unit/               # Unit tests
├── vendor/                  # Composer dependencies
├── composer.json           # Dependencies and scripts
├── phpunit.xml             # PHPUnit/Pest configuration
└── .php-cs-fixer.dist.php  # Code style configuration
```

---

## Additional Tools

### Spatie Ray
The project includes **spatie/ray** for debugging. Use it for development debugging:

```php
ray($variable);  // Debug output
ray()->measure();  // Performance monitoring
```

---

## Notes

- The project is in early development; the main `Ponto` class is currently empty
- All composer plugins are pre-configured and allowed in composer.json
- Minimum stability is set to "dev" with "prefer-stable" enabled
- The project targets implementation of the [Ponto API](https://documentation.myponto.com/1/api/curl)
