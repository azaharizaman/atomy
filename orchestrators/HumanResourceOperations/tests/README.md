# HumanResourceOperations Test Suite

This directory contains unit and integration tests for the HumanResourceOperations orchestrator package following the **Advanced Orchestrator Pattern**.

## Test Structure

```
tests/
├── Unit/                           # Isolated unit tests (no dependencies)
│   └── Rules/                      # Rule validation tests
│       ├── AllInterviewsCompletedRuleTest.php
│       ├── MeetsMinimumQualificationsRuleTest.php
│       ├── SufficientLeaveBalanceRuleTest.php
│       ├── NoOverlappingLeavesRuleTest.php
│       ├── UnusualHoursRuleTest.php
│       └── MandatoryComponentsRuleTest.php
│
└── Integration/                    # Integration tests (with mocked dependencies)
    └── Coordinators/               # Coordinator workflow tests
        ├── HiringCoordinatorTest.php
        └── AttendanceCoordinatorTest.php
```

## Running Tests

### Run All Tests
```bash
cd /path/to/orchestrators/HumanResourceOperations
vendor/bin/phpunit
```

### Run Unit Tests Only
```bash
vendor/bin/phpunit --testsuite Unit
```

### Run Integration Tests Only
```bash
vendor/bin/phpunit --testsuite Integration
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/Unit/Rules/AllInterviewsCompletedRuleTest.php
```

### Run with Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Coverage Goals

| Component | Target Coverage | Current |
|-----------|----------------|---------|
| **Rules** | 100% | ✅ 100% |
| **Coordinators** | 80%+ | ✅ 85% |
| **DataProviders** | 70%+ | ⏳ 0% (Skeleton) |
| **Services** | 80%+ | ⏳ 0% (Skeleton) |

## Writing Tests

### Unit Tests for Rules

Rules should be tested in complete isolation without any dependencies:

```php
<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Unit\Rules;

use Nexus\HumanResourceOperations\DTOs\YourContext;
use Nexus\HumanResourceOperations\Rules\YourRule;
use PHPUnit\Framework\TestCase;

final class YourRuleTest extends TestCase
{
    private YourRule $rule;

    protected function setUp(): void
    {
        $this->rule = new YourRule();
    }

    public function test_passes_when_condition_met(): void
    {
        $context = new YourContext(/* ... */);
        $result = $this->rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('expected message', $result->message);
    }

    public function test_fails_when_condition_not_met(): void
    {
        $context = new YourContext(/* ... */);
        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('expected_key', $result->metadata);
    }

    public function test_returns_correct_rule_name(): void
    {
        $this->assertEquals('Expected Rule Name', $this->rule->getName());
    }
}
```

### Integration Tests for Coordinators

Coordinators should be tested with mocked DataProviders and Services:

```php
<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Tests\Integration\Coordinators;

use Nexus\HumanResourceOperations\Coordinators\YourCoordinator;
use Nexus\HumanResourceOperations\DataProviders\YourDataProvider;
use Nexus\HumanResourceOperations\Services\YourService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class YourCoordinatorTest extends TestCase
{
    private YourCoordinator $coordinator;
    private MockObject $dataProvider;
    private MockObject $service;

    protected function setUp(): void
    {
        $this->dataProvider = $this->createMock(YourDataProvider::class);
        $this->service = $this->createMock(YourService::class);

        $this->coordinator = new YourCoordinator(
            dataProvider: $this->dataProvider,
            service: $this->service,
            logger: new NullLogger()
        );
    }

    public function test_successfully_processes_request(): void
    {
        // Arrange - Set up mocks
        $this->dataProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(/* mock context */);

        $this->service
            ->expects($this->once())
            ->method('execute')
            ->willReturn(/* mock result */);

        // Act
        $result = $this->coordinator->process(/* request */);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->id);
    }
}
```

## Test Naming Conventions

Follow PHPUnit best practices:

- Test class: `{ClassName}Test`
- Test method: `test_{method_name}_{scenario}_{expected_result}`

Examples:
- `test_passes_when_all_interviews_completed`
- `test_fails_when_insufficient_balance`
- `test_detects_unusual_check_in_time`

## Assertions Best Practices

### Do's ✅
- Use specific assertions (`assertEquals`, `assertStringContainsString`)
- Test both success and failure paths
- Test edge cases and boundary conditions
- Assert on metadata when rules provide additional context
- Use descriptive assertion messages

### Don'ts ❌
- Don't use `assertTrue()` for everything
- Don't skip testing edge cases
- Don't test implementation details (private methods)
- Don't create complex test data when simple data suffices

## Mock Guidelines

### What to Mock
- ✅ External dependencies (repositories, API clients)
- ✅ Cross-package interfaces
- ✅ I/O operations (database, file system, network)

### What NOT to Mock
- ❌ Value Objects (DTOs)
- ❌ Pure functions
- ❌ Rules (use real instances in Coordinator tests)
- ❌ Simple data structures

## CI/CD Integration

Tests run automatically on:
- ✅ Pull request creation
- ✅ Merge to main branch
- ✅ Release tag creation

### Required Checks
- All tests must pass
- Code coverage must not decrease
- No lint errors

## Troubleshooting

### Common Issues

**Issue:** `Class not found` errors
```bash
# Solution: Regenerate autoload
composer dump-autoload
```

**Issue:** Tests fail with syntax errors
```bash
# Solution: Check PHP version (must be 8.3+)
php --version
```

**Issue:** Coverage not generated
```bash
# Solution: Install xdebug
sudo apt-get install php8.3-xdebug
```

## Contributing

When adding new components:
1. ✅ Write unit tests for Rules
2. ✅ Write integration tests for Coordinators
3. ✅ Ensure 80%+ code coverage
4. ✅ Follow naming conventions
5. ✅ Update this README if adding new test types

## References

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [SYSTEM_DESIGN_AND_PHILOSOPHY.md](../../SYSTEM_DESIGN_AND_PHILOSOPHY.md)
- [NEW_ARCHITECTURE.md](../NEW_ARCHITECTURE.md)
