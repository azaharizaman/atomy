# Nexus\Compliance

Operational compliance engine for enforcing business rules, SOD (Segregation of Duties), and compliance scheme requirements (ISO 14001, SOX, GDPR, etc.).

## Overview

The Compliance package provides a framework-agnostic engine for managing compliance schemes and enforcing Segregation of Duties (SOD) rules across business transactions. It is designed to integrate with various ERP modules to ensure regulatory compliance and internal controls.

## Features

- **Compliance Scheme Management**: Activate, deactivate, and configure compliance schemes (ISO 14001, SOX, GDPR, HIPAA, PCI_DSS)
- **SOD Rule Engine**: Define and enforce segregation of duties rules
- **Violation Tracking**: Log and monitor compliance violations
- **Configuration Auditing**: Validate required features and settings
- **Multi-Severity Levels**: Critical, High, Medium, Low
- **Framework-Agnostic**: Pure PHP with no Laravel dependencies

## Installation

```bash
composer require nexus/compliance
```

## Architecture

This package follows the Nexus architecture principles:

- **Framework-Agnostic**: No Laravel dependencies in core services
- **Contract-Driven**: All external dependencies defined via interfaces
- **Value Objects**: Immutable objects for domain concepts (SeverityLevel)
- **Repository Pattern**: Persistence abstraction via repository interfaces

### Package Structure

```
packages/Compliance/
├── composer.json
├── LICENSE
├── README.md
└── src/
    ├── Contracts/                    # Interfaces
    │   ├── ComplianceManagerInterface.php
    │   ├── ComplianceSchemeInterface.php
    │   ├── ComplianceSchemeRepositoryInterface.php
    │   ├── SodManagerInterface.php
    │   ├── SodRuleInterface.php
    │   ├── SodRuleRepositoryInterface.php
    │   ├── SodViolationInterface.php
    │   └── SodViolationRepositoryInterface.php
    ├── Services/                     # Business logic
    │   ├── ComplianceManager.php
    │   └── SodManager.php
    ├── ValueObjects/                 # Immutable domain objects
    │   └── SeverityLevel.php
    └── Exceptions/                   # Domain exceptions
        ├── DuplicateRuleException.php
        ├── InvalidSchemeException.php
        ├── RuleNotFoundException.php
        ├── SchemeAlreadyActiveException.php
        ├── SchemeNotFoundException.php
        └── SodViolationException.php
```

## Usage

### Compliance Scheme Management

```php
use Nexus\Compliance\Services\ComplianceManager;

// Activate a compliance scheme
$schemeId = $complianceManager->activateScheme(
    tenantId: 'tenant-123',
    schemeName: 'ISO14001',
    configuration: [
        'audit_frequency' => 'quarterly',
        'enable_environmental_tracking' => true,
    ]
);

// Check if scheme is active
$isActive = $complianceManager->isSchemeActive('tenant-123', 'ISO14001');

// Get all active schemes
$activeSchemes = $complianceManager->getActiveSchemes('tenant-123');

// Deactivate a scheme
$complianceManager->deactivateScheme('tenant-123', 'ISO14001');
```

### SOD Rule Management

```php
use Nexus\Compliance\Services\SodManager;
use Nexus\Compliance\ValueObjects\SeverityLevel;

// Create a SOD rule
$ruleId = $sodManager->createRule(
    tenantId: 'tenant-123',
    ruleName: 'Purchase Order Approval',
    transactionType: 'purchase_order',
    severityLevel: SeverityLevel::CRITICAL,
    creatorRole: 'purchaser',
    approverRole: 'manager'
);

// Validate a transaction
try {
    $sodManager->validateTransaction(
        tenantId: 'tenant-123',
        transactionType: 'purchase_order',
        creatorId: 'user-001',
        approverId: 'user-002'
    );
} catch (SodViolationException $e) {
    // Handle violation
    echo $e->getMessage();
}

// Get all violations
$violations = $sodManager->getViolations(
    tenantId: 'tenant-123',
    from: new DateTimeImmutable('2025-01-01'),
    to: new DateTimeImmutable('2025-12-31')
);
```

## Supported Compliance Schemes

- **ISO14001**: Environmental Management System
- **SOX**: Sarbanes-Oxley Act (financial controls)
- **GDPR**: General Data Protection Regulation
- **HIPAA**: Health Insurance Portability and Accountability Act
- **PCI_DSS**: Payment Card Industry Data Security Standard

## Severity Levels

```php
use Nexus\Compliance\ValueObjects\SeverityLevel;

SeverityLevel::CRITICAL;  // Priority: 4, Requires immediate action
SeverityLevel::HIGH;      // Priority: 3, Requires immediate action
SeverityLevel::MEDIUM;    // Priority: 2
SeverityLevel::LOW;       // Priority: 1
```

## Integration with Applications

This package defines contracts that must be implemented by the consuming application:

1. **Repository Implementations**: Implement all repository interfaces with Eloquent models
2. **Entity Implementations**: Implement all entity interfaces
3. **Database Migrations**: Create required tables in application layer
4. **Service Provider Bindings**: Bind interfaces to implementations in IoC container

### Required Tables (Application Layer)

```sql
-- Compliance schemes
compliance_schemes (id, tenant_id, scheme_name, is_active, activated_at, configuration, created_at, updated_at)

-- SOD rules
sod_rules (id, tenant_id, rule_name, transaction_type, severity_level, creator_role, approver_role, is_active, created_at, updated_at)

-- SOD violations
sod_violations (id, tenant_id, rule_id, transaction_id, transaction_type, creator_id, approver_id, violated_at, created_at)
```

## Dependencies

- **PHP**: ^8.3
- **psr/log**: ^3.0 (for logging interface)

## Development

### Running Tests

```bash
composer test
```

### Code Style

This package follows PSR-12 coding standards.

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please follow the Nexus architecture principles:

1. Keep the package framework-agnostic
2. Define all dependencies via interfaces
3. Use immutable Value Objects for domain concepts
4. Place all business logic in services
5. No database access or migrations in this package

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with basic setup
- **[API Reference](docs/api-reference.md)** - Complete API documentation for all interfaces and services
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - Simple use cases
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - Complex scenarios

### Additional Resources

- **[Requirements](REQUIREMENTS.md)** - Detailed requirements traceability (62 requirements)
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Implementation progress and design decisions
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and testing strategy
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation and ROI analysis

## Support

For issues, questions, or contributions, please refer to the main Nexus monorepo documentation.
