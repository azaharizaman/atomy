# Nexus Coding Guidelines

**Version:** 1.0  
**Last Updated:** November 26, 2025  
**Target Audience:** Developers & Coding Agents  
**Purpose:** Enforce consistent, high-quality code across all Nexus packages

---

## Table of Contents

1. [Core Principles](#1-core-principles)
2. [PHP Language Standards](#2-php-language-standards)
3. [Repository Interface Design](#3-repository-interface-design)
4. [Service Class Design](#4-service-class-design)
5. [Contract-Driven Development](#5-contract-driven-development)
6. [Value Objects & Data Protection](#6-value-objects--data-protection)
7. [Error Handling](#7-error-handling)
8. [Testing Standards](#8-testing-standards)
9. [Documentation Requirements](#9-documentation-requirements)
10. [Code Quality Checklist](#10-code-quality-checklist)
11. [Anti-Patterns to Avoid](#11-anti-patterns-to-avoid)

---

## 1. Core Principles

### Framework Agnosticism is Mandatory

Every package in `packages/` must be **pure PHP** and work with any framework (Laravel, Symfony, Slim, etc.).

**NEVER:**
- Use framework-specific classes (`Illuminate\*`, `Symfony\*`)
- Use facades (`Log::`, `Cache::`, `DB::`, `Event::`)
- Use global helpers (`config()`, `app()`, `now()`, `dd()`, `env()`)
- Include database migrations or ORM models
- Reference application-specific code

**ALWAYS:**
- Write pure PHP 8.3+ code
- Define dependencies via interfaces
- Use dependency injection via constructor
- Make packages publishable independently

### Stateless Architecture

Packages must not store long-term state in memory.

**Rule:** Any state that survives beyond a single request (cache, counters, flags, circuit breaker states) must be externalized via injected `StorageInterface`.

**Example:**
```php
// ❌ WRONG: State stored in class property
final class CircuitBreaker
{
    private array $states = [];
}

// ✅ CORRECT: State externalized
final readonly class CircuitBreaker
{
    public function __construct(
        private CircuitBreakerStorageInterface $storage
    ) {}
}
```

### Dependency Injection Only

All dependencies must be injected via constructor as **interfaces**, never concrete classes.

```php
// ❌ WRONG: Concrete dependency
public function __construct(
    private GeneralLedgerManager $glManager
) {}

// ✅ CORRECT: Interface dependency
public function __construct(
    private GeneralLedgerManagerInterface $glManager
) {}
```

---

## 2. PHP Language Standards

### PHP 8.3+ Required

All packages must require `"php": "^8.3"` in composer.json.

### Mandatory Modern PHP Features

1. **Strict Types Declaration**
   ```php
   <?php
   
   declare(strict_types=1);
   
   namespace Nexus\YourPackage;
   ```

2. **Constructor Property Promotion**
   ```php
   // ✅ Use constructor property promotion
   public function __construct(
       private readonly LoggerInterface $logger,
       private readonly TenantContextInterface $tenantContext
   ) {}
   ```

3. **Readonly Properties**
   ```php
   // ✅ All injected dependencies must be readonly
   final readonly class InvoiceManager
   {
       public function __construct(
           private InvoiceRepositoryInterface $repository,
           private AuditLogManagerInterface $auditLogger
       ) {}
   }
   ```

4. **Native PHP Enums**
   ```php
   // ✅ Use backed enums
   enum InvoiceStatus: string
   {
       case DRAFT = 'draft';
       case PENDING = 'pending';
       case PAID = 'paid';
       case CANCELLED = 'cancelled';
   }
   ```

5. **Match Expression (not switch)**
   ```php
   // ✅ Use match instead of switch
   $message = match ($status) {
       InvoiceStatus::DRAFT => 'Invoice is in draft',
       InvoiceStatus::PAID => 'Invoice has been paid',
       default => 'Unknown status',
   };
   ```

6. **Throw in Expressions**
   ```php
   // ✅ Use throw in expressions
   $invoice = $this->repository->findById($id) 
       ?? throw new InvoiceNotFoundException("Invoice {$id} not found");
   ```

7. **Named Arguments**
   ```php
   // ✅ Use named arguments for clarity
   $this->auditLogger->log(
       entityId: $invoiceId,
       action: 'status_change',
       description: 'Status updated to Paid'
   );
   ```

### Type Hints

**All methods must have complete type hints:**

```php
// ✅ CORRECT: Full type hints
public function createInvoice(
    string $customerId,
    array $lineItems,
    \DateTimeImmutable $invoiceDate
): Invoice {
    // ...
}

// ❌ WRONG: Missing type hints
public function createInvoice($customerId, $lineItems, $invoiceDate) {
    // ...
}
```

---

## 3. Repository Interface Design

### The Baseline: Query and Persist

**Every package starts with these two interfaces:**

1. **`EntityQueryInterface`** - Read operations only
2. **`EntityPersistInterface`** - Write operations only

This enforces **CQRS (Command Query Responsibility Segregation)**.

**Example:**
```php
namespace Nexus\Invoice\Contracts;

interface InvoiceQueryInterface
{
    public function findById(string $id): ?InvoiceInterface;
    
    /**
     * @return array<InvoiceInterface>
     */
    public function findAll(): array;
}

interface InvoicePersistInterface
{
    public function save(InvoiceInterface $invoice): InvoiceInterface;
    public function delete(string $id): void;
}
```

**Alternative Pattern (Separate Create/Update):**

When you need different return types or validation logic for create vs update:

```php
interface InvoicePersistInterface
{
    public function create(InvoiceInterface $invoice): string;  // Returns ID
    public function update(InvoiceInterface $invoice): void;    // Returns nothing
    public function delete(string $id): void;
}
```

### CQRS Best Practices

**Consistent Method Signatures in CQRS Interfaces:**

1. **Query Interfaces - Type Consistency**
   - All query methods must return **typed objects** or **typed arrays** with PHPDoc
   - Use `?array` ONLY when returning raw data structures (not entities)
   - Always add `@return array<Type>` PHPDoc for array returns
   - Never mix return types inconsistently (e.g., some methods return entities, others return `?array` for the same data)

   ```php
   // ✅ CORRECT: Consistent - all methods return arrays (raw data)
   interface MfaEnrollmentQueryInterface
   {
       /** @return array|null Enrollment data */
       public function findById(string $id): ?array;
       
       /** @return array|null Enrollment data */
       public function findPendingByUserAndMethod(string $userId, string $method): ?array;
       
       /** @return array<array> Array of enrollment data */
       public function findActiveBackupCodes(string $userId): array;
   }
   
   // ✅ CORRECT: Consistent - all methods return typed entities
   interface InvoiceQueryInterface
   {
       public function findById(string $id): ?InvoiceInterface;
       public function findByNumber(string $number): ?InvoiceInterface;
       
       /**
        * @return array<InvoiceInterface>
        */
       public function findAll(): array;
   }
   
   // ❌ WRONG: Inconsistent - mixing entity returns with raw arrays
   interface MfaEnrollmentQueryInterface
   {
       public function findById(string $id): ?MfaEnrollmentInterface;  // Entity ✅
       public function findPendingByUserAndMethod(string $userId, string $method): ?array;  // Raw array ❌
   }
   ```

2. **Persist Interfaces - Choose One Pattern Consistently**
   - **Pattern A (Recommended)**: Single `save()` method for both create and update
   - **Pattern B**: Separate `create()` and `update()` methods when they have different signatures/logic
   - **Never** mix both patterns (having `save()`, `create()`, AND `update()` in the same interface)

   ```php
   // ✅ PATTERN A (Recommended): Single save method
   interface InvoicePersistInterface
   {
       public function save(InvoiceInterface $invoice): InvoiceInterface;  // Handles both create and update
       public function delete(string $id): void;
   }
   
   // ✅ PATTERN B: Separate methods (when create and update have different signatures)
   interface InvoicePersistInterface
   {
       public function create(InvoiceInterface $invoice): string;  // Returns new ID
       public function update(InvoiceInterface $invoice): void;    // No return
       public function delete(string $id): void;
   }
   
   // ❌ WRONG: Method overlap - having both save() and create()
   interface MfaEnrollmentPersistInterface
   {
       public function save(MfaEnrollmentInterface $enrollment): MfaEnrollmentInterface;
       public function create(array $data): array;  // Overlaps with save() ❌
   }
   ```

### When to Create Additional Repository Interfaces

Beyond Query and Persist, create additional interfaces **only when justified** by one of these factors:

#### Factor 1: Behavioral Intent

Separates analytical/reporting from transactional access.

| Use Case | Interface Name | Example Methods | Why Separate? |
|----------|---------------|-----------------|---------------|
| **Full-text search** | `EntitySearchInterface` | `search(string $query, array $filters)` | Decouples search engine (ElasticSearch) from domain queries |
| **Bulk/Streaming** | `EntityStreamInterface` | `stream(): \Generator` | Returns generators for memory efficiency, different from sync queries |

**Example:**
```php
namespace Nexus\Content\Contracts;

// Separate interface for search functionality
interface ContentSearchInterface
{
    /**
     * Search content using full-text search engine
     */
    public function search(
        string $query,
        array $filters = [],
        int $limit = 20
    ): array;
    
    /**
     * Get search suggestions
     */
    public function suggest(string $partial): array;
}
```

#### Factor 2: Aggregate Boundary

Ensures repository manages specific aggregate root per DDD.

| Use Case | Interface Name | Example Methods | Why Separate? |
|----------|---------------|-----------------|---------------|
| **Cross-aggregate relationships** | `CaseMessagingInterface` | `getMessages(string $caseId)` | Fetches related data from different package (`Nexus\Messaging`) |
| **Different contexts** | `DraftRepositoryInterface` | `findDrafts()` | Ensures draft-only services can't access published data |

**Example:**
```php
namespace Nexus\Case\Contracts;

// Separate interface for related messages (from Nexus\Messaging)
interface CaseMessagingInterface
{
    /**
     * Get all messages associated with a case
     * 
     * @return MessageInterface[]
     */
    public function getMessages(string $caseId): array;
    
    /**
     * Add message to case
     */
    public function addMessage(string $caseId, MessageInterface $message): void;
}
```

#### Factor 3: External System Dependency

Used when "persistence" interacts with third-party API.

| Use Case | Interface Name | Example Methods | Why Separate? |
|----------|---------------|-----------------|---------------|
| **Third-party connectors** | `MessagingConnectorInterface` | `sendSms(string $to, string $message)` | Abstracts vendor-specific APIs (Twilio, SendGrid) |

**Example:**
```php
namespace Nexus\Messaging\Contracts;

// Connector abstraction for SMS providers
interface SmsConnectorInterface
{
    /**
     * Send SMS via external provider
     */
    public function send(
        string $to,
        string $message,
        array $options = []
    ): SmsResultInterface;
}
```

#### Factor 4: Write Intent

Isolates specific state changes from general updates.

| Use Case | Interface Name | Example Methods | Why Separate? |
|----------|---------------|-----------------|---------------|
| **State transitions** | `CaseTransitionInterface` | `transitionTo(string $caseId, CaseStatus $status)` | Granular control - service only needs state change capability |

**Example:**
```php
namespace Nexus\Case\Contracts;

// Separate interface for state transitions
interface CaseTransitionInterface
{
    /**
     * Transition case to new status
     */
    public function transitionTo(
        string $caseId,
        CaseStatus $newStatus,
        ?string $reason = null
    ): void;
    
    /**
     * Check if transition is valid
     */
    public function canTransitionTo(
        string $caseId,
        CaseStatus $newStatus
    ): bool;
}
```

### Decision Matrix: Should I Create a New Repository Interface?

Ask these questions in order:

1. **Is this a simple read operation?** → Use `EntityQueryInterface`
2. **Is this a simple write operation?** → Use `EntityPersistInterface`
3. **Does this require specialized search/indexing?** → Create `EntitySearchInterface`
4. **Is this for bulk/streaming data?** → Create `EntityStreamInterface`
5. **Does this cross aggregate boundaries?** → Create relationship-specific interface
6. **Is this interfacing with external system?** → Create connector interface
7. **Is this a specific state transition?** → Create transition interface

**If none of these apply, DON'T create a new interface. Extend Query or Persist instead.**

### Interface Naming Conventions

| Type | Naming Pattern | Example |
|------|----------------|---------|
| Entity Contract | `{Entity}Interface` | `InvoiceInterface` |
| Query Repository | `{Entity}QueryInterface` | `InvoiceQueryInterface` |
| Persistence Repository | `{Entity}PersistInterface` | `InvoicePersistInterface` |
| Search | `{Entity}SearchInterface` | `ContentSearchInterface` |
| Stream/Bulk | `{Entity}StreamInterface` | `PayslipStreamInterface` |
| Relationship | `{Entity}{Relation}Interface` | `CaseMessagingInterface` |
| Connector | `{Service}ConnectorInterface` | `SmsConnectorInterface` |
| Transition | `{Entity}TransitionInterface` | `CaseTransitionInterface` |
| Manager/Service | `{Entity}ManagerInterface` | `InvoiceManagerInterface` |

---

## 4. Service Class Design

### Service Class Structure

```php
<?php

declare(strict_types=1);

namespace Nexus\YourPackage\Services;

use Nexus\YourPackage\Contracts\EntityInterface;
use Nexus\YourPackage\Contracts\EntityRepositoryInterface;
use Nexus\YourPackage\Exceptions\EntityNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class EntityManager implements EntityManagerInterface
{
    public function __construct(
        private EntityRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}
    
    public function create(array $data): EntityInterface
    {
        // Validation
        $this->validateData($data);
        
        // Business logic
        $entity = $this->buildEntity($data);
        
        // Persistence
        $id = $this->repository->create($entity);
        
        // Logging (optional dependency)
        $this->logger->info('Entity created', ['id' => $id]);
        
        return $entity;
    }
    
    private function validateData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidEntityException('Name is required');
        }
    }
    
    private function buildEntity(array $data): EntityInterface
    {
        // Entity construction logic
    }
}
```

### Service Class Rules

1. **Final and Readonly**
   ```php
   final readonly class InvoiceManager implements InvoiceManagerInterface
   ```

2. **All Dependencies via Constructor**
   ```php
   public function __construct(
       private InvoiceRepositoryInterface $repository,
       private TenantContextInterface $tenantContext,
       private AuditLogManagerInterface $auditLogger
   ) {}
   ```

3. **No Public Properties**
   - All properties must be `private readonly`

4. **No Static Methods**
   - All methods must be instance methods

5. **Optional Dependencies Use Nullable Types**
   ```php
   public function __construct(
       private InvoiceRepositoryInterface $repository,
       private ?TelemetryTrackerInterface $telemetry = null
   ) {}
   
   public function doSomething(): void
   {
       // Gracefully handle missing dependency
       $this->telemetry?->increment('action.performed');
   }
   ```

---

## 5. Contract-Driven Development

### Define Needs, Not Solutions

Packages define **what** they need via interfaces, not **how** it's implemented.

**Example:**
```php
// ❌ WRONG: Package depends on concrete implementation
namespace Nexus\Receivable\Services;

use App\Services\Finance\FinanceGLService; // Application-specific!

final readonly class InvoiceManager
{
    public function __construct(
        private FinanceGLService $glService
    ) {}
}

// ✅ CORRECT: Package defines interface
namespace Nexus\Receivable\Contracts;

interface GeneralLedgerIntegrationInterface
{
    public function postJournalEntry(JournalEntry $entry): void;
}

namespace Nexus\Receivable\Services;

use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;

final readonly class InvoiceManager
{
    public function __construct(
        private GeneralLedgerIntegrationInterface $glIntegration
    ) {}
}
```

### Consumer Implements Interface

The consuming application provides concrete implementation:

```php
// Application layer implementation
namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

final readonly class FinanceGLAdapter implements GeneralLedgerIntegrationInterface
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager
    ) {}
    
    public function postJournalEntry(JournalEntry $entry): void
    {
        $this->glManager->post($entry);
    }
}

// Laravel service provider binding
$this->app->singleton(
    GeneralLedgerIntegrationInterface::class,
    FinanceGLAdapter::class
);
```

---

## 6. Value Objects & Data Protection

Value Objects (VOs) are immutable objects that represent domain concepts without identity. They are compared by their values, not by reference. This section provides comprehensive guidance on when to create VOs, when to avoid them, and how to use them to protect sensitive data.

### 6.1 When to Create a Value Object

**CREATE a VO when:**

| Scenario | Example | Rationale |
|----------|---------|-----------|
| **Domain concept with validation** | `Money`, `EmailAddress`, `PhoneNumber` | Encapsulates validation rules; invalid states are impossible |
| **Multi-field composite values** | `Address`, `DateRange`, `Coordinates` | Groups related fields that always travel together |
| **Unit-sensitive quantities** | `Weight`, `Distance`, `Temperature` | Prevents unit confusion (kg vs lb, km vs miles) |
| **Formatted identifiers** | `InvoiceNumber`, `TaxId`, `Iban` | Enforces format validation and normalization |
| **Safe data projection** | `UserProfile`, `InvoiceSummary` | Exposes only safe-to-share fields, hides sensitive data |
| **Calculations with precision** | `Money`, `Percentage`, `ExchangeRate` | Ensures correct arithmetic (no floating point errors) |

**Example - Domain Concept VO:**
```php
// ✅ CORRECT: Money VO with validation and precision
final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public string $currency
    ) {
        if ($amountInCents < 0) {
            throw new InvalidMoneyException('Amount cannot be negative');
        }
        if (strlen($currency) !== 3) {
            throw new InvalidMoneyException('Currency must be ISO 4217 code');
        }
    }
    
    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }
}
```

### 6.2 When NOT to Create a Value Object

**DO NOT create a VO when:**

| Scenario | Use Instead | Rationale |
|----------|-------------|-----------|
| **Single primitive with no validation** | Native type (`string`, `int`, `float`) | Unnecessary abstraction; adds complexity without value |
| **Fixed set of options** | Native PHP Enum | Enums are purpose-built for finite option sets |
| **Simple boolean flags** | `bool` | No benefit to wrapping a boolean |
| **Temporary calculation result** | Local variable or array | VOs are for domain concepts, not intermediate values |
| **Entity with identity** | Entity class | If it has an ID and lifecycle, it's not a VO |
| **Wrapper with no behavior** | Native type | If the VO only has getters, it's just noise |

**❌ WRONG: Unnecessary VO for simple string**
```php
// ❌ Over-engineering: No validation, no behavior
final readonly class CustomerName
{
    public function __construct(
        public string $value
    ) {}
}

// Just use string directly in the entity
interface CustomerInterface
{
    public function getName(): string;  // ✅ Simple is better
}
```

**❌ WRONG: VO instead of Enum**
```php
// ❌ Should be an Enum, not a VO
final readonly class InvoiceStatus
{
    public const DRAFT = 'draft';
    public const PENDING = 'pending';
    public const PAID = 'paid';
    
    public function __construct(
        public string $value
    ) {
        if (!in_array($value, [self::DRAFT, self::PENDING, self::PAID])) {
            throw new InvalidArgumentException();
        }
    }
}

// ✅ CORRECT: Use native PHP Enum
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PAID = 'paid';
}
```

### 6.3 Value Object Categories

#### Category 1: Domain Primitive VOs

Wrap single values with domain-specific validation and behavior.

| VO Name | Wraps | Validation | Behavior |
|---------|-------|------------|----------|
| `EmailAddress` | `string` | RFC 5322 format | `getDomain()`, `getLocalPart()` |
| `PhoneNumber` | `string` | E.164 format | `getCountryCode()`, `format()` |
| `Percentage` | `float` | 0-100 range | `of()`, `apply()`, `asDecimal()` |
| `Ulid` | `string` | 26 chars, Crockford Base32 | `toDateTime()`, `compare()` |

**Example:**
```php
final readonly class EmailAddress
{
    public function __construct(
        public string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
    }
    
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }
    
    public function equals(self $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }
}
```

#### Category 2: Composite VOs

Group related fields that always travel together.

| VO Name | Fields | Use Case |
|---------|--------|----------|
| `Address` | street, city, state, postalCode, country | Shipping, billing addresses |
| `DateRange` | startDate, endDate | Fiscal periods, contracts |
| `Coordinates` | latitude, longitude | Geolocation |
| `Money` | amount, currency | Financial calculations |

**Example:**
```php
final readonly class DateRange
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate
    ) {
        if ($endDate < $startDate) {
            throw new InvalidDateRangeException('End date must be after start date');
        }
    }
    
    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }
    
    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }
    
    public function getDays(): int
    {
        return $this->startDate->diff($this->endDate)->days;
    }
}
```

#### Category 3: Projection VOs (Read Models)

Expose only safe-to-share fields from entities. **Critical for preventing data leakage.**

| VO Name | Source Entity | Excluded Fields | Use Case |
|---------|---------------|-----------------|----------|
| `UserProfile` | `User` | passwordHash, apiTokens, mfaSecret | API responses, views |
| `InvoiceSummary` | `Invoice` | internalNotes, costPrice | Customer portal |
| `EmployeeCard` | `Employee` | salary, ssn, bankAccount | Org chart display |

**Example - Preventing Sensitive Data Leakage:**
```php
// ❌ DANGEROUS: Entity interface exposes sensitive data
interface UserInterface
{
    public function getId(): string;
    public function getEmail(): string;
    public function getPasswordHash(): string;  // ⚠️ NEVER EXPOSE!
    public function getMfaSecret(): ?string;    // ⚠️ NEVER EXPOSE!
    public function getApiTokens(): array;      // ⚠️ NEVER EXPOSE!
}

// ✅ SAFE: Projection VO for external use
final readonly class UserProfile
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public ?string $avatarUrl,
        public \DateTimeImmutable $createdAt
        // NO password, NO tokens, NO MFA secrets
    ) {}
    
    public static function fromUser(UserInterface $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            avatarUrl: $user->getAvatarUrl(),
            createdAt: $user->getCreatedAt()
        );
    }
}

// ✅ SAFE: Query interface returns projection, not entity
interface UserQueryInterface
{
    public function findById(string $id): UserInterface;           // Internal use only
    public function getProfileById(string $id): UserProfile;       // Safe for API/views
    public function getProfilesByTenant(string $tenantId): array;  // Returns UserProfile[]
}
```

### 6.4 Enum vs Value Object Decision Matrix

| Characteristic | Use Enum | Use Value Object |
|----------------|----------|------------------|
| **Finite, known set of values** | ✅ Yes | ❌ No |
| **Values can change at runtime** | ❌ No | ✅ Yes |
| **Needs validation logic** | ❌ Limited | ✅ Yes |
| **Has behavior/methods** | ❌ Limited | ✅ Yes |
| **Composite (multiple fields)** | ❌ No | ✅ Yes |
| **Backed by database lookup** | ❌ No | ✅ Yes |
| **Type safety for options** | ✅ Yes | ⚠️ Overkill |

**Use Enum for:**
```php
// Status with fixed options - ENUM
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

// Type classification - ENUM
enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';
}

// Permission level - ENUM
enum AccessLevel: int
{
    case NONE = 0;
    case READ = 1;
    case WRITE = 2;
    case ADMIN = 3;
}
```

**Use Value Object for:**
```php
// Dynamic tax rate with validation - VO
final readonly class TaxRate
{
    public function __construct(
        public string $code,
        public float $rate,
        public string $jurisdiction,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo = null
    ) {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidTaxRateException();
        }
    }
    
    public function isEffectiveOn(\DateTimeImmutable $date): bool
    {
        return $date >= $this->effectiveFrom 
            && ($this->effectiveTo === null || $date <= $this->effectiveTo);
    }
}

// Currency with conversion behavior - VO (not enum, too many currencies)
final readonly class Currency
{
    public function __construct(
        public string $code,
        public string $symbol,
        public int $decimalPlaces
    ) {
        if (strlen($code) !== 3) {
            throw new InvalidCurrencyException();
        }
    }
}
```

### 6.5 Data Protection Rules

#### Rule 1: Never Expose Password Hashes

```php
// ❌ FORBIDDEN: Password hash in interface
interface UserInterface
{
    public function getPasswordHash(): string;  // NEVER!
}

// ✅ CORRECT: Verification method instead
interface UserInterface
{
    public function verifyPassword(string $plainPassword): bool;
}
```

#### Rule 2: Never Expose API Tokens/Secrets

```php
// ❌ FORBIDDEN: Tokens exposed via getter
interface UserInterface
{
    public function getApiTokens(): array;      // NEVER!
    public function getMfaSecret(): ?string;    // NEVER!
}

// ✅ CORRECT: Separate, secured interface
interface TokenManagerInterface
{
    public function validateToken(string $token): bool;
    public function revokeToken(string $tokenId): void;
}
```

#### Rule 3: Use Projections for External APIs

```php
// ❌ DANGEROUS: Returning entity to controller
final readonly class UserController
{
    public function show(string $id): JsonResponse
    {
        $user = $this->userQuery->findById($id);
        return response()->json($user);  // Exposes everything!
    }
}

// ✅ SAFE: Return projection VO
final readonly class UserController
{
    public function show(string $id): JsonResponse
    {
        $profile = $this->userQuery->getProfileById($id);
        return response()->json($profile);  // Only safe fields
    }
}
```

#### Rule 4: Audit Log Protection

```php
// ❌ DANGEROUS: Logging sensitive data
$this->auditLogger->log($userId, 'password_change', [
    'old_hash' => $user->getPasswordHash(),  // NEVER LOG!
    'new_hash' => $newHash,                   // NEVER LOG!
]);

// ✅ SAFE: Log action without sensitive details
$this->auditLogger->log($userId, 'password_change', [
    'changed_at' => now()->toIso8601String(),
    'ip_address' => $request->ip(),
]);
```

### 6.6 Value Object Checklist

Before creating a VO, answer these questions:

- [ ] **Does it have validation rules?** If no, consider using native type
- [ ] **Does it have behavior (methods)?** If no, consider using native type
- [ ] **Is it a finite set of options?** If yes, use Enum instead
- [ ] **Does it group related fields?** If yes, VO is appropriate
- [ ] **Does it protect sensitive data?** If yes, create projection VO
- [ ] **Will it be reused across packages?** If yes, place in `src/ValueObjects/`
- [ ] **Is it immutable?** VOs MUST be immutable (use `readonly`)

### 6.7 VO Anti-Patterns

**❌ Anti-Pattern 1: Wrapper with no value**
```php
// ❌ WRONG: Just a wrapper, no validation, no behavior
final readonly class CustomerId
{
    public function __construct(public string $value) {}
}

// ✅ Just use string - less code, same effect
public function findCustomer(string $customerId): CustomerInterface;
```

**❌ Anti-Pattern 2: Mutable "Value Object"**
```php
// ❌ WRONG: VOs must be immutable
final class Money  // Missing readonly!
{
    public int $amount;  // Mutable!
    
    public function setAmount(int $amount): void  // Setter!
    {
        $this->amount = $amount;
    }
}

// ✅ CORRECT: Immutable VO
final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public string $currency
    ) {}
    
    public function add(Money $other): self  // Returns new instance
    {
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }
}
```

**❌ Anti-Pattern 3: VO with identity**
```php
// ❌ WRONG: If it has an ID, it's an Entity, not a VO
final readonly class Address
{
    public function __construct(
        public string $id,  // VOs don't have IDs!
        public string $street,
        public string $city
    ) {}
}

// ✅ CORRECT: VO without identity
final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country
    ) {}
    
    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }
}
```

---

## 7. Error Handling

### Custom Exceptions

Every package must define domain-specific exceptions.

**Structure:**
```
src/
└── Exceptions/
    ├── InvoiceException.php          # Base exception
    ├── InvoiceNotFoundException.php  # Specific exception
    ├── InvalidInvoiceException.php
    └── InvoiceAlreadyPaidException.php
```

**Example:**
```php
<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

class InvoiceException extends \Exception
{
    // Base exception for all invoice-related errors
}

class InvoiceNotFoundException extends InvoiceException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice {$invoiceId} not found");
    }
}

class InvalidInvoiceException extends InvoiceException
{
    // Validation errors
}
```

### Throw Exceptions, Don't Return Null

```php
// ❌ WRONG: Returns null
public function findById(string $id): ?InvoiceInterface
{
    $invoice = $this->repository->findById($id);
    return $invoice; // Could be null
}

// ✅ CORRECT: Throws exception
public function findById(string $id): InvoiceInterface
{
    return $this->repository->findById($id)
        ?? throw new InvoiceNotFoundException($id);
}
```

### Exception Documentation

All exceptions must be documented in method docblocks:

```php
/**
 * Find invoice by ID
 * 
 * @param string $id Invoice ULID
 * @return InvoiceInterface
 * @throws InvoiceNotFoundException If invoice not found
 * @throws InvalidTenantException If tenant context invalid
 */
public function findById(string $id): InvoiceInterface
{
    // ...
}
```

---

## 8. Testing Standards

### Test Organization

```
tests/
├── Unit/                    # Isolated unit tests
│   ├── Services/
│   │   └── InvoiceManagerTest.php
│   └── ValueObjects/
│       └── MoneyTest.php
└── Feature/                 # Integration tests
    └── InvoiceLifecycleTest.php
```

### Unit Test Requirements

1. **Test All Public Methods**
   - Every public method must have at least one test

2. **Test Edge Cases**
   - Null values, empty arrays, boundary conditions

3. **Test Exceptions**
   ```php
   public function test_throws_exception_when_invoice_not_found(): void
   {
       $this->expectException(InvoiceNotFoundException::class);
       
       $manager = new InvoiceManager($this->mockRepository());
       $manager->findById('non-existent-id');
   }
   ```

4. **Mock All Dependencies**
   ```php
   private function mockRepository(): InvoiceRepositoryInterface
   {
       return $this->createMock(InvoiceRepositoryInterface::class);
   }
   ```

### Test Naming Convention

```php
// Pattern: test_{method_name}_{scenario}_{expected_result}
public function test_create_invoice_with_valid_data_returns_invoice(): void

public function test_create_invoice_with_invalid_data_throws_exception(): void

public function test_find_invoice_by_id_when_not_found_throws_exception(): void
```

---

## 9. Documentation Requirements

### Method Docblocks

All public methods must have complete docblocks:

```php
/**
 * Create a new customer invoice
 * 
 * This method creates an invoice, assigns a unique invoice number,
 * and posts the corresponding journal entry to the general ledger.
 * 
 * @param string $customerId Customer ULID
 * @param array<int, InvoiceLineItem> $lineItems Invoice line items
 * @param \DateTimeImmutable $invoiceDate Invoice date
 * @param array<string, mixed> $metadata Optional metadata
 * @return InvoiceInterface Created invoice
 * @throws CustomerNotFoundException If customer not found
 * @throws InvalidInvoiceException If validation fails
 * @throws PeriodClosedException If period is closed
 */
public function create(
    string $customerId,
    array $lineItems,
    \DateTimeImmutable $invoiceDate,
    array $metadata = []
): InvoiceInterface {
    // ...
}
```

### Package README.md

Every package must have comprehensive README.md:

**Required Sections:**
1. **Overview** - Purpose and capabilities
2. **Installation** - Composer install command
3. **Features** - List of features
4. **Quick Start** - Basic usage example
5. **Usage Examples** - Detailed examples
6. **Available Interfaces** - List of all contracts
7. **Integration Guide** - How to use in Laravel/Symfony
8. **Testing** - How to run tests
9. **License** - MIT License

---

## 10. Code Quality Checklist

Before committing any code:

### Package-Level Checks
- [ ] Consulted `docs/NEXUS_PACKAGES_REFERENCE.md` to avoid reimplementing functionality
- [ ] Package has valid `composer.json` with `"php": "^8.3"`
- [ ] Package has comprehensive `README.md`
- [ ] Package has `LICENSE` file (MIT)
- [ ] Package has `.gitignore`

### Code-Level Checks
- [ ] `declare(strict_types=1);` at top of every file
- [ ] All classes are `final readonly`
- [ ] All dependencies injected via constructor as interfaces
- [ ] All properties are `private readonly`
- [ ] No framework facades used (`Log::`, `Cache::`, `DB::`)
- [ ] No global helpers used (`config()`, `app()`, `now()`, `dd()`)
- [ ] Native enums used instead of class constants
- [ ] `match` used instead of `switch`
- [ ] All public methods have complete docblocks
- [ ] All methods have full type hints (params and return)

### Testing Checks
- [ ] All public methods have unit tests
- [ ] Test coverage > 80%
- [ ] All exceptions tested
- [ ] Edge cases tested
- [ ] Tests use mocks, not real database

### Documentation Checks
- [ ] All public methods have docblocks with `@param`, `@return`, `@throws`
- [ ] README.md has usage examples
- [ ] README.md has integration guide
- [ ] Complex logic has inline comments

---

## 11. Anti-Patterns to Avoid

### ❌ Anti-Pattern 1: God Repository

**Problem:** Single repository interface with too many responsibilities.

```php
// ❌ WRONG: Fat repository interface
interface TenantRepositoryInterface
{
    // CRUD
    public function create(TenantInterface $tenant): string;
    public function update(TenantInterface $tenant): void;
    public function delete(string $id): void;
    
    // Queries
    public function findById(string $id): TenantInterface;
    public function findAll(): array;
    
    // Validation
    public function validateSlug(string $slug): bool;
    
    // Business Logic
    public function getExpiredTrials(): array;
    public function getSuspended(): array;
    
    // Reporting
    public function getTenantStatistics(): array;
    public function getAgingReport(): array;
}
```

**✅ Solution:** Split into focused interfaces following ISP.

```php
// ✅ CORRECT: Focused interfaces
interface TenantQueryInterface
{
    public function findById(string $id): TenantInterface;
    public function findAll(): array;
}

interface TenantPersistInterface
{
    public function create(TenantInterface $tenant): string;
    public function update(TenantInterface $tenant): void;
    public function delete(string $id): void;
}

interface TenantValidationInterface
{
    public function validateSlug(string $slug): bool;
}

// Business logic in domain service
final readonly class TenantStatusService
{
    public function __construct(
        private TenantQueryInterface $query
    ) {}
    
    public function getExpiredTrials(): array
    {
        $tenants = $this->query->findAll();
        return array_filter($tenants, fn($t) => $t->isTrialExpired());
    }
}
```

### ❌ Anti-Pattern 2: Framework Coupling

**Problem:** Package depends on framework-specific code.

```php
// ❌ WRONG: Using Laravel facades
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final readonly class InvoiceManager
{
    public function create(array $data): Invoice
    {
        Log::info('Creating invoice');
        
        $invoice = new Invoice($data);
        
        Cache::put("invoice:{$invoice->id}", $invoice, 3600);
        
        return $invoice;
    }
}
```

**✅ Solution:** Inject interfaces.

```php
// ✅ CORRECT: Inject interfaces
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class InvoiceManager
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}
    
    public function create(array $data): Invoice
    {
        $this->logger->info('Creating invoice');
        
        $invoice = new Invoice($data);
        
        $this->cache->set("invoice:{$invoice->id}", $invoice, 3600);
        
        return $invoice;
    }
}
```

### ❌ Anti-Pattern 3: Stateful Package

**Problem:** Package stores state in memory.

```php
// ❌ WRONG: State stored in class
final class RateLimiter
{
    private array $attempts = [];
    
    public function attempt(string $key): bool
    {
        $this->attempts[$key] = ($this->attempts[$key] ?? 0) + 1;
        return $this->attempts[$key] <= 5;
    }
}
```

**✅ Solution:** Externalize state.

```php
// ✅ CORRECT: State externalized
final readonly class RateLimiter
{
    public function __construct(
        private RateLimiterStorageInterface $storage
    ) {}
    
    public function attempt(string $key): bool
    {
        $attempts = $this->storage->increment($key);
        return $attempts <= 5;
    }
}
```

### ❌ Anti-Pattern 4: Mixing Commands and Queries

**Problem:** Single repository interface mixes reads and writes.

```php
// ❌ WRONG: Mixed CQRS
interface InvoiceRepositoryInterface
{
    public function create(InvoiceInterface $invoice): string;
    public function findById(string $id): InvoiceInterface;
    public function update(InvoiceInterface $invoice): void;
    public function findAll(): array;
}
```

**✅ Solution:** Separate Query and Persist.

```php
// ✅ CORRECT: CQRS separation
interface InvoiceQueryInterface
{
    public function findById(string $id): InvoiceInterface;
    public function findAll(): array;
}

interface InvoicePersistInterface
{
    public function create(InvoiceInterface $invoice): string;
    public function update(InvoiceInterface $invoice): void;
    public function delete(string $id): void;
}
```

### ❌ Anti-Pattern 5: Missing Type Hints

**Problem:** Methods without type hints.

```php
// ❌ WRONG: No type hints
public function create($data)
{
    return new Invoice($data);
}
```

**✅ Solution:** Full type hints.

```php
// ✅ CORRECT: Complete type hints
public function create(array $data): InvoiceInterface
{
    return new Invoice($data);
}
```

---

## Quick Reference Card

### File Structure Template

```php
<?php

declare(strict_types=1);

namespace Nexus\YourPackage\Services;

use Nexus\YourPackage\Contracts\EntityInterface;
use Nexus\YourPackage\Contracts\EntityRepositoryInterface;
use Nexus\YourPackage\Exceptions\EntityNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Manages entity lifecycle
 */
final readonly class EntityManager implements EntityManagerInterface
{
    public function __construct(
        private EntityRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Create new entity
     * 
     * @param array<string, mixed> $data Entity data
     * @return EntityInterface Created entity
     * @throws InvalidEntityException If validation fails
     */
    public function create(array $data): EntityInterface
    {
        // Implementation
    }
}
```

### Repository Interface Decision Tree

```
Is this a read operation?
├─ Yes → Use EntityQueryInterface
└─ No
   └─ Is this a write operation?
      ├─ Yes → Use EntityPersistInterface
      └─ No
         └─ Does this require search/indexing?
            ├─ Yes → Create EntitySearchInterface
            └─ No
               └─ Is this bulk/streaming?
                  ├─ Yes → Create EntityStreamInterface
                  └─ No
                     └─ Does this cross aggregate boundaries?
                        ├─ Yes → Create relationship interface
                        └─ No
                           └─ Is this external system integration?
                              ├─ Yes → Create connector interface
                              └─ No
                                 └─ Is this specific state transition?
                                    ├─ Yes → Create transition interface
                                    └─ No → Extend Query or Persist
```

---

## 12. Architectural Violation Detection

### Automated Violation Scans

Before merging any package code, run these automated scans:

```bash
# Navigate to package directory
cd packages/PackageName

# ISP Violations (Fat Interfaces)
echo "=== ISP Violations ==="
grep -r "RepositoryInterface" src/Contracts/ | grep -E "(get|calculate|find|validate|create)" 
# If one interface matches multiple verbs → ISP violation

# Framework References
echo "=== Framework References ==="
grep -ri "eloquent\|laravel\|symfony" src/
# Any matches in src/ → Framework coupling violation

# Global Helpers
echo "=== Global Helpers ==="
grep -r "now()\|config()\|app()\|dd()\|env()" src/
# Any matches → Global helper violation

# CQRS Violations  
echo "=== CQRS Violations ==="
grep -r "paginate\|PaginatedResult\|LengthAwarePaginator" src/Contracts/
# Any matches in Contracts/ → CQRS violation

# Stateless Violations
echo "=== Stateless Violations ==="
grep -r "private array\|private int\|private string" src/Services/ | grep -v "readonly"
# Non-readonly properties in services → Stateless violation
```

### Auto-Rejection Criteria

**REJECT immediately if:**
- Package has > 3 violations from above scans
- Any violation found in `src/Contracts/` (contracts define architecture)
- Framework references in docblocks (leaky abstraction)
- composer.json requires framework packages (`laravel/framework`, `symfony/symfony`)

### ISP (Interface Segregation Principle) Violations

**❌ REJECT if:**
- Any interface has more than 7-10 methods
- Interface name ends in "RepositoryInterface" but contains business logic methods (e.g., `getExpiredTrials()`, `calculateTotal()`)
- Single interface mixes write operations (create, update, delete) with read operations (find, get, all)
- Interface contains both persistence operations and validation operations
- DocBlock says "This interface handles X, Y, and Z" (multiple responsibilities)

**✅ ACCEPT if:**
- Each interface has single, focused responsibility
- Write operations in `*PersistInterface`
- Read operations in `*QueryInterface`
- Validation in `*ValidationInterface`
- Business logic in domain service classes, not interfaces

### CQRS Violations

**❌ REJECT if:**
- Repository interface contains both `create()` and `findById()` methods (mixed command/query)
- Domain layer interface has pagination parameters (`int $page`, `int $perPage`)
- Method returns paginated result object (`PaginatedResult`, `LengthAwarePaginator`)
- Repository has reporting methods (`getAgingReport()`, `getStatistics()`)

**✅ ACCEPT if:**
- Commands (write) separated from queries (read)
- Query methods return raw arrays (`array<TenantInterface>`)
- Pagination handled in application layer
- Reporting queries in application-specific read models

### Stateless Architecture Violations

**❌ REJECT if:**
- Service class has private properties storing state (e.g., `private array $cache = []`)
- Constructor stores non-interface dependencies
- Service class is NOT `readonly`
- Long-term state (session, impersonation, circuit breaker) stored in-memory
- Properties declared without `readonly` modifier (except request-scoped state in context managers)

**✅ ACCEPT if:**
- All dependencies are `readonly` and injected via constructor
- Class declared as `final readonly class`
- Long-term state externalized via `*StorageInterface`
- Only request-scoped ephemeral state allowed (e.g., `TenantContextManager::$currentTenantId`)

### Framework Agnosticism Violations

**❌ REJECT if:**
- DocBlock mentions "Eloquent", "Laravel", "Symfony", "Doctrine" (framework names)
- Method type-hints framework classes (`Illuminate\Http\Request`, `Symfony\Component\HttpFoundation\Request`)
- Uses framework facades (`DB::`, `Cache::`, `Log::`, `Event::`)
- Uses global helpers (`now()`, `config()`, `app()`, `dd()`, `env()`)
- composer.json requires framework packages

**✅ ACCEPT if:**
- DocBlock says "consuming application provides implementation"
- All dependencies are PSR interfaces or Nexus package interfaces
- No framework-specific code or terminology
- composer.json only requires `php: ^8.3`, PSR packages, or other Nexus packages

---

## 13. Package Documentation Standards

### Required Package Files

Every package MUST include:
- `composer.json` - Package definition with `"php": "^8.3"`
- `LICENSE` - MIT License
- `.gitignore` - Package-specific ignores
- `README.md` - Comprehensive usage guide with examples
- `IMPLEMENTATION_SUMMARY.md` - Progress tracking and metrics
- `REQUIREMENTS.md` - Standardized requirements table
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation for funding assessment
- `docs/` folder - User documentation
- `src/` folder - Source code (Contracts, Services, Exceptions, etc.)
- `tests/` folder - Unit and feature tests

### Documentation Anti-Patterns (FORBIDDEN)

**❌ Do NOT create:**
- Duplicate README files in subdirectories
- TODO.md files (use IMPLEMENTATION_SUMMARY.md)
- Random markdown files without clear purpose
- Migration/deployment guides (packages are libraries)
- Status update files (use IMPLEMENTATION_SUMMARY.md)

**Principle:** Each document serves a **unique, non-overlapping purpose**. No duplication.

### When to Use `Core/` Folder

Create a `Core/` folder when your package is **complex** and contains internal components that should **never be accessed directly** by consumers.

**When to Create:**
- High complexity (Analytics, Workflow, Manufacturing)
- Internal contracts for engine components
- Value Objects or Internal Entities that should only be handled by the main Manager
- Engine logic where the main Manager is merely an orchestrator

**When to Skip:**
- Simple packages (Uom, Tenant)
- Fewer than 10 total files
- Manager class under 200 lines

---

## 14. Hybrid Event Architecture

Nexus uses two event patterns for different needs:

### The "Feed" View: `Nexus\AuditLogger` (95% of use cases)

**Purpose:** User-facing timeline/feed displaying "what happened" on an entity's page.

**Use Case:** Customer records, HR data, settings, inventory adjustments, approval workflows.

**Mechanism:**
- Domain packages call `AuditLogManagerInterface::log()` after transaction commit
- Records the **result** of an action (e.g., "Invoice status changed to Paid")
- Simple to query and display in chronological order
- Human-readable descriptions for non-technical users

**Example:**
```php
$this->auditLogger->log(
    $entityId,
    'status_change',
    'Invoice status updated from Draft to Paid by User'
);
```

### The "Replay" Capability: `Nexus\EventStream` (Critical domains only)

**Purpose:** Immutable event log enabling **state reconstruction** at any point in history.

**Use Case (Critical Domains Only):**
- **Finance (GL)**: Every debit/credit is an event (`AccountCreditedEvent`, `AccountDebitedEvent`)
- **Inventory**: Every stock change is an event (`StockReservedEvent`, `StockAddedEvent`, `StockShippedEvent`)
- **Large Enterprise AP/AR**: Optional event sourcing for payment lifecycle tracking

**Example:**
```php
// Publish event to EventStream
$this->eventStore->append(
    $aggregateId,
    new AccountCreditedEvent(
        accountId: '1000',
        amount: Money::of(1000, 'MYR'),
        description: 'Customer payment received'
    )
);

// Rebuild state at specific point in time
$balance = $this->eventStream->getStateAt($accountId, '2024-10-15');
```

**Decision Rule:** Use EventStream only when you need temporal queries for legal compliance.

---

## 15. Compliance & Statutory Architecture

All compliance activities are divided into two distinct packages:

### A. `Nexus\Compliance` (The Orchestrator & Rulebook)

Manages **Operational Compliance** and the **System's internal governance**. It deals with the mandatory *behavior* and *configuration* required by a scheme (e.g., ISO, internal policy).

### B. `Nexus\Statutory` (The Contract Hub & Reporter)

Manages **Reporting Compliance** and the specific formats mandated by a legal authority. It deals with the data tags, schemas, and logistical metadata required for filing.

---

## 16. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| **Packages** | PascalCase | `Tenant`, `AuditLogger` |
| **Composer names** | kebab-case | `nexus/audit-logger` |
| **Namespaces** | `Nexus\PackageName` | `Nexus\Receivable` |
| **Interfaces** | Descriptive with `Interface` suffix | `TenantRepositoryInterface` |
| **Services** | Domain-specific managers | `TenantManager`, `StockManager` |
| **Exceptions** | Descriptive with `Exception` suffix | `TenantNotFoundException` |
| **Enums** | Descriptive nouns | `InvoiceStatus`, `PaymentMethod` |
| **Value Objects** | Domain nouns | `Money`, `Period`, `Coordinates` |

---

## 17. Mandatory Pre-Implementation Checklist

**BEFORE implementing ANY feature, you MUST:**

1. **Consult `docs/NEXUS_PACKAGES_REFERENCE.md`** - This document lists all 50+ available first-party packages and their capabilities
2. **Use existing packages FIRST** - If a Nexus package provides the functionality, you MUST use it via dependency injection
3. **Never reimplement package functionality** - Creating custom implementations when packages exist is an architectural violation

**Example Violations to Avoid:**
- ❌ Creating custom metrics collector when `Nexus\Monitoring` exists
- ❌ Building custom audit logger when `Nexus\AuditLogger` exists  
- ❌ Implementing file storage when `Nexus\Storage` exists
- ❌ Creating notification system when `Nexus\Notifier` exists

**See `docs/NEXUS_PACKAGES_REFERENCE.md` for the complete "I Need To..." decision matrix.**

---

## 18. Package Folder Structure

### Clear Separation of Concerns

| Folder | Rule of Thumb | Example Content |
|--------|---------------|-----------------|
| **`src/Services/`** | **Public API:** Only expose the high-level logic users need | Managers, Coordinators, Façade accessors |
| **`src/Core/`** | **Internal Engine:** Complex internal logic not part of the public API | Internal Contracts, Value Objects, Engine components |
| **`src/Exceptions/`** | **Domain-Specific Errors:** Custom exceptions | All exceptions extending PHP base exceptions |
| **`src/Enums/`** | **Fixed Value Sets:** Native PHP enums for statuses, types | Status, Level, Type enums |
| **`src/ValueObjects/`** | **Immutable Domain Data:** Money, Period, Coordinates | All readonly classes with validation |
| **`src/Contracts/`** | **Interfaces:** All package contracts | Repository, Manager, Entity interfaces |

---

## 19. Development Workflow

### Creating a New Package

**📌 For complete package creation instructions, see:** `.github/prompts/create-package-instruction.prompt.md`

**Quick checklist:**

1. **Initialize Structure** - composer.json, LICENSE, .gitignore
2. **Create Documentation FIRST** - REQUIREMENTS.md, IMPLEMENTATION_SUMMARY.md, README.md, TEST_SUITE_SUMMARY.md, docs/
3. **Implement Code** - Contracts, Services, Exceptions, Enums, ValueObjects
4. **Write Tests** - Unit and feature tests
5. **Update Documentation** - Keep all docs in sync with implementation
6. **Register in Monorepo** - Update root composer.json
7. **Validate** - Run tests, verify documentation completeness

### Implementing a New Feature

**Always update documentation alongside code changes.**

1. **Requirements Analysis**
   - Check if logic exists → Consult `docs/NEXUS_PACKAGES_REFERENCE.md`
   - Add new requirements to `REQUIREMENTS.md` with proper codes
   - Update `IMPLEMENTATION_SUMMARY.md` with feature plan

2. **Implementation**
   - Define contracts → Create/update interfaces in `src/Contracts/`
   - Implement services → Create/update manager/service classes
   - Create exceptions → Define domain-specific errors
   - Update `docs/api-reference.md` with new interfaces/methods

3. **Testing**
   - Write tests → Unit tests for all business logic
   - Update `TEST_SUITE_SUMMARY.md` with new tests and coverage

4. **Documentation**
   - Update `README.md` with new feature examples
   - Add examples to `docs/examples/` if applicable
   - Update `docs/getting-started.md` if feature affects setup
   - Update `docs/integration-guide.md` with new integration patterns
   - Mark requirements as Complete in `REQUIREMENTS.md`
   - Update metrics in `IMPLEMENTATION_SUMMARY.md`

**Remember:** A feature is not complete until all documentation is updated.

---

## 20. Facade & Global Helper Prohibition

The use of framework Facades and global helpers is **strictly forbidden** in all code within the `packages/` directory.

### Absolute Prohibitions (Zero Tolerance)

| Forbidden Artifact | Atomic Replacement |
| :--- | :--- |
| **`Log::...`** | Inject `LoggerInterface` (PSR-3) |
| **`Cache::...`** | Inject `CacheRepositoryInterface` |
| **`DB::...`** | Inject `RepositoryInterface` |
| **`Config::...`** | Inject `SettingsManagerInterface` |
| **`Mail::...`** | Inject `NotificationManagerInterface` |
| **`Storage::...`** | Inject `StorageInterface` |
| **`Event::...`** | Inject `EventDispatcherInterface` |
| **`Queue::...`** | Inject `QueueInterface` |
| **Global Helpers** (`now()`, `config()`, `app()`, `dd()`, `env()`, etc.) | Inject interfaces or use native PHP |

### Required Replacements

**Logging Example:**
```php
// ✅ CORRECT
use Psr\Log\LoggerInterface;

public function __construct(
    private readonly LoggerInterface $logger
) {}

public function processData(array $data): void
{
    $this->logger->info('Processing data', ['count' => count($data)]);
}
```

**Time/Date Example:**
```php
// ✅ CORRECT - Define Clock Contract
namespace Nexus\YourPackage\Contracts;

interface ClockInterface
{
    public function getCurrentTime(): \DateTimeImmutable;
}

// Use in service
public function __construct(
    private readonly ClockInterface $clock
) {}

public function isExpired(\DateTimeImmutable $expiresAt): bool
{
    return $expiresAt < $this->clock->getCurrentTime();
}
```

---

## 21. Key Reminders

1. **Packages are pure engines**: Pure logic, no persistence, no framework coupling
2. **Interfaces define needs**: Every external dependency is an interface
3. **Consumers provide implementations**: Applications bind concrete classes to interfaces
4. **Always check NEXUS_PACKAGES_REFERENCE.md** before creating new functionality
5. **When in doubt, inject an interface**
6. **All primary keys are ULIDs** (string-based UUID v4)
7. **Follow PSR-12** coding standards
8. **Use meaningful variable and method names**
9. **Validate inputs in services** before processing
10. **Throw descriptive exceptions** for error cases

---

## Important References

- **Architecture Guidelines:** `ARCHITECTURE.md`
- **Package Reference:** `docs/NEXUS_PACKAGES_REFERENCE.md` - **MANDATORY READ**
- **Package Creation:** `.github/prompts/create-package-instruction.prompt.md`
- **Package Requirements:** `docs/REQUIREMENTS_*.md`
- **Implementation Summaries:** `docs/*_IMPLEMENTATION_SUMMARY.md`

---

**Last Updated:** November 26, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all developers and coding agents
