# ComplianceOperations Orchestrator

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The **ComplianceOperations** orchestrator provides cross-package workflow coordination for compliance-related business processes within the Nexus ecosystem. It implements the Advanced Orchestrator Pattern with Saga-based workflows for distributed transaction management.

## Features

- **Saga Pattern Workflows**: Distributed transaction coordination with compensation logic
- **KYC Verification**: Know Your Customer onboarding workflows
- **AML Screening**: Anti-Money Laundering screening integration
- **Sanctions Checking**: Real-time sanctions list screening
- **Privacy Rights Management**: GDPR/CCPA compliance workflows
- **Risk Assessment**: Automated risk scoring and assessment
- **Transaction Monitoring**: Real-time suspicious activity detection

## Architecture

This orchestrator follows the **Three-Layer Architecture** defined in ARCHITECTURE.md:

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs  │
└─────────────────────────────────────────────────────────┘
                           ▲ implements
┌─────────────────────────────────────────────────────────┐
│                 ComplianceOperations (L2)               │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on: php, psr/log, psr/event-dispatcher │
│   - Saga-based workflow coordination                    │
└─────────────────────────────────────────────────────────┘
                           ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                     │
│   - KycVerification, Compliance, Party, Audit           │
│   - Publishable on their own (Common + PSR only)        │
└─────────────────────────────────────────────────────────┘
```

### Interface Segregation

Following ARCHITECTURE.md Section 3.1, this orchestrator:
- Defines its own interfaces in `Contracts/`
- Does NOT depend on atomic package interfaces directly
- Can be published as a standalone composer package
- Allows swapping atomic package implementations via adapters

## Installation

```bash
composer require nexus/compliance-operations
```

### Framework Integration

This package is **framework-agnostic** and depends only on PSR interfaces. The following examples show how to register the package's services in various frameworks. Adapt these examples to your application's dependency injection container.

#### Laravel

Create a service provider in your application to register ComplianceOperations services:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;
use Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface;
use Nexus\ComplianceOperations\Contracts\PrivacyServiceAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface;
use Nexus\ComplianceOperations\Adapters\AmlScreeningAdapter;
use Nexus\ComplianceOperations\Adapters\KycVerificationAdapter;
use Nexus\ComplianceOperations\Adapters\PrivacyServiceAdapter;
use Nexus\ComplianceOperations\Adapters\SanctionsCheckAdapter;

class ComplianceOperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register adapter implementations (singletons)
        $this->app->singleton(KycVerificationAdapterInterface::class, KycVerificationAdapter::class);
        $this->app->singleton(AmlScreeningAdapterInterface::class, AmlScreeningAdapter::class);
        $this->app->singleton(SanctionsCheckAdapterInterface::class, SanctionsCheckAdapter::class);
        $this->app->singleton(PrivacyServiceAdapterInterface::class, PrivacyServiceAdapter::class);

        // Workflow infrastructure (singletons)
        $this->app->singleton(SecureIdGeneratorInterface::class, function () {
            return new class implements SecureIdGeneratorInterface {
                public function generate(): string {
                    return bin2hex(random_bytes(32));
                }
            };
        });

        // Override with database-backed storage in production
        $this->app->singleton(WorkflowStorageInterface::class, function () {
            // TODO: Implement database-backed storage
            return new InMemoryWorkflowStorage(); // Or your implementation
        });

        // Register coordinators as singletons
        $coordinators = [
            \Nexus\ComplianceOperations\Coordinators\OnboardingCoordinator::class,
            \Nexus\ComplianceOperations\Coordinators\TransactionMonitoringCoordinator::class,
            \Nexus\ComplianceOperations\Coordinators\PeriodicReviewCoordinator::class,
            \Nexus\ComplianceOperations\Coordinators\PrivacyRightsCoordinator::class,
            \Nexus\ComplianceOperations\Coordinators\RiskAssessmentCoordinator::class,
            \Nexus\ComplianceOperations\Coordinators\ComplianceReportingCoordinator::class,
        ];

        foreach ($coordinators as $coordinator) {
            $this->app->singleton($coordinator);
        }

        // Register data providers
        $dataProviders = [
            \Nexus\ComplianceOperations\DataProviders\KycDataProvider::class,
            \Nexus\ComplianceOperations\DataProviders\AmlDataProvider::class,
            \Nexus\ComplianceOperations\DataProviders\SanctionsDataProvider::class,
            \Nexus\ComplianceOperations\DataProviders\RiskAssessmentDataProvider::class,
            \Nexus\ComplianceOperations\DataProviders\PrivacyDataProvider::class,
            \Nexus\ComplianceOperations\DataProviders\ComplianceAuditDataProvider::class,
        ];

        foreach ($dataProviders as $provider) {
            $this->app->singleton($provider);
        }

        // Register rules
        $rules = [
            \Nexus\ComplianceOperations\Rules\KycThresholdRule::class,
            \Nexus\ComplianceOperations\Rules\AmlRiskThresholdRule::class,
            \Nexus\ComplianceOperations\Rules\SanctionsMatchRule::class,
            \Nexus\ComplianceOperations\Rules\DataRetentionRule::class,
            \Nexus\ComplianceOperations\Rules\ReviewFrequencyRule::class,
        ];

        foreach ($rules as $rule) {
            $this->app->singleton($rule);
        }
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\ComplianceOperationsServiceProvider::class,
],
```

#### Symfony

Register services in your `services.yaml`:

```yaml
services:
    # Adapters
    Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface:
        class: Nexus\ComplianceOperations\Adapters\KycVerificationAdapter
    
    Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface:
        class: Nexus\ComplianceOperations\Adapters\AmlScreeningAdapter
    
    Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface:
        class: Nexus\ComplianceOperations\Adapters\SanctionsCheckAdapter
    
    Nexus\ComplianceOperations\Contracts\PrivacyServiceAdapterInterface:
        class: Nexus\ComplianceOperations\Adapters\PrivacyServiceAdapter
    
    # Workflow infrastructure
    Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface:
        class: App\SecureIdGenerator  # Your implementation
    
    Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface:
        class: App\WorkflowStorage  # Your database-backed implementation
    
    # Coordinators
    Nexus\ComplianceOperations\Coordinators\OnboardingCoordinator:
        shared: true
    
    # ... other coordinators
    
    # Data Providers
    Nexus\ComplianceOperations\DataProviders\KycDataProvider:
        shared: true
    
    # ... other data providers
    
    # Rules
    Nexus\ComplianceOperations\Rules\KycThresholdRule:
        shared: true
    
    # ... other rules
```

#### Plain PHP / PSR-11 Container

```php
use Nexus\ComplianceOperations\Adapters\KycVerificationAdapter;
use Nexus\ComplianceOperations\Adapters\AmlScreeningAdapter;
use Nexus\ComplianceOperations\Coordinators\OnboardingCoordinator;
use Psr\Container\ContainerInterface;

// Simple container setup example
$container->set(KycVerificationAdapterInterface::class, new KycVerificationAdapter());
$container->set(AmlScreeningAdapterInterface::class, new AmlScreeningAdapter());
// ... other adapters

$container->set(OnboardingCoordinator::class, new OnboardingCoordinator(
    $container->get(KycVerificationAdapterInterface::class),
    $container->get(AmlScreeningAdapterInterface::class),
    // ... other dependencies
));
```

#### Service Binding Summary

| Interface | Purpose |
|-----------|---------|
| `KycVerificationAdapterInterface` | KYC verification bridge |
| `AmlScreeningAdapterInterface` | AML screening bridge |
| `SanctionsCheckAdapterInterface` | Sanctions checking bridge |
| `PrivacyServiceAdapterInterface` | Privacy/GDPR services bridge |
| `SecureIdGeneratorInterface` | Secure ID generation |
| `WorkflowStorageInterface` | Saga state persistence |

## Usage

### Customer Onboarding Workflow

```php
use Nexus\ComplianceOperations\Coordinators\OnboardingCoordinator;
use Nexus\ComplianceOperations\DTOs\Kyc\KycVerificationContext;

class CustomerController
{
    public function __construct(
        private OnboardingCoordinator $coordinator
    ) {}

    public function onboard(Request $request): Response
    {
        $context = new KycVerificationContext(
            partyId: $request->partyId,
            dueDiligenceLevel: 'standard',
            partyData: $request->validated()
        );

        $result = $this->coordinator->initiateOnboarding($context);

        return $result->isSuccessful()
            ? response()->json(['status' => 'onboarded'])
            : response()->json(['errors' => $result->getErrors()], 422);
    }
}
```

### Transaction Monitoring

```php
use Nexus\ComplianceOperations\Coordinators\TransactionMonitoringCoordinator;

$result = $this->monitoringCoordinator->screenTransaction(
    transactionId: $transaction->id,
    amount: $transaction->amount,
    currency: $transaction->currency,
    partyId: $transaction->party_id
);

if ($result->requiresReview()) {
    // Escalate to compliance officer
    $this->escalationService->createAlert($result);
}
```

### Privacy Rights Request

```php
use Nexus\ComplianceOperations\Coordinators\PrivacyRightsCoordinator;

$result = $this->privacyCoordinator->processSubjectRequest(
    subjectId: $user->id,
    requestType: 'data_export', // or 'deletion', 'rectification'
    requestDetails: ['format' => 'json']
);

return $result->getResponsePackage();
```

## Workflows

### 1. Onboarding Compliance Workflow

Coordinates KYC, AML, sanctions, and risk assessment for new customers.

**Steps:**
1. KYC Verification
2. AML Screening
3. Sanctions Check
4. Risk Assessment
5. Compliance Approval

**Compensation:** Each step has rollback logic for failed workflows.

### 2. Transaction Monitoring Workflow

Real-time screening of transactions for suspicious activity.

**Steps:**
1. Transaction Screening
2. Risk Threshold Evaluation
3. Alert Generation
4. Escalation Handling

### 3. Periodic Review Workflow

Scheduled compliance reviews for existing customers.

**Steps:**
1. Review Trigger
2. Reverification
3. Risk Reassessment
4. Status Update

### 4. Privacy Rights Workflow

Handles GDPR/CCPA subject rights requests.

**Steps:**
1. Request Validation
2. Data Discovery
3. Subject Rights Processing
4. Response Generation

## Directory Structure

```
src/
├── Adapters/              # Bridge to atomic packages
├── Contracts/             # Interface definitions
├── Coordinators/          # Traffic management
├── DataProviders/         # Context aggregation
├── DTOs/                  # Data Transfer Objects
├── Enums/                 # Enumerations
├── Exceptions/            # Domain exceptions
├── Rules/                 # Business constraints
└── Workflows/             # Saga implementations
```

## Components

### Coordinators

| Coordinator | Purpose |
|-------------|---------|
| `OnboardingCoordinator` | Customer onboarding flow |
| `TransactionMonitoringCoordinator` | Transaction screening |
| `PeriodicReviewCoordinator` | Scheduled compliance reviews |
| `PrivacyRightsCoordinator` | Privacy rights requests |
| `RiskAssessmentCoordinator` | Risk assessment processes |
| `ComplianceReportingCoordinator` | Compliance reporting |

### DataProviders

| DataProvider | Aggregates |
|--------------|-----------|
| `KycDataProvider` | KYC verification data |
| `AmlDataProvider` | AML screening results |
| `SanctionsDataProvider` | Sanctions matches |
| `RiskAssessmentDataProvider` | Risk scores |
| `PrivacyDataProvider` | Privacy preferences |
| `ComplianceAuditDataProvider` | Audit trails |

### Rules

| Rule | Validates |
|------|-----------|
| `KycThresholdRule` | KYC thresholds |
| `AmlRiskThresholdRule` | AML risk limits |
| `SanctionsMatchRule` | Sanctions clearance |
| `DataRetentionRule` | Retention compliance |
| `ReviewFrequencyRule` | Review schedules |

## Testing

```bash
composer test
```

## Documentation

- [Implementation Summary](IMPLEMENTATION_SUMMARY.md) - Detailed implementation status
- [Architecture Guidelines](../../ARCHITECTURE.md) - Nexus architectural standards

## Contributing

Please see [CONTRIBUTING.md](../../CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

**Nexus** - Enterprise Resource Planning for the Modern Age
