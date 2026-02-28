---
name: test-engineer
description: Writes and maintains tests for Nexus packages and orchestrators following project patterns. Use when writing unit tests, integration tests, E2E tests, or when the user asks for test coverage.
---

# Test Engineer

## Test Structure

| Type | Location | Framework |
|------|----------|-----------|
| Unit | `packages/*/tests/Unit/`, `orchestrators/*/tests/Unit/` | PHPUnit |
| Integration | `packages/*/tests/Integration/`, `tests/Unit/Orchestrators/` | PHPUnit |
| E2E | Root `tests/` | Playwright |

## Mocking Rules

- **Mock interfaces, not implementations**
- Use `$this->createMock(Interface::class)` for dependencies
- Use in-memory stubs (e.g. `InMemoryStockRepository`) for integration tests
- Anonymous classes for simple doubles

## Unit Test Pattern

```php
public function test_it_executes_full_health_check_correctly(): void
{
    $laborService = $this->createMock(LaborHealthServiceInterface::class);
    $laborService->method('calculate')->willReturn(new LaborHealthDTO(...));

    $coordinator = new ProjectManagementOperationsCoordinator(
        $laborService,
        $expenseService,
        $timelineService,
        $billingService
    );

    $health = $coordinator->getFullHealth('proj-123');

    $this->assertEquals(50.0, $health->overallScore);
}
```

## Testing final readonly Classes

- Constructor injection enables mocking all dependencies
- No special handling needed – mock interfaces passed to constructor

## Coverage Targets

- **Packages**: >95%
- **Apps**: >85%

## Commands

```bash
# Package tests
cd packages/FeatureFlags && composer test

# With coverage
composer test -- --coverage-html coverage-html --coverage-text

# E2E (root)
pnpm test:e2e
pnpm test:e2e:api
pnpm test:e2e:laravel
```

## Fixtures

- **canary-atomy-api**: Doctrine Fixtures (tenants, users, feature flags)
- **laravel-nexus-saas**: Laravel factories

## Reference

- `docs/project/ARCHITECTURE.md` – Section 11: Testing final readonly classes
- `docs/project/NEXUS_SYSTEM_OVERVIEW.md` – Testing strategy
