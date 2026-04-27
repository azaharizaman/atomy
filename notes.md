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


---

## Laravel-Way Opportunities for Atomy-Q API

### High-Impact Improvements

1. **Expand FormRequest Validation**
2. **Add Tenant Global Scope** — prevents query leaks by automatically scoping queries by `tenant_id`
3. **Laravel Policies for Authorization** — wrap existing `ProjectAclService` rather than replace it
4. **Standardize API Resources & Collections** — align transformers in `Http/Resources` with DTOs
5. **Split Routes File** — decompose into modular files (`api/v1/rfqs.php`, `api/v1/projects.php`, `api/v1/vendors.php`)

### Architectural Integration

Integrate Laravel features with existing Nexus architecture:
- `FormRequest::authorize()` → Combine with NexusPermission middleware
- Laravel Policy → Wrap ProjectAclService, don't replace
- Global Tenant Scope → Use tenant context from existing TenantContext middleware
- API Resources → Keep in Http/Resources, align with DTOs

### Implementation Suggestions

- **TenantScope**: Prevent query leaks with automatic tenant filtering via `app/Scopes/TenantScope.php`
- **ApiResponse trait**: Standardize `success()`, `created()`, and `error()` methods for JSON responses
- **Route decomposition**: Use `require` statements in `routes/api.php` to load modular route files

Start with Tenant Global Scope and FormRequest expansion to prevent bugs and standardize validation with minimal refactoring risk.

### Factory Adoption Recommendation

The current `PetrochemicalTenantSeeder.php` is hand-coded using raw `DB::table()->insert()` calls, making maintenance brittle. Adopting Laravel factories would improve maintainability through test isolation, TDD enablement, and DRY model definitions. Factories should be introduced in phases: core domain models first (RfqFactory, QuoteSubmissionFactory), then supporting entities (VendorInvitationFactory, ComparisonRunFactory), and finally a seeder refactor to use factories internally.