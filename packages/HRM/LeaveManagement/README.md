# Nexus Leave Management

**Pure domain logic for leave management operations**

## Features

- Leave application and approval
- Leave balance calculation
- Accrual strategies (monthly, fixed allocation, custom law-adjusted)
- Carry-forward processing
- Proration calculations
- Leave encashment
- Overlap detection
- Policy validation

## Installation

```bash
composer require nexus/leave-management
```

## Key Concepts

### Contracts
- `LeaveRepositoryInterface` - Leave data access
- `LeaveBalanceRepositoryInterface` - Balance tracking
- `LeaveCalculatorInterface` - Balance calculations
- `AccrualStrategyInterface` - Accrual strategy contract
- `LeavePolicyInterface` - Policy enforcement

### Entities
- `Leave` - Leave record entity
- `LeaveType` - Leave type definition
- `LeaveBalance` - Employee leave balance
- `LeaveEntitlement` - Leave entitlement rules

### Services
- `LeaveBalanceCalculator` - Balance computation
- `LeaveAccrualEngine` - Accrual processing
- `LeavePolicyValidator` - Policy compliance
- `LeaveOverlapDetector` - Overlap detection
- `CarryForwardProcessor` - Year-end carry-forward

## Architecture

This is a **framework-agnostic domain package**:
- Pure PHP 8.3+
- No framework dependencies
- Contract-driven design
- All dependencies via interfaces

## License

MIT
