# Factory Adoption Design for Atomy-Q API

**Date:** 2026-04-27  
**Author:** Atomy Agent  
**Status:** Approved

---

## Overview

Adopt Laravel Model Factories across the Atomy-Q API codebase following a dedicated-phase approach, with full pipeline realism for states and functionally identical seeder output.

---

## Goals

1. Enable test-first development with isolated factory-created fixtures
2. Reduce test suite runtime by eliminating `migrate:fresh --seed` dependency
3. Centralize model field definitions (DRY principle)
4. Provide semantic state transitions for domain workflows
5. Refactor PetrochemicalTenantSeeder to use factories with identical output

---

## Scope

### In Scope

- 21 model factories for domain entities
- 6 implementation phases
- Seeder refactor to use factories (maintaining identical output)
- Model trait additions (`HasFactory`) where missing

### Excluded Models

The following models are **excluded** from factory adoption as they are system-generated, infrastructure-only, or immutable:

| Model | Exclusion Reason |
|-------|------------------|
| AuditLog | System-generated via audit logger |
| DecisionTrailEntry | Immutable blockchain-like record |
| EvidenceBundle | Derived from approval |
| Notification | System-generated |
| Integration | Infrastructure configuration |
| IntegrationJob | Infrastructure queue |
| Role | Seed data, not test fixture |
| Permission | Seed data, not test fixture |
| Session | Auth infrastructure |
| MfaEnrollment | Auth infrastructure |
| MfaChallenge | Auth infrastructure |
| BackupCode | Auth infrastructure |
| ReportSchedule | Scheduled infrastructure |
| ReportRun | Scheduled output |
| Debrief | Summary/generated content |
| RiskItem | Rule-generated from vendor |
| Handoff | Derived from award |
| PolicyDefinitionRecord | System configuration |
| RequisitionSelectedVendor | Simple join table |
| NegotiationRound | Workflow state |

---

## Factory Inventory

### Priority Matrix

| Priority | Factory | Status States | Notes |
|----------|---------|---------------|-------|
| **HIGH** | TenantFactory | default, active, suspended | Foundation for all relationships |
| **HIGH** | UserFactory | default, active, locked, admin | Exists; extend with states + relationships |
| **HIGH** | ProjectFactory | default, active, planning, completed, on_hold, cancelled | RFQ container |
| **HIGH** | RfqFactory | draft, published, closed, awarded, cancelled | Most tested model; 5 states |
| **HIGH** | QuoteSubmissionFactory | uploaded, extracting, extracted, normalizing, ready, needs_review, failed | Complex state machine |
| **HIGH** | VendorFactory | default, approved, restricted, under_review, suspended | Procurement entity |
| **HIGH** | ApprovalFactory | pending, approved, rejected, snoozed | Workflow state machine |
| **HIGH** | ComparisonRunFactory | draft, final, preview | Payload-heavy; multiple states |
| **MEDIUM** | RfqLineItemFactory | default | Line items; often created with RFQ |
| **MEDIUM** | VendorInvitationFactory | pending, accepted, declined | RFQ-vendor relationship |
| **MEDIUM** | ProjectAclFactory | default | Project permissions |
| **MEDIUM** | AwardFactory | pending, signed_off, protested | Derived from approved comparison |
| **MEDIUM** | VendorEvidenceFactory | default, pending_review, reviewed | Compliance records |
| **MEDIUM** | VendorFindingFactory | default, open, resolved | Risk findings |
| **MEDIUM** | ScoringModelFactory | default, active | Evaluation framework |
| **MEDIUM** | NormalizationSourceLineFactory | default, with_override | Quote ingestion lines |
| **LOW** | RfqTemplateFactory | default, active | RFQ templates |
| **LOW** | TaskFactory | default, completed | Project tasks |
| **LOW** | ApprovalHistoryFactory | default | Approval audit trail |
| **LOW** | ScenarioFactory | draft, active | What-if scenarios |
| **LOW** | NormalizationConflictFactory | default, resolved | Quote normalization conflicts |

### Models Already Supporting Factories

| Model | Current State |
|-------|---------------|
| User | Has `HasFactory` trait, existing `UserFactory` |

### Models Requiring HasFactory Trait

- Tenant
- Project
- Rfq
- RfqLineItem
- Vendor
- VendorInvitation
- QuoteSubmission
- Approval
- ComparisonRun
- Award
- ProjectAcl
- ScoringModel
- VendorEvidence
- VendorFinding
- NormalizationSourceLine
- RfqTemplate
- Task
- ApprovalHistory
- Scenario
- NormalizationConflict

---

## Implementation Standards

### Base Factory Structure

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rfq;
use Illuminate\Database\Eloquent\Factories\Factory;

class RfqFactory extends Factory
{
    protected $model = Rfq::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_number' => 'RFQ-' . fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['Rotating equipment', 'Instrumentation', 'Valves & piping']),
            'department' => fake()->randomElement(['Maintenance', 'Projects', 'Operations']),
            'status' => 'draft',
            'owner_id' => User::factory(),
            'project_id' => Project::factory(),
            'estimated_value' => fake()->randomFloat(2, 10000, 500000),
            'savings_percentage' => fake()->randomFloat(2, 2.5, 18.0),
            'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
            'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
            'expected_award_at' => fake()->dateTimeBetween('+3 weeks', '+8 weeks'),
            'technical_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'financial_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 45 EOM', 'Net 60']),
            'evaluation_method' => 'weighted',
        ];
    }
}
```

### Status State Definition

States set status AND related field combinations for pipeline realism:

```php
public function draft(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'draft',
        'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
        'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
    ]);
}

public function published(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'published',
        'submission_deadline' => fake()->dateTimeBetween('+3 days', '+2 weeks'),
        'closing_date' => fake()->dateTimeBetween('+1 week', '+3 weeks'),
    ]);
}

public function closed(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'closed',
        'closing_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
    ]);
}

public function awarded(): static
{
    return $this->state(fn (array $attributes): array => [
        'status' => 'awarded',
        'closing_date' => fake()->dateTimeBetween('-3 weeks', '-1 week'),
    ])->afterCreating(function (Rfq $rfq): void {
        ComparisonRun::factory()
            ->for($rfq)
            ->for($rfq->owner, 'creator')
            ->final()
            ->create();
        
        Approval::factory()
            ->for($rfq)
            ->approved()
            ->create();
            
        Award::factory()
            ->for($rfq)
            ->signedOff()
            ->create();
    });
}

public function cancelled(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'cancelled',
        'closing_date' => fake()->dateTimeBetween('-4 weeks', '-2 weeks'),
    ]);
}
```

### Relationship Modifiers

```php
public function forProject(Project $project): static
{
    return $this->state(fn (array $attributes) => [
        'project_id' => $project->id,
        'tenant_id' => $project->tenant_id,
    ]);
}

public function ownedBy(User $user): static
{
    return $this->state(fn (array $attributes) => [
        'owner_id' => $user->id,
        'tenant_id' => $user->tenant_id,
    ]);
}

public function withLineItems(int $count = 3): static
{
    return $this->has(RfqLineItem::factory()->count($count));
}
```

---

## Relationship Hierarchy

```
TenantFactory
├── UserFactory::for($tenant)
│   └── ProjectFactory::for($tenant)->ownedBy($user)
│       └── RfqFactory::for($tenant)->for($project)->ownedBy($owner)
│           ├── RfqLineItemFactory::for($rfq) x N
│           ├── VendorInvitationFactory::for($rfq)->for($vendor)
│           └── QuoteSubmissionFactory::for($rfq)->for($vendor)
│               └── NormalizationSourceLineFactory::for($quote) x N
├── ScoringModelFactory::for($tenant)
│   └── ScoringPolicyFactory::for($scoringModel)
└── VendorFactory::for($tenant)
    ├── VendorEvidenceFactory::for($vendor) x N
    └── VendorFindingFactory::for($vendor) x N (risky vendors only)
        └── ApprovalFactory::for($comparisonRun)
        └── AwardFactory::for($comparisonRun)
            └── HandoffFactory::for($award) x N
```

---

## Phase Implementation

### Phase 1: Foundation
**Factories:** Tenant, User, Project, Rfq  
**Goal:** Establish base entity factories with all status states

1. Add `HasFactory` trait to Tenant, Project, Rfq models
2. Create TenantFactory with states: default, active, suspended
3. Extend existing UserFactory with states: active, locked, admin
4. Create ProjectFactory with states: active, planning, completed, on_hold, cancelled
5. Create RfqFactory with states: draft, published, closed, awarded, cancelled
6. Add relationship modifiers: forProject(), ownedBy(), withLineItems()

### Phase 2: Core Workflow
**Factories:** QuoteSubmission, Vendor, VendorInvitation, RfqLineItem  
**Goal:** Procurement flow factories

1. Add `HasFactory` trait to Vendor, QuoteSubmission, RfqLineItem, VendorInvitation models
2. Create VendorFactory with states: default, approved, restricted, under_review, suspended
3. Create QuoteSubmissionFactory with states: uploaded, extracting, extracted, normalizing, ready, needs_review, failed
4. Create RfqLineItemFactory
5. Create VendorInvitationFactory with states: pending, accepted, declined
6. Add relationship modifiers: forRfq(), forVendor(), withQuotes()

### Phase 3: Approval Flow
**Factories:** Approval, ComparisonRun, Award  
**Goal:** Workflow chain factories

1. Add `HasFactory` trait to Approval, ComparisonRun, Award models
2. Create ComparisonRunFactory with states: draft, final, preview
3. Create ApprovalFactory with states: pending, approved, rejected, snoozed
4. Create AwardFactory with states: pending, signed_off, protested
5. Implement `afterCreating()` callbacks for pipeline states (awarded RFQ auto-creates comparison, approval, award)

### Phase 4: Supporting
**Factories:** ProjectAcl, ScoringModel, VendorEvidence, VendorFinding  
**Goal:** Permissions and compliance factories

1. Add `HasFactory` trait to ProjectAcl, ScoringModel, VendorEvidence, VendorFinding models
2. Create ProjectAclFactory
3. Create ScoringModelFactory with states: default, active
4. Create ScoringPolicyFactory
5. Create VendorEvidenceFactory with states: default, pending_review, reviewed
6. Create VendorFindingFactory with states: default, open, resolved

### Phase 5: Specialized
**Factories:** NormalizationSourceLine, RfqTemplate, Task, ApprovalHistory, Scenario, NormalizationConflict  
**Goal:** Ingestion and miscellaneous factories

1. Add `HasFactory` trait to remaining models
2. Create NormalizationSourceLineFactory with states: default, with_override
3. Create RfqTemplateFactory with states: default, active
4. Create TaskFactory with states: default, completed
5. Create ApprovalHistoryFactory
6. Create ScenarioFactory with states: draft, active
7. Create NormalizationConflictFactory with states: default, resolved

### Phase 6: Seeder Refactor
**Goal:** Rewrite PetrochemicalTenantSeeder using factories

1. Replace all `DB::table()->insert()` calls with factory calls
2. Maintain identical output:
   - 1 tenant (Nordfjord Process Chemicals)
   - 8 users with realistic names
   - 12 projects with varied statuses
   - 56 RFQs across all status types
   - 28 vendors (including 8 marked risky)
   - Full quote pipeline with normalization data
   - Comparison runs, approvals, and awards for closed/awarded RFQs
3. Target: ~150 LOC (from ~1200 LOC)

---

## Seeder Refactor Example

### Before (Current)
```php
// PetrochemicalTenantSeeder.php ~1200 lines
private function insertRfqAndLines(array $ctx): array
{
    $rfqId = (string) $ctx['rfq_id'];
    DB::table('rfqs')->insert([
        'id' => $rfqId,
        'tenant_id' => $this->tenantId,
        'project_id' => $ctx['project_id'],
        'rfq_number' => $ctx['rfq_number'],
        'title' => $ctx['title'],
        // ... 20+ fields manually typed
        'created_at' => $this->now,
        'updated_at' => $this->now,
    ]);
    
    $lineIds = [];
    for ($j = 1; $j <= $ctx['line_count']; $j++) {
        $lid = (string) Str::ulid();
        DB::table('rfq_line_items')->insert([
            'id' => $lid,
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfqId,
            // ... line item fields
        ]);
    }
    // ...
}
```

### After (Target)
```php
// PetrochemicalTenantSeeder.php ~150 lines
public function run(): void
{
    $tenant = Tenant::factory()->create([
        'code' => 'NORDFJORD',
        'name' => 'Nordfjord Process Chemicals AS',
        // ...
    ]);
    
    $users = User::factory()
        ->count(8)
        ->sequence([
            ['name' => 'Ingrid Solberg', 'email' => 'user1@example.com'],
            ['name' => 'Erik Haugen', 'email' => 'user2@example.com'],
            // ...
        ])
        ->for($tenant)
        ->create();
    
    $projects = Project::factory()
        ->count(12)
        ->sequence(...$projectDefinitions)
        ->for($tenant)
        ->for($users->first(), 'projectManager')
        ->create();
    
    foreach ($rfqContexts as $ctx) {
        $rfq = match ($ctx['status']) {
            'draft' => Rfq::factory()->draft()->create([...]),
            'published' => Rfq::factory()->published()->create([...]),
            'awarded' => Rfq::factory()->awarded()->create([...]),
            // ...
        };
    }
}
```

---

## Acceptance Criteria

1. **Test Isolation:** Each factory can create valid model instances without database dependencies beyond the schema
2. **State Consistency:** Each status state produces field combinations that pass domain validation rules
3. **Relationship Integrity:** Factory-created models satisfy foreign key constraints
4. **Pipeline Realism:** Workflow states (e.g., `awarded()`) auto-create related entities
5. **Seeder Parity:** Phase 6 refactored seeder produces identical data to current seeder
6. **Performance:** Factory creation adds no more than 50ms overhead per instance vs raw inserts

---

## File Locations

- Factories: `database/factories/{Model}Factory.php`
- Seeder: `database/seeders/PetrochemicalTenantSeeder.php`
- Models: `app/Models/{Model}.php`
- Module summaries: `app/Modules/{Module}/IMPLEMENTATION_SUMMARY.md`

## Completion Criteria

When behavior or public contracts change, update `IMPLEMENTATION_SUMMARY.md` in the affected module with the relevant fields (purpose, contracts, factory states, relationships).