This is not for agent read. exclude this file from agent context

1. Need to add on later

a. manual override governance. a way to categorised low risk, high risk and critical override changes to minimize user frictions.
That’s the right question — because **tiered governance only works when risk classification is concrete, explainable, and automatic**.

If users can’t predict why something is low/high/critical, they lose trust.

So in real business systems, risk should be based on **business impact**, not technical complexity.

---

# Core Principle

A change becomes risky when it can affect one or more of these:

1. **Money** – changes commercial value
2. **Award fairness** – changes supplier ranking / comparison outcome
3. **Quantity / scope** – changes what is being bought
4. **Compliance** – violates policy / approval rules
5. **Traceability** – difficult to justify later

That’s how the engine should score risk.

---

# Practical Tier Model for Quotation Normalization

# 🟢 Low Risk Changes

Changes that are clerical, formatting, or operationally harmless.

Examples:

* Fix typo in description
* Change “pcs” → “pieces” where equivalent
* Add missing punctuation
* Supplier name capitalization
* Reorder text labels
* Description cleanup without semantic change
* Accept AI suggestion with high confidence

## Governance Action

* Reason code only
* Instant save
* No manager approval
* Logged silently

---

# 🟠 High Risk Changes

Changes that may influence pricing comparison or mapping, but are still routine.

Examples:

* Change RFQ mapping from Line 4 to Line 5
* Adjust quantity from 100 to 120
* Change UOM from box to pcs
* Correct unit price RM12.50 → RM13.20
* Override medium-confidence AI mapping
* Split bundled supplier line into 2 RFQ lines

## Governance Action

* Mandatory reason code
* Optional note
* Highlight in audit log
* Included in supervisor review queue

---

# 🔴 Critical Changes

Changes that can materially affect commercial decisions, fairness, compliance, or award recommendation.

Examples:

* Price change > 10%
* Total quote value change > RM10,000
* Mapping premium item to cheaper RFQ spec
* Delete a quoted line item
* Override low-confidence AI into conflicting manual decision
* Change after comparison freeze
* Change affecting winning supplier ranking
* Manual award-impacting normalization near tender close date

## Governance Action

* Mandatory reason code + detailed note
* Supervisor approval required
* Dual authorization possible
* Immediate alert
* Full audit spotlight

---

# How the System Decides Automatically

Use rules engine + scoring model.

## Example Logic

```text
If description typo only = LOW

If unit price changed <3% = HIGH

If unit price changed >10% = CRITICAL

If mapping changes supplier rank = CRITICAL

If post-freeze edit = CRITICAL

If AI confidence >95% and accepted unchanged = LOW

If AI confidence <50% manually overridden = HIGH

If line deleted = CRITICAL
```

---

# Better Method: Weighted Risk Score

Instead of only hard rules, assign points.

| Trigger                  | Score |
| ------------------------ | ----: |
| Price changed            |   +30 |
| Quantity changed         |   +25 |
| Mapping changed          |   +35 |
| Impacts supplier ranking |   +50 |
| After freeze             |   +70 |
| Low confidence AI        |   +15 |
| Large contract value     |   +40 |

Then:

* 0–29 = Low
* 30–69 = High
* 70+ = Critical

This is more scalable.

---

# Example in Your System

## Scenario A

Buyer changes:

> “Steel Bolt M8” → “Steel Bolt M8 Zinc”

No price impact.

➡ **Low Risk**

---

## Scenario B

Buyer changes:

> Qty 500 → Qty 650

May affect totals.

➡ **High Risk**

---

## Scenario C

Buyer remaps supplier premium industrial pump to a lower spec RFQ line, making them cheapest bidder.

➡ **Critical**

Because award outcome may change.

---

# Important Rule: Risk Should Consider Contract Value

Same change can have different risk.

Example:

Price change RM0.20:

* On 10 units = low
* On 500,000 units = critical

So always include:

* quantity
* total line value
* contract size

---

# Recommended Governance Matrix for Atomy-Q

| Change Type           | Low   | High   | Critical           |
| --------------------- | ----- | ------ | ------------------ |
| Description edit      | ✔     |        |                    |
| UOM change            |       | ✔      | if major           |
| Quantity change       |       | ✔      | if > threshold     |
| Price change          | small | medium | large              |
| Mapping change        |       | ✔      | if affects ranking |
| Delete line           |       |        | ✔                  |
| Post-freeze edit      |       |        | ✔                  |
| Award outcome changed |       |        | ✔                  |

---

# UX Tip (Very Important)

Never show users “Risk Score = 72”.

Show:

* Low Impact Change
* Review Required
* Approval Required

Simple language wins adoption.

---

# Governance Gold Standard

When user clicks save:

> This change may affect supplier ranking. Manager approval required.

That instantly creates trust.

---

# What I’d Recommend for Alpha Release

Start simple:

## Low Risk

* text edits
* formatting
* high confidence accepts

## High Risk

* mapping changes
* qty/UOM changes
* moderate price changes

## Critical

* ranking impact
* freeze-stage edits
* large value changes
* deletions

Then refine using real usage data later.

---

# My Honest View

Your earlier spec becomes much stronger once tiered governance is added, because now:

* buyers move fast on harmless edits
* managers focus only on risky changes
* audit effort is proportional
* enterprise buyers trust system more


--------------------------

Laravel-Way Opportunities for Atomy-Q API
High-Impact Improvements (DX Priority)
Recommendation
1. Expand FormRequest Validation
2. Add Tenant Global Scope
3. Laravel Policies for Authorization
4. Standardize API Resources & Collections
5. Split Routes File
Architectural Considerations
Don't add these the "naive" way - integrate with existing Nexus architecture:
Laravel Feature          →  Nexus Integration
─────────────────────────────────────────────────
FormRequest::authorize() →  Combine with NexusPermission middleware
Laravel Policy           →  Wrap ProjectAclService, don't replace
Global Tenant Scope      →  Use tenant context from existing TenantContext middleware
API Resources            →  Keep transformers in Http/Resources, align with DTOs
Specific Quick Wins
1. Tenant Global Scope (prevents query leaks):
// app/Scopes/TenantScope.php
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    }
}
2. Standardize Response Trait:
trait ApiResponse
{
    public function success(mixed $data, int $status = 200): JsonResponse
    public function created(mixed $data): JsonResponse
    public function error(string $message, int $status): JsonResponse
}
3. Route File Decomposition:
// routes/api.php
require __DIR__.'/api/v1/rfqs.php';
require __DIR__.'/api/v1/projects.php';
require __DIR__.'/api/v1/vendors.php';
Recommendation
Start with Tenant Global Scope + FormRequest expansion - these prevent bugs and standardize validation with minimal refactoring risk. Authorization (Policies) should wrap existing ProjectAclService rather than replace it.
▣  Build · Big Pickle · 2m 35s
how about Factories. I saw there is only one factories and seed data are being manually hand coded. Since atomy-q is still in active development do you think factories adoption can increase the code mentainability?
Thinking: The user is asking about Model Factories in Laravel and whether they should adopt more of them. They noted:
1. There's only one factory currently
2. Seed data is manually hand-coded
3. The app is in active development
Let me check the current state of factories and seeders in the API to give a concrete assessment.
✱ Glob "**/database/factories/**/*.php" in apps/atomy-q/API (1 match)
✱ Glob "**/database/seeders/**/*.php" in apps/atomy-q/API (2 matches)
→ Read apps/atomy-q/API/database/seeders 
Thinking: Let me read both seeders to see the manual approach they use.
→ Read apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php 
→ Read apps/atomy-q/API/database/factories/UserFactory.php 
→ Read apps/atomy-q/API/database/seeders/DatabaseSeeder.php 
Thinking: The user is asking about factories and whether their adoption can increase code maintainability given the current state of the seeder.
Looking at the code:
1. PetrochemicalTenantSeeder.php - 1200+ lines of hand-coded seeder that:
   - Inserts raw SQL via DB::table()->insert() directly
   - Uses Str::ulid() to generate IDs
   - Has complex logic for building relationships
   - Contains 30+ helper methods
   - Creates 56 RFQs, 100+ quotes, vendors, etc.
2. UserFactory.php - Only factory exists, 51 lines, simple
The seeder is doing things "the hard way" - directly inserting raw data instead of using factories + models. This is:
- Hard to maintain
- Hard to test
- Duplicates model logic (field names, relationships)
- No reuse for tests
Benefits of Factory adoption:
1. Test isolation - Tests can create fresh, isolated records without the full seeder
2. TDD - Factories enable writing tests first
3. DRY - Definition lives with the model (in factory), not duplicated in seeder
4. Faker integration - Realistic data generation
5. States & variants - Easy to create "draft rfq", "published rfq", "awarded rfq" states
6. Relationships - Laravel handles cascading relationships via hasMany, belongsTo
7. CI/CD - Quick DB setup for tests without running full seeder
For Atomy-Q specifically, factories would help:
- Unit tests can create RFQs with specific states
- Feature tests don't need full seeder data
- Developers can create test scenarios quickly
- The seeder itself could use factories internally
The trade-off is upfront investment to create 46 factories (for 47 models). But this pays off in long-term maintainability.
I should summarize the key insights and make a clear recommendation based on what's most practical for this codebase.
Yes, factories adoption would significantly improve maintainability. The current seeder is an anti-pattern - 1200+ lines of raw DB::table()->insert() calls duplicating model field knowledge.
Current Problem
// PetrochemicalTenantSeeder.php:627-649 - manually duplicating model structure
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
Factory Benefits for Atomy-Q
Aspect	Current (Manual)
Model changes	Update seeder + all test calls
Test isolation	Needs full migrate:fresh --seed
State variants	Copy-paste logic
Relationships	Manual ID tracking
Test data	56 RFQs seeded always
Recommended Approach
Phase 1: Core Domain Models (highest test churn)
- RfqFactory with states: draft, published, closed, awarded
- QuoteSubmissionFactory with states: uploaded, extracting, ready, failed
- ProjectFactory, VendorFactory, UserFactory (extend existing)
Phase 2: Supporting Entities
- RfqLineItemFactory, VendorInvitationFactory
- ComparisonRunFactory, ApprovalFactory
Phase 3: The Seeder Refactor
// After factories exist, seeder becomes ~100 lines
public function run(): void
{
    $tenant = Tenant::factory()->create();
    $users = User::factory()->count(8)->for($tenant)->create();
    $projects = Project::factory()->count(12)->for($tenant)->create();
    
    // ... relationships auto-handled
    Rfq::factory()
        ->for($tenant)
        ->for($projects->random())
        ->awarded()
        ->hasQuotes(3)
        ->create();
}
Trade-off Consideration
Cost: ~2-3 days to create 40+ factories  
Benefit: Immediate TDD enablement + reduced test suite runtime (no full seed for unit tests) + DRY model definitions
For active development with 60+ tests, the payoff is weeks of cumulative time saved per year.
▣  Build · Big Pickle · 32.2s