# Nexus Analytics Package

Framework-agnostic analytics and business intelligence engine for the Nexus ERP ecosystem.

## Overview

The `Nexus\QueryEngine` package provides a comprehensive analytics engine that enables:

- **Query Execution**: Execute complex analytical queries with ACID compliance
- **Predictive Modeling**: Machine learning integration for forecasting and predictions
- **Data Aggregation**: Parallel data source merging and aggregation
- **Permission Management**: Role-based access control with delegation chains
- **Analytics History**: Immutable audit trail of all analytics operations
- **Guard Conditions**: Pre-execution validation and security checks

## Key Features

- ✅ Framework-agnostic design (no Laravel dependencies)
- ✅ ACID-compliant query execution with transaction support
- ✅ Parallel data source processing
- ✅ Built-in security with RBAC integration
- ✅ Tenant isolation support
- ✅ Predictive model management with drift detection
- ✅ Before/after hooks for extensibility
- ✅ Database-driven analytics definitions (JSON)
- ✅ Comprehensive error handling and retry logic

## Installation

In the Atomy application:

```bash
composer require nexus/query-engine:"*@dev"
```

## Basic Usage

```php
use Nexus\QueryEngine\Services\AnalyticsManager;

// Execute a query
$result = $analyticsManager->runQuery($queryDefinition, $context);

// Check permissions
$canExecute = $analyticsManager->can($userId, 'execute', $queryId);

// Get analytics history
$history = $analyticsManager->getHistory($entityId, $limit);
```

## Architecture

This package follows the Nexus architecture principles:

- **Logic in Packages**: All business logic is framework-agnostic
- **Implementation in Applications**: Atomy provides concrete implementations
- **Contract-Driven**: All dependencies defined via interfaces

## Documentation

See `docs/ANALYTICS_IMPLEMENTATION.md` for complete implementation details.

## License

MIT License - see LICENSE file for details.

## Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start and basic usage
- **[API Reference](docs/api-reference.md)** - Complete interface documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples

### Package Specifications
- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Implementation progress and architecture decisions
- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Detailed package requirements (84 requirements)
- **[TEST_SUITE_SUMMARY.md](TEST_SUITE_SUMMARY.md)** - Test coverage plan (135+ tests planned)
- **[VALUATION_MATRIX.md](VALUATION_MATRIX.md)** - Package valuation ($250K, 2,083% ROI)

### Code Examples
- **[Basic Usage](docs/examples/basic-usage.php)** - Simple queries and common use cases
- **[Advanced Usage](docs/examples/advanced-usage.php)** - Multi-dimensional analysis, cohort analysis

