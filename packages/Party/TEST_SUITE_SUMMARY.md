# Test Suite Summary: Party

**Package:** `Nexus\Party`  
**Last Test Run:** 2025-11-25  
**Status:** ⚠️ **Tests Not Yet Implemented**

---

## Test Coverage Metrics

### Overall Coverage
- **Line Coverage:** 0% (No tests implemented yet)
- **Function Coverage:** 0% (No tests implemented yet)
- **Class Coverage:** 0% (No tests implemented yet)
- **Complexity Coverage:** 0% (No tests implemented yet)

### Target Coverage Goals
- **Line Coverage:** >80%
- **Function Coverage:** >90%
- **Critical Path Coverage:** 100%

---

## Test Inventory (Planned)

### Unit Tests (Planned: 35 tests)

#### Value Objects Tests
- `TaxIdentityTest.php` - 8 tests
  - ✅ Validates country code format (ISO 3166-1 alpha-3)
  - ✅ Validates tax number not empty
  - ✅ Validates expiry date after issue date
  - ✅ Tests isExpired() logic
  - ✅ Tests isValidOn() logic
  - ✅ Tests format() output
  - ✅ Tests toArray() serialization
  - ✅ Tests fromArray() deserialization

- `PostalAddressTest.php` - 10 tests
  - ✅ Validates street line 1 not empty
  - ✅ Validates city not empty
  - ✅ Validates postal code format for Malaysia (5 digits)
  - ✅ Validates postal code format for Singapore (6 digits)
  - ✅ Validates postal code format for USA (ZIP/ZIP+4)
  - ✅ Validates postal code format for UK (complex pattern)
  - ✅ Tests format() with all fields
  - ✅ Tests format() with minimal fields
  - ✅ Tests optional coordinates
  - ✅ Tests toArray() serialization

#### Service Tests
- `PartyManagerTest.php` - 12 tests
  - ✅ Creates organization with tax identity
  - ✅ Creates individual without tax identity
  - ✅ Prevents duplicate party by legal name
  - ✅ Prevents duplicate party by tax identity
  - ✅ Adds address to party
  - ✅ Sets primary address (clears existing primary)
  - ✅ Adds contact method to party
  - ✅ Sets primary contact method per type
  - ✅ Finds potential duplicates by name fuzzy match
  - ✅ Finds potential duplicates by tax identity exact match
  - ✅ Updates party metadata
  - ✅ Retrieves party by ID

- `PartyRelationshipManagerTest.php` - 5 tests
  - ✅ Creates relationship with effective dates
  - ✅ Ends relationship with effective date
  - ✅ Prevents circular relationship (A→B→C→A)
  - ✅ Validates relationship type constraints
  - ✅ Respects max depth 50 for circular check

### Integration Tests (Planned: 8 tests)

- `PartyIntegrationTest.php` - 8 tests
  - ✅ Full lifecycle: Create org → Add address → Add contacts → Create relationship
  - ✅ Employee mobility: Individual moves from Company A to Company B
  - ✅ Organizational hierarchy: Department → Division → Company
  - ✅ Multi-role individual: Same person as Employee + Vendor contact
  - ✅ Address primary flag atomicity (concurrent updates)
  - ✅ Contact method primary flag per type
  - ✅ Duplicate detection across tenant boundary
  - ✅ Tax identity expiry validation

---

## Test Execution (Not Yet Run)

**Status:** No tests implemented  
**Next Steps:**
1. Create `packages/Party/tests/` directory structure
2. Implement unit tests for value objects
3. Implement unit tests for services (with mocked repositories)
4. Implement integration tests (requires consuming app setup)

---

## Testing Strategy

### What Should Be Tested (Package Level)

#### ✅ **Value Object Validation**
- All constructor validation rules
- Country-specific postal code patterns
- Tax identity expiry logic
- Immutability guarantees

#### ✅ **Service Business Logic**
- Duplicate detection algorithms
- Primary flag management (atomic clear + set)
- Circular relationship detection (iterative algorithm)
- Exception throwing for error cases

#### ✅ **Interface Contracts**
- All interface methods return correct types
- Null safety for optional parameters
- Metadata handling

### What Should NOT Be Tested (Application Layer)

#### ❌ **Database Persistence**
- Not tested in package (tested in consuming app)
- Repositories are mocked in package unit tests
- Actual DB queries tested in Laravel/Symfony integration tests

#### ❌ **Framework Integration**
- Service provider bindings (tested in consuming app)
- HTTP controllers/routes (not in package scope)
- Eloquent/Doctrine models (consuming app concern)

---

## How to Run Tests (When Implemented)

### PHPUnit Configuration

**File:** `packages/Party/phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Party Package Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <coverage>
        <report>
            <html outputDirectory="coverage/html"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
</phpunit>
```

### Commands

```bash
# Run all tests
cd packages/Party
composer test

# Run with coverage
composer test:coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/ValueObjects/TaxIdentityTest.php

# Run with verbose output
vendor/bin/phpunit --verbose
```

---

## CI/CD Integration (Planned)

### GitHub Actions Workflow

```yaml
name: Party Package Tests

on:
  push:
    paths:
      - 'packages/Party/**'
  pull_request:
    paths:
      - 'packages/Party/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
      - name: Install dependencies
        run: composer install --working-dir=packages/Party
      - name: Run tests
        run: composer test --working-dir=packages/Party
      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

---

## Known Test Gaps (To Be Addressed)

1. **Concurrent Primary Flag Updates** - Need database-level test to verify atomicity
2. **Recursive CTE for Org Hierarchy** - Requires actual database for testing
3. **Fuzzy Name Matching** - Edge cases with special characters, unicode
4. **Country-Specific Postal Codes** - Only 9 countries validated, need coverage for all
5. **Tax Identity Validation** - Format validation per country (currently generic)

---

## Test Dependencies

### Required for Package Unit Tests
- **PHPUnit 11.x** - Testing framework
- **Mockery** - Mocking library for repositories
- **No database required** - Unit tests use mocked repositories

### Required for Integration Tests (Consuming App)
- **Laravel 12** or **Symfony 7** - Framework for integration
- **Database** - MySQL/PostgreSQL for actual persistence tests
- **RefreshDatabase trait** - Laravel database reset between tests

---

## Test Quality Standards

### Code Coverage Requirements
- **Critical Business Logic:** 100% coverage (duplicate detection, circular ref prevention)
- **Value Objects:** >95% coverage (all validation paths)
- **Service Methods:** >85% coverage (all public methods, exception paths)
- **Interfaces:** 100% signature coverage (all methods tested via implementations)

### Test Quality Metrics
- **Arrange-Act-Assert Pattern:** All tests follow AAA structure
- **Single Responsibility:** Each test validates one behavior
- **Descriptive Names:** Test names describe expected behavior
- **Edge Cases:** Boundary conditions tested (empty strings, max depth, null values)

---

## Example Test Structure (Planned)

### Value Object Test Example

```php
<?php

declare(strict_types=1);

namespace Nexus\Party\Tests\Unit\ValueObjects;

use Nexus\Party\ValueObjects\TaxIdentity;
use PHPUnit\Framework\TestCase;

final class TaxIdentityTest extends TestCase
{
    public function test_creates_tax_identity_with_valid_country_code(): void
    {
        $taxId = new TaxIdentity(
            country: 'MYS',
            number: '202301012345'
        );
        
        $this->assertSame('MYS', $taxId->country);
        $this->assertSame('202301012345', $taxId->number);
    }
    
    public function test_throws_exception_for_invalid_country_code_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid country code format');
        
        new TaxIdentity(
            country: 'MY', // Only 2 chars, should be 3
            number: '202301012345'
        );
    }
    
    public function test_validates_expiry_date_after_issue_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiry date must be after issue date');
        
        new TaxIdentity(
            country: 'MYS',
            number: '202301012345',
            issueDate: new \DateTimeImmutable('2025-01-01'),
            expiryDate: new \DateTimeImmutable('2024-12-31') // Before issue!
        );
    }
}
```

### Service Test Example (with Mocked Repository)

```php
<?php

declare(strict_types=1);

namespace Nexus\Party\Tests\Unit\Services;

use Nexus\Party\Services\PartyManager;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Enums\PartyType;
use PHPUnit\Framework\TestCase;
use Mockery;

final class PartyManagerTest extends TestCase
{
    public function test_creates_organization_party(): void
    {
        $mockRepository = Mockery::mock(PartyRepositoryInterface::class);
        $mockRepository->shouldReceive('findByLegalName')->andReturn(null);
        $mockRepository->shouldReceive('save')->once()->andReturn(/* mock party */);
        
        $manager = new PartyManager(
            partyRepository: $mockRepository,
            addressRepository: Mockery::mock(),
            contactMethodRepository: Mockery::mock(),
            logger: Mockery::mock()
        );
        
        $party = $manager->createOrganization(
            tenantId: 'tenant-123',
            legalName: 'Acme Corp'
        );
        
        $this->assertInstanceOf(PartyInterface::class, $party);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
    }
}
```

---

## Recommendations

### Immediate Actions Required
1. ✅ Create `tests/Unit/ValueObjects/` directory
2. ✅ Implement `TaxIdentityTest.php` with 8 test cases
3. ✅ Implement `PostalAddressTest.php` with 10 test cases
4. ✅ Create `tests/Unit/Services/` directory
5. ✅ Implement `PartyManagerTest.php` with mocked repositories
6. ✅ Implement `PartyRelationshipManagerTest.php`
7. ✅ Set up PHPUnit configuration file
8. ✅ Add test commands to composer.json scripts

### Long-term Testing Goals
- Achieve **>85% line coverage** for entire package
- Document all edge cases discovered during testing
- Create performance benchmarks for circular ref detection (target: <100ms for depth 50)
- Integration tests in consuming app for database-level features

---

**Status:** ⚠️ **Tests Pending Implementation**  
**Priority:** High - Required before v1.0.0 release  
**Estimated Effort:** 3-5 days to implement comprehensive test suite

---

**Last Updated:** 2025-11-25  
**Maintained By:** Nexus Architecture Team
