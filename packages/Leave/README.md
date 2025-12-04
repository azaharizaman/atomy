# Nexus\Leave

Framework-agnostic leave management package for HR systems.

## Features

- ✅ Leave application and approval workflow
- ✅ Leave balance tracking and calculation
- ✅ Leave type management (annual, sick, unpaid, etc.)
- ✅ Leave policy enforcement
- ✅ Overlap detection
- ✅ Pro-rated leave allocation
- ✅ Leave carry-forward rules

## Installation

```bash
composer require nexus/leave
```

## Usage

```php
use Nexus\Leave\Contracts\LeaveManagerInterface;

$leaveManager->apply(
    employeeId: 'emp-123',
    leaveTypeId: 'annual',
    startDate: new \DateTimeImmutable('2024-12-10'),
    endDate: new \DateTimeImmutable('2024-12-15'),
    reason: 'Family vacation'
);
```

## Contracts

- `LeaveManagerInterface` - Core leave management operations
- `LeaveQueryInterface` - Read operations (CQRS)
- `LeavePersistInterface` - Write operations (CQRS)
- `LeaveBalanceCalculatorInterface` - Leave balance calculations
- `LeaveValidatorInterface` - Leave policy validation

## Enums

- `LeaveStatus` - PENDING, APPROVED, REJECTED, CANCELLED
- `LeaveType` - ANNUAL, SICK, UNPAID, MATERNITY, PATERNITY, COMPASSIONATE

## License

MIT
