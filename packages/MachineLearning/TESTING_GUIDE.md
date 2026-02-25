# Intelligence Package Testing Guide

## Test Suite Overview

The Nexus Intelligence package includes comprehensive test coverage across all Wave 1 extractors and integration points.

## Package Unit Tests (PHPUnit)

Location: `packages/Intelligence/tests/Unit/`

### Running Package Tests

```bash
# From package directory
cd packages/Intelligence
composer install  # Install PHPUnit
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/DuplicatePaymentDetectionExtractorTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run specific group
vendor/bin/phpunit --group=extractors
vendor/bin/phpunit --group=payable
vendor/bin/phpunit --group=receivable
vendor/bin/phpunit --group=inventory
```

### Test Files

1. **DuplicatePaymentDetectionExtractorTest.php** (7 tests)
   - ✅ All 22 features extraction
   - ✅ Weekend payment detection (data provider)
   - ✅ Z-score calculation (5 scenarios)
   - ✅ Null value handling
   - ✅ Levenshtein similarity scoring
   - ✅ Missing vendor_id handling

2. **CustomerPaymentPredictionExtractorTest.php** (7 tests)
   - ✅ All 20 features extraction
   - ✅ Payment consistency scoring (4 scenarios)
   - ✅ New customer handling
   - ✅ Credit risk assessment (4 scenarios)
   - ✅ Days-to-pay prediction
   - ✅ Missing customer_id handling

3. **DemandForecastExtractorTest.php** (7 tests)
   - ✅ All 22 features extraction
   - ✅ Trend pattern identification (5 scenarios)
   - ✅ Demand variability assessment (5 scenarios)
   - ✅ Stockout risk evaluation (5 scenarios)
   - ✅ Safety stock calculation
   - ✅ New product handling
   - ✅ Missing product_id handling

## Application Feature Tests (Laravel)

Location: `consuming application (e.g., Laravel app)tests/Feature/Intelligence/`

### Running Application Tests

```bash
# From consuming application directory
cd apps/consuming application

# Run all Intelligence tests
php artisan test --filter=Intelligence

# Run specific test file
php artisan test tests/Feature/Intelligence/PaymentHistoryRepositoryTest.php

# Run with specific group
php artisan test --group=intelligence
php artisan test --group=repositories
php artisan test --group=integrations

# Run with coverage
php artisan test --coverage --min=80
```

### Test Files

1. **PaymentHistoryRepositoryTest.php** (8 tests)
   - ✅ Average payment delay calculation
   - ✅ Payment behavior metrics retrieval
   - ✅ Credit health metrics retrieval
   - ✅ Relationship metrics retrieval
   - ✅ Dirty record marking (incremental refresh)
   - ✅ Null last payment date handling
   - ✅ Tenant isolation verification
   - ✅ Non-existent customer handling

2. **EnrichInvoiceWithPaymentPredictionListenerTest.php** (6 tests)
   - ✅ Queue job dispatched on event
   - ✅ Payment prediction record creation
   - ✅ Confidence score calculation
   - ✅ High-urgency collections alerts
   - ✅ Feature JSON storage (20 features)
   - ✅ New customer handling

## Test Data Providers

The test suite uses PHPUnit data providers for comprehensive scenario coverage:

- **Weekend dates**: Saturday, Sunday, Monday, Friday
- **Z-score scenarios**: Mean, +1σ, +2σ, -1σ, zero variance
- **Payment rates**: Excellent (95%), Good (80%), Average (60%), Poor (30%)
- **Credit utilization**: Low (20%), Medium (50%), High (85%), Maxed (95%)
- **Trend patterns**: Strong growth, Moderate growth, Stable, Decline
- **Demand variability**: Stable (CV<0.30), Moderate (0.30-0.60), Volatile (>0.60)
- **Stockout risk**: Low, Medium, High, Very high

## Coverage Targets

- **Unit Tests**: 100% of extractor methods
- **Feature Tests**: All critical repository methods
- **Integration Tests**: All event listeners and commands

## Test Execution Summary

```bash
# Quick validation (package unit tests only)
cd packages/Intelligence && vendor/bin/phpunit

# Full test suite (requires database)
cd apps/consuming application && php artisan test --filter=Intelligence

# Pre-deployment verification
cd apps/consuming application && php artisan test --group=intelligence --coverage --min=80
```

## CI/CD Integration

```yaml
# Example GitHub Actions workflow
- name: Run Intelligence Package Tests
  run: |
    cd packages/Intelligence
    composer install
    vendor/bin/phpunit --coverage-clover=coverage.xml

- name: Run Intelligence Feature Tests
  run: |
    cd apps/consuming application
    php artisan migrate:fresh --seed
    php artisan test --filter=Intelligence
```

## Test Maintenance

- All tests use PHP 8.3 attributes (`#[Test]`, `#[DataProvider]`, `#[Group]`)
- Mock-based unit tests (no database required)
- Database transactions for feature tests (automatic rollback)
- Tenant isolation verified in all repository tests
- Factory-based test data generation

## Expected Test Results

```
Package Unit Tests:
  DuplicatePaymentDetectionExtractorTest  ✓ 7 tests
  CustomerPaymentPredictionExtractorTest  ✓ 7 tests
  DemandForecastExtractorTest            ✓ 7 tests
  
Application Feature Tests:
  PaymentHistoryRepositoryTest                      ✓ 8 tests
  EnrichInvoiceWithPaymentPredictionListenerTest    ✓ 6 tests

Total: 35 tests, 150+ assertions
```

## Troubleshooting

**Package tests fail with "Class not found":**
```bash
cd packages/Intelligence
composer dump-autoload
```

**Feature tests fail with database errors:**
```bash
cd apps/consuming application
php artisan migrate:fresh
php artisan db:seed
```

**Queue tests fail:**
```bash
# Ensure queue driver is 'sync' for testing
# In phpunit.xml:
<env name="QUEUE_CONNECTION" value="sync"/>
```
