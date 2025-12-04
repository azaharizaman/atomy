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

## Documentation

Comprehensive documentation is available in the `docs/` folder:

- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, core concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, services, enums, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Framework-specific integration examples for Laravel and Symfony
- **[Examples](docs/examples/)** - Working code examples:
  - [Basic Usage](docs/examples/basic-usage.php) - Fundamental operations (balance checks, leave applications)
  - [Advanced Usage](docs/examples/advanced-usage.php) - Advanced scenarios (custom strategies, proration, carry-forward)

### Quick Links

| Document | Purpose |
|----------|---------|
| `REQUIREMENTS.md` | Complete functional requirements (35 requirements) |
| `IMPLEMENTATION_SUMMARY.md` | Implementation progress and metrics |
| `TEST_SUITE_SUMMARY.md` | Test coverage and planned tests |
| `VALUATION_MATRIX.md` | Package valuation metrics ($43,000 estimated value) |

## Contributing

See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for current implementation status and pending tasks.

## License

MIT
