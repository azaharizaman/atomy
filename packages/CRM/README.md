# Nexus CRM Package

A pure CRM domain logic package with zero business package dependencies for Nexus ERP.

## Overview

This package provides the core CRM (Customer Relationship Management) domain logic including:

- **Lead Management** - Lead scoring, qualification, and lifecycle management
- **Opportunity Management** - Sales opportunity tracking and stage transitions
- **Pipeline Management** - Sales pipeline configuration and analytics
- **Activity Management** - CRM activity tracking and history

## Architecture

This is an **Atomic Package** following Nexus ERP's package architecture:

- **Zero business package dependencies** - No dependencies on `nexus/party`, `nexus/sales`, etc.
- **Pure domain logic** - Contains only CRM-specific business rules and value objects
- **Interface-driven** - All external integrations through interfaces implemented by orchestrators

## Installation

```bash
composer require nexus/crm
```

## Features

### Lead Management
- Lead scoring engine with configurable rules
- Lead status lifecycle (New, Contacted, Qualified, Converted, Disqualified)
- Lead source tracking and attribution

### Opportunity Management
- Stage-based opportunity tracking
- Forecast probability calculation
- Stage transition validation

### Pipeline Management
- Configurable sales pipelines
- Pipeline stage definitions
- Pipeline analytics support

### Activity Management
- Activity type tracking (Call, Email, Meeting, Task, Note)
- Activity duration tracking
- Activity history and audit

## Usage

```php
use Nexus\CRM\Services\LeadScoringEngine;
use Nexus\CRM\Services\StageTransitionService;
use Nexus\CRM\ValueObjects\LeadScore;
use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\OpportunityStage;

// Lead scoring
$score = $leadScoringEngine->calculateScore($lead);

// Stage transitions
$opportunity = $stageTransitionService->advance($opportunity, OpportunityStage::Negotiation);
```

## Testing

```bash
./vendor/bin/phpunit
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

Azahari Zaman (azaharizaman@gmail.com)