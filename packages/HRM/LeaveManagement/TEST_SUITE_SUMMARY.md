# Test Suite Summary: LeaveManagement

**Package:** `Nexus\LeaveManagement`  
**Last Updated:** 2025-12-04  
**Test Framework:** PHPUnit 10.x

---

## Test Suite Status

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Unit Tests | 0 | 0 | ⏳ Planned |
| Integration Tests | 0 | 0 | ⏳ Planned |
| **TOTAL** | **0** | **0** | **⏳ Planned** |

---

## Coverage Summary

| Metric | Value | Target |
|--------|-------|--------|
| Line Coverage | 0% | 80% |
| Branch Coverage | 0% | 75% |
| Method Coverage | 0% | 90% |
| Class Coverage | 0% | 100% |

---

## Planned Test Structure

### Unit Tests (`tests/Unit/`)

#### Services Tests

**LeaveBalanceCalculatorTest.php** (Planned)
```php
- test_calculate_balance_returns_available_days()
- test_calculate_balance_with_no_transactions_returns_entitlement()
- test_calculate_balance_deducts_approved_leaves()
- test_calculate_balance_excludes_cancelled_leaves()
- test_calculate_accrual_for_monthly_strategy()
- test_calculate_accrual_for_fixed_allocation()
- test_calculate_balance_with_multiple_leave_types()
```

**MonthlyAccrualStrategyTest.php** (Planned)
```php
- test_calculate_returns_monthly_accrual_amount()
- test_calculate_prorates_for_partial_month()
- test_calculate_respects_maximum_cap()
- test_get_name_returns_monthly()
```

**FixedAllocationStrategyTest.php** (Planned)
```php
- test_calculate_returns_full_allocation_on_start_date()
- test_calculate_returns_zero_before_allocation_date()
- test_calculate_prorates_for_mid_year_join()
- test_get_name_returns_fixed_allocation()
```

#### Enums Tests

**LeaveStatusTest.php** (Planned)
```php
- test_draft_status_exists()
- test_pending_status_exists()
- test_approved_status_exists()
- test_rejected_status_exists()
- test_cancelled_status_exists()
- test_status_values_are_strings()
```

**LeaveCategoryTest.php** (Planned)
```php
- test_all_standard_categories_exist()
- test_category_values_are_lowercase()
```

**LeaveDurationTest.php** (Planned)
```php
- test_full_day_exists()
- test_half_day_am_exists()
- test_half_day_pm_exists()
- test_hours_exists()
```

#### Exceptions Tests

**InsufficientBalanceExceptionTest.php** (Planned)
```php
- test_exception_message_contains_employee_id()
- test_exception_message_contains_requested_days()
- test_exception_message_contains_available_days()
```

**LeaveNotFoundExceptionTest.php** (Planned)
```php
- test_exception_message_contains_leave_id()
```

### Integration Tests (`tests/Integration/`)

**LeaveWorkflowTest.php** (Planned)
```php
- test_complete_leave_application_flow()
- test_balance_updates_on_approval()
- test_balance_restored_on_cancellation()
- test_accrual_processing_updates_balances()
- test_carry_forward_at_year_end()
```

---

## Test Data Fixtures (Planned)

### Employee Fixtures
```php
- Employee with full entitlement
- Employee with partial year (mid-year join)
- Employee with multiple leave types
- Employee with zero balance
```

### Leave Type Fixtures
```php
- Annual Leave (monthly accrual, 14 days/year)
- Sick Leave (fixed allocation, 14 days/year)
- Maternity Leave (fixed allocation, 90 days)
- Paternity Leave (fixed allocation, 7 days)
- Unpaid Leave (no limit)
```

### Leave Request Fixtures
```php
- Pending leave request
- Approved leave request
- Rejected leave request
- Cancelled leave request
- Overlapping leave requests
```

---

## Testing Strategy

### Unit Testing Approach
1. **Mock all dependencies** - Repository interfaces mocked for isolation
2. **Test edge cases** - Zero balance, negative amounts, date boundaries
3. **Test validation logic** - Policy rules, balance checks
4. **Test enum values** - All enum cases covered

### Integration Testing Approach
1. **In-memory repositories** - Use array-based implementations
2. **Complete workflows** - Application to approval flow
3. **Accrual processing** - Month-end processing scenarios
4. **Year-end scenarios** - Carry-forward and reset

### Test Doubles Strategy
```php
// Repository mocks
$leaveRepository = $this->createMock(LeaveRepositoryInterface::class);
$balanceRepository = $this->createMock(LeaveBalanceRepositoryInterface::class);

// Strategy mocks
$accrualStrategy = $this->createMock(AccrualStrategyInterface::class);
```

---

## Running Tests

```bash
# Run all tests
cd packages/HRM/LeaveManagement
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run specific test file
./vendor/bin/phpunit tests/Unit/Services/LeaveBalanceCalculatorTest.php

# Run specific test method
./vendor/bin/phpunit --filter test_calculate_balance_returns_available_days
```

---

## Continuous Integration

### GitHub Actions Configuration (Planned)
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: ./vendor/bin/phpunit --coverage-clover coverage.xml
```

---

## Test Quality Metrics (Target)

| Metric | Target | Current |
|--------|--------|---------|
| Mutation Score | >80% | N/A |
| Test-to-Code Ratio | >0.5:1 | N/A |
| Average Assertions per Test | >2 | N/A |
| Flaky Test Rate | 0% | N/A |

---

## Known Test Limitations

1. **No tests currently exist** - Test implementation pending
2. **No database tests** - Package is framework-agnostic
3. **No external service tests** - No external dependencies

---

## Next Steps

1. Create PHPUnit configuration file (`phpunit.xml`)
2. Implement unit tests for existing services
3. Create test fixtures for common scenarios
4. Add integration tests for workflows
5. Set up CI pipeline for automated testing

---

**Test Suite Maintainer:** Nexus Architecture Team  
**Review Frequency:** Per release
