# Nexus Analytics Package

Framework-agnostic analytics and business intelligence engine for the Nexus ERP ecosystem.

## Overview

The `Nexus\Analytics` package provides a comprehensive analytics engine that enables:

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
composer require nexus/analytics:"*@dev"
```

## Basic Usage

```php
use Nexus\Analytics\Services\AnalyticsManager;

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
