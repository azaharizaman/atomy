# Nexus CRM Operations Orchestrator

Cross-package workflow coordination for CRM operations in Nexus ERP.

## Overview

This orchestrator package coordinates workflows that span multiple packages:

- **Lead Conversion** - Converting leads to opportunities with customer creation
- **Opportunity Closing** - Closing deals with quotation and order integration
- **Pipeline Analytics** - Cross-package analytics and reporting

## Architecture

This is an **Orchestrator Package** following Nexus ERP's package architecture:

- **Cross-package coordination** - Coordinates between CRM, Party, Sales, Workflow, and Notifier packages
- **Workflow management** - Manages complex multi-step business processes
- **Event-driven** - Responds to and emits domain events
- **Rule evaluation** - Applies business rules across package boundaries

## Dependencies

### Required
- PHP ^8.3
- psr/log:^3
- psr/event-dispatcher:^1

### Suggested (for full functionality)
- `nexus/crm` - CRM domain operations
- `nexus/party` - Customer data integration
- `nexus/sales` - Quotation and sales integration
- `nexus/workflow` - Approval workflows
- `nexus/notifier` - Notification services

## Installation

```bash
composer require nexus/crm-operations
```

## Features

### Coordinators
- **LeadConversionCoordinator** - Orchestrates lead to opportunity conversion
- **OpportunityCloseCoordinator** - Manages deal closing process
- **PipelineAnalyticsCoordinator** - Aggregates pipeline analytics

### Workflows
- **LeadToOpportunityWorkflow** - Multi-step lead conversion workflow
- **DealApprovalWorkflow** - Deal approval process with escalations
- **EscalationWorkflow** - SLA breach and escalation handling

### Rules
- **LeadQualificationRule** - Validates lead qualification criteria
- **OpportunityApprovalRule** - Determines approval requirements
- **SLABreachRule** - Detects and handles SLA breaches

### Integration Services
- **PartyIntegrationService** - Customer data integration
- **SalesIntegrationService** - Quotation and order integration
- **AnalyticsIntegrationService** - Analytics data integration

## Usage

```php
use Nexus\CRMOperations\Coordinators\LeadConversionCoordinator;
use Nexus\CRMOperations\Workflows\LeadToOpportunityWorkflow;

// Convert a lead with full orchestration
$coordinator = new LeadConversionCoordinator(
    $leadQuery,
    $leadPersist,
    $opportunityPersist,
    $customerProvider,
    $eventDispatcher
);

$result = $coordinator->convertLead($leadId);
```

## Testing

```bash
./vendor/bin/phpunit
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

Azahari Zaman (azaharizaman@gmail.com)