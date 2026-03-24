# Layer 1 First-Party Packages: High-Impact Additions

**Date:** 2026-03-24  
**Status:** Draft for Review  
**Purpose:** Identify and design 10 high-impact Layer 1 packages that can serve as "lego bricks" for modern SaaS ERP applications

---

## 1. Webhook

**Purpose:** Bidirectional HTTP event handling for external system integration

> **Composition vs. a new `Nexus\Webhook` package:** The capabilities below overlap **transactional fan-out** (`Nexus\Outbox`), **command idempotency** (`Nexus\Idempotency`), **HMAC / signing** (`Nexus\Crypto`), **tenant scope** (`Nexus\Tenant`), and optional **EventStream** dual-write. A monolithic Layer 1 “Webhook” package would largely duplicate those packages. See the **“Recipe: Webhook-style integrations (composition)”** section in [`docs/project/NEXUS_PACKAGES_REFERENCE.md`](../../project/NEXUS_PACKAGES_REFERENCE.md) for inbound/outbound flows and a pseudocode sketch using existing Nexus contracts.

### Capabilities
- **Inbound**: Receive events from Stripe, Twilio, external ERPs
- **Outbound**: Send events to external HTTP endpoints with retry logic
- **Signature Verification**: HMAC-SHA256 payload verification
- **Retry Logic**: Exponential backoff, max retries, dead-letter queue
- **Event Transformation**: Map internal events to external payload formats

### Contracts
```php
interface WebhookReceiverInterface {
    public function receive(ServerRequest $request): WebhookEvent;
    public function verifySignature(string $payload, string $signature): bool;
}

interface WebhookDispatcherInterface {
    public function dispatch(WebhookEvent $event): DispatchResult;
    public function scheduleRetry(WebhookEvent $event, \DateTimeInterface $nextAttempt): void;
}
```

### Use Cases
- Stripe payment events → update invoice status
- Twilio SMS delivery callbacks → update message status
- Outbound: Order confirmed → notify external WMS
- Outbound: Inventory low → alert external procurement

---

## 2. Approval

**Purpose:** Enterprise-grade approval workflow engine with template and rule-based routing

### Capabilities
- **Sequential Approval**: Strict order (Manager → Director → CFO)
- **Parallel Approval**: Multiple approvers simultaneously with quorum modes:
  - `ANY_ONE` - First to respond wins
  - `ALL_MUST` - Unanimous approval required
  - `MAJORITY` - More than 50% must approve
- **Conditional Routing**: Threshold-based rules (e.g., amount > $10k → CFO approval)
- **Template-Based**: Predefined approval chains per document type
- **Escalation**: Auto-escalate after SLA breach
- **Delegation**: Approver can delegate to alternate user
- **Comments & Attachments**: Approvers can add context

### Contracts
```php
interface ApprovalServiceInterface {
    public function createApproval(ApprovalRequest $request): ApprovalProcess;
    public function approve(ApprovalAction $action): ApprovalResult;
    public function reject(ApprovalAction $action): ApprovalResult;
    public function getPendingApprovals(string $userId): array;
    public function escalateStaleApprovals(): int;
}

interface ApprovalTemplateInterface {
    public function getSteps(): array;
    public function resolveApprover(ApprovalStep $step, mixed $context): string;
}

interface ApprovalRuleInterface {
    public function matches(mixed $context): bool;
    public function getApprover(mixed $context): string;
}
```

### Use Cases
- Purchase order approval with amount thresholds
- Invoice approval with sequential + parallel combo
- Expense report approval with category-based routing
- Leave request approval with manager hierarchy

---

## 3. Search

**Purpose:** Database-agnostic full-text search across entities

### Capabilities
- **Adapter-Based**: Pluggable backends
  - MySQL Full-Text Search (Phase 1)
  - PostgreSQL tsvector (Phase 1)
  - Elasticsearch/OpenSearch (Phase 2 - optional, requires separate adapter package)
- **Entity Indexing**: Register entities to search index
- **Faceted Search**: Filter by type, date, status
- **Synonyms**: Configurable synonym dictionaries
- **Ranking**: TF-IDF or custom scoring

### Contracts
```php
interface SearchIndexInterface {
    public function index(SearchableDocument $document): void;
    public function remove(string $id, string $type): void;
    public function reindex(string $type): void;
}

interface SearchQueryInterface {
    public function search(SearchQuery $query): SearchResults;
    public function facets(string $type): array;
}

interface SearchableDocument {
    public function getId(): string;
    public function getType(): string;
    public function getContent(): array;
    public function getTenantId(): string;
}
```

### Use Cases
- Global search across invoices, projects, contacts
- Product search in e-commerce module
- Document search with filters

---

## 4. FormBuilder

**Purpose:** Dynamic form creation for configurable data entry

### Capabilities
- **Field Types**: Text, number, date, select, multi-select, file upload, signature, lookup
- **Validation Rules**: Required, min/max, regex, custom validators
- **Conditional Visibility**: Show/hide fields based on other field values
- **Multi-Step Wizards**: Form split into steps with validation per step
- **Layout**: Sections, columns, tabs
- **Pre-population**: Default values, lookup from related entities

### Contracts
```php
interface FormBuilderInterface {
    public function createForm(FormDefinition $definition): Form;
    public function render(Form $form, string $renderer): RenderedForm;
    public function validate(Form $form, mixed $data): ValidationResult;
    public function submit(Form $form, mixed $data): FormSubmission;
}

interface FormDefinition {
    public function getFields(): array;
    public function getLayout(): Layout;
    public function getSteps(): array;
}
```

### Use Cases
- Employee onboarding forms
- Customer intake forms
- Survey builders
- Configuration wizards

---

## 5. Subscription

**Purpose:** SaaS tier and plan management with billing integration hooks

### Capabilities
- **Plans/Tiers**: Free, Basic, Pro, Enterprise with feature listings
- **Limits**: API calls, storage, users, transactions
- **Billing Integration**: Hooks for Stripe, Chargebee, PayPal
- **Upgrade/Downgrade**: Prorated billing changes
- **Trial Periods**: Configurable trial with reminders
- **Add-ons**: Per-seat, per-feature charges

### Contracts
```php
interface SubscriptionManagerInterface {
    public function createSubscription(SubscriptionRequest $request): Subscription;
    public function changeTier(string $subscriptionId, string $newTier): Subscription;
    public function cancel(string $subscriptionId): void;
    public function getCurrentPlan(string $subscriptionId): Plan;
    public function checkLimit(string $subscriptionId, string $limitType): LimitCheck;
}
```

### Use Cases
- SaaS pricing tiers with feature gating
- Per-seat licensing
- Usage-based billing hooks

---

## 6. UsageTracking

**Purpose:** Metered billing and quota enforcement

### Capabilities
- **Counters**: Increment counters per action (API calls, storage, bandwidth)
- **Aggregations**: Daily, monthly, yearly rollups
- **Quotas**: Hard limits with enforcement
- **Usage Events**: Emit events when thresholds crossed
- **Reset Periods**: Monthly/annual resets
- **Overage Tracking**: Track excess usage for billing

### Contracts
```php
interface UsageTrackerInterface {
    public function record(UsageEvent $event): void;
    public function getCurrentUsage(string $resourceId, string $period): UsageSnapshot;
    public function checkQuota(string $resourceId, string $quotaType): QuotaCheck;
    public function getUsageHistory(string $resourceId, \DatePeriod $period): UsageReport;
}
```

### Use Cases
- API call metering
- Storage usage tracking
- Transaction volume limits

---

## 7. Entitlement

**Purpose:** Feature access control per subscription tier

### Capabilities
- **Feature Flags**: Toggle features on/off per tenant
- **Tier-Based Access**: Map features to subscription tiers
- **Usage-Based Gates**: Lock features after usage threshold
- **Role-Based Override**: Grant exceptions to specific users
- **Audit**: Log all entitlement checks

### Contracts
```php
interface EntitlementCheckerInterface {
    public function hasAccess(string $tenantId, string $feature): bool;
    public function getAvailableFeatures(string $tenantId): array;
    public function checkEntitlement(EntitlementRequest $request): EntitlementResult;
}

interface FeatureFlagInterface {
    public function isEnabled(string $flag, string $tenantId): bool;
    public function toggle(string $flag, string $tenantId, bool $enabled): void;
}
```

### Use Cases
- Hide premium features for basic tier
- Enable beta features for specific tenants
- Role-based feature override

---

## 8. TenantBranding

**Purpose:** White-label customization per tenant

### Capabilities
- **Themes**: Custom colors, fonts, CSS
- **Logos**: Header, login, email attachments
- **Email Templates**: Custom email HTML per tenant
- **Custom Domain**: CNAME support for tenant URLs
- **Footer Text**: Custom legal disclaimers

### Contracts
```php
interface TenantBrandingInterface {
    public function getTheme(string $tenantId): Theme;
    public function getLogo(string $tenantId, string $location): Logo;
    public function getEmailTemplate(string $tenantId, string $template): EmailTemplate;
    public function resolveCustomDomain(string $domain): ?string;
}
```

### Use Cases
- Multi-tenant SaaS with white-label clients
- Custom email branding per tenant
- Theme switching without code deploy

---

## 9. WebhookSignature

**Purpose:** Secure HMAC verification for incoming webhooks

### Capabilities
- **HMAC-SHA256**: Signature generation and verification
- **Timestamp Validation**: Reject requests older than N minutes
- **Nonce Tracking**: Prevent replay attacks
- **Configurable Algorithms**: SHA256, SHA384, SHA512
- **Standalone**: Can be used by Webhook, API gateways, or any system needing signature verification

### Relationship
- **Webhook** uses **WebhookSignature** internally for inbound event verification
- **WebhookSignature** is also usable standalone by API gateways, mobile clients, or other systems

### Contracts
```php
interface WebhookSignatureInterface {
    public function generateSignature(string $payload, string $secret): string;
    public function verify(SignatureVerification $verification): bool;
    public function isReplayAttack(string $nonce, int $ttlSeconds): bool;
}
```

### Use Cases
- Verify Stripe webhook signatures
- Verify Twilio webhook signatures
- Any external system signature verification

---

## 10. DataClassification

**Purpose:** PII/sensitive data tagging for compliance

### Capabilities
- **Classification Types**: PII, PHI, Financial, Proprietary, Public
- **Auto-Tagging**: Regex patterns to auto-detect sensitive fields
- **Retention Policies**: Configurable retention per classification
- **Masking Rules**: Define how to mask each type
- **Audit Trail**: Log all data access by classification

### Contracts
```php
interface DataClassifierInterface {
    public function classify(DataField $field): Classification;
    public function getRetentionPolicy(Classification $class): RetentionPolicy;
    public function mask(Classification $class, mixed $value): string;
    public function scan(array $data): ClassificationReport;
}
```

### Use Cases
- GDPR compliance - mark PII fields
- PDPA compliance - personal data tagging
- Financial data classification
- HIPAA PHI tracking

---

## Implementation Priority

| Priority | Package | Rationale |
|----------|---------|-----------|
| 1 | Approval | Core business process - needed for RFQ workflow |
| 2 | Webhook | Integration foundation |
| 3 | Entitlement | Feature gating - prevents feature leakage |
| 4 | Subscription | SaaS monetization (uses UsageTracking for metering) |
| 5 | UsageTracking | Metered billing foundation (used by Subscription) |
| 6 | Search | Global search UX |
| 7 | FormBuilder | Dynamic data entry |
| 8 | TenantBranding | White-label requirement |
| 9 | WebhookSignature | Security foundation (used by Webhook) |
| 10 | DataClassification | Compliance foundation |

---

## Dependencies Between Packages

```
Approval
  ↑
  ├─→ Webhook (approval notifications)
  └─→ Entitlement (approval feature flag)

Subscription
  ↑
  ├─→ Entitlement (feature access)
  ├─→ UsageTracking (quota/usage metering)
  └─→ Webhook (billing events)

Webhook
  ↑
  └─→ WebhookSignature (HMAC verification for inbound)

TenantBranding
  ↑
  └─→ FormBuilder (custom forms)

Search ← no strong dependencies (standalone)

DataClassification ← no strong dependencies (standalone)

FormBuilder ← no strong dependencies (standalone)

UsageTracking ← no strong dependencies (standalone, but used by Subscription)

Entitlement ← no strong dependencies (standalone)
```
