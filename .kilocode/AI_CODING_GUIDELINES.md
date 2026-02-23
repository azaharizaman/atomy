# AI Coding Agent Guidelines

> This is a living document that evolves with each discovered mistake. Last updated: 2026-02-23 (CodeRabbit PR #237 review)

## Purpose
This document captures common mistakes made by AI coding agents and provides guidelines to avoid them. It should be updated whenever new patterns of errors are discovered.

---

## Critical Rules (Never Violate)

### 1. No Stub Implementations in Production Code

**Problem**: AI agents sometimes leave stub methods that return empty/hardcoded values instead of real implementations.

**Example from PR #227**:
```php
// WRONG - Stub implementation
public function getOrderReservations(OrderId $orderId): array
{
    return []; // TODO: Implement actual reservation lookup
}

// CORRECT - Real implementation
public function getOrderReservations(OrderId $orderId): array
{
    return $this->reservationRepository->findByOrderId($orderId);
}
```

**Prevention**:
- Always implement real database queries or business logic
- If implementation requires external dependencies not yet available, add proper TODO comments AND throw `NotImplementedException`
- Never merge code with stub implementations that silently return empty/hardcoded values
- Code reviews must flag any method returning `[]`, `''`, `0`, `null`, or hardcoded values without clear justification

### 2. Laravel Cache: Use Repository, Not Store

**Problem**: Laravel's `Illuminate\Contracts\Cache\Store` interface does NOT have a `remember()` method. Only `Illuminate\Contracts\Cache\Repository` has it. Calling a method that doesn't exist causes runtime errors.

**Example from PR #237 additional fix**:
```php
// WRONG - Using Store interface which doesn't have remember()
public function __construct(
    private readonly Store $cache  // Store doesn't have remember()!
) {}

public function remember(string $key, int $ttl, callable $callback): mixed
{
    return $this->cache->remember($key, $ttl, $callback); // FAILS!
}

// CORRECT - Use Repository interface or implement manually
public function __construct(
    private readonly Repository $cache  // Repository has remember()
) {}

// OR implement manually:
public function remember(string $key, int $ttl, callable $callback): mixed
{
    $value = $this->cache->get($key);
    if ($value !== null) {
        return $value;
    }
    $value = $callback();
    $this->cache->put($key, $value, $ttl);
    return $value;
}
```

**Prevention**:
- When using Laravel cache, type-hint `Repository` instead of `Store` if you need methods like `remember()`
- Alternatively, implement the logic manually by checking `get()`, calling callback if null, then `put()`
- Check Laravel documentation to verify which interface provides which methods

### 3. Database Operations Must Be Atomic

**Problem**: Multiple repository calls without transaction wrapping can lead to partial state updates.

**Example from PR #227**:
```php
// WRONG - Non-atomic operations
public function convertQuoteToOrder(QuoteId $quoteId): Order
{
    $order = $this->orderRepository->create($orderData);
    $this->quoteRepository->markAsConverted($quoteId); // If this fails, we have an orphan order
    return $order;
}

// CORRECT - Atomic operations
public function convertQuoteToOrder(QuoteId $quoteId): Order
{
    return DB::transaction(function () use ($quoteId) {
        $order = $this->orderRepository->create($orderData);
        $this->quoteRepository->markAsConverted($quoteId);
        return $order;
    });
}
```

**Prevention**:
- Wrap multiple related database operations in transactions
- Use `DB::transaction()` or equivalent
- Consider rollback scenarios and ensure data consistency
- Ask: "If the second operation fails, what happens to the first?"

### 4. Foreign Key Constraints Are Mandatory

**Problem**: Missing FK constraints lead to orphaned records and referential integrity issues.

**Example from PR #227**:
```php
// WRONG - No FK constraint
$table->uuid('order_id');
$table->uuid('product_id');

// CORRECT - With FK constraints
$table->uuid('order_id');
$table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
$table->uuid('product_id');
$table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
```

**Prevention**:
- Every column referencing another table MUST have a FK constraint
- Choose appropriate `ON DELETE` action:
  - `RESTRICT`: Prevent deletion if referenced (default for most cases)
  - `CASCADE`: Delete related records (use carefully, e.g., order items when order deleted)
  - `SET NULL`: Clear reference (only if column is nullable)
- Document the reasoning for the chosen action in migration comments

### 5. Proper ID Generation

**Problem**: Using inappropriate ID generation methods.

**Example from PR #227**:
```php
// WRONG - Random bytes for IDs
$orderId = bin2hex(random_bytes(16));

// CORRECT - Use proper ID generation
$orderId = OrderId::generate(); // Uses Ramsey UUID or similar
// Or for database IDs
$orderId = $this->idGenerator->generate();
```

**Prevention**:
- Use UUID libraries (Ramsey UUID, Symfony Uid) for UUID generation
- Use database sequences or auto-increment for integer IDs
- Never use `random_bytes()` directly for ID generation
- Ensure ID generation is consistent with the project's ID strategy

---

## Database Migration Standards

### Foreign Key Constraints

**Rule**: All relational fields must have FK constraints.

```php
// Standard FK constraint pattern
$table->foreignId('user_id')
    ->constrained('users')
    ->onDelete('restrict')
    ->onUpdate('cascade');

// For UUID columns
$table->uuid('order_id');
$table->foreign('order_id')
    ->references('id')
    ->on('orders')
    ->onDelete('cascade');
```

**Checklist**:
- [ ] Every `*_id` column has a corresponding FK constraint
- [ ] `ON DELETE` action is appropriate for the relationship
- [ ] `ON UPDATE` action is specified if needed
- [ ] Index is created for the FK column (usually automatic)

### Indexes

**Rule**: Add indexes for frequently queried columns.

```php
// Single column index
$table->index('status');

// Composite index for multi-column queries
$table->index(['tenant_id', 'status']);
$table->index(['customer_id', 'created_at']);

// Unique index for business keys
$table->unique(['tenant_id', 'order_number']);
```

**When to add indexes**:
- Columns used in WHERE clauses
- Columns used in JOIN conditions (FK columns usually auto-indexed)
- Columns used in ORDER BY clauses
- Combinations of columns frequently queried together

### Migration Naming Convention

```text
Version{YYYYMMDDHHMMSS}.php
```

Example: `Version20260219100200.php`

---

## PHP/Laravel Specific Guidelines

### PSR-4 Compliance

**Problem**: Multiple classes in one file or file path not matching namespace.

**Rules**:
- One class per file
- File path must match namespace
- Class name must match file name

```
# CORRECT
packages/Sales/src/Services/SalesOrderManager.php
→ namespace Atomy\Sales\Services;
→ class SalesOrderManager

# WRONG
packages/Sales/src/Services/Managers.php (contains multiple classes)
packages/Sales/src/Services/salesOrderManager.php (wrong case)
```

### Type Safety

**Problem**: Type coercion issues when passing values to methods expecting specific types.

**Example from PR #227**:
```php
// WRONG - Type coercion issue
$productId = $item['product_id']; // Could be string or int
$product = $this->productRepository->find($productId); // May fail

// CORRECT - Explicit type handling
$productId = ProductId::fromString($item['product_id']);
$product = $this->productRepository->find($productId);

// CORRECT - Type coercion with validation
$quantity = (int) $item['quantity'];
if ($quantity <= 0) {
    throw new InvalidQuantityException('Quantity must be positive');
}
```

**Prevention**:
- Use typed properties and return types
- Validate and coerce types before operations
- Use Value Objects for domain identifiers
- Never assume input types from external sources

### Float Comparisons

**Problem**: Direct float comparisons due to precision issues.

```php
// WRONG - Direct float comparison
if ($total === $expectedTotal) {
    // May fail due to floating point precision
}

// CORRECT - Use tolerance
if (abs($total - $expectedTotal) < 0.0001) {
    // Handles floating point precision
}

// BETTER - Use Money library
if ($total->equals($expectedTotal)) {
    // Using moneyphp/money or similar
}
```

### Redundant Try-Catch Blocks

**Problem**: Try-catch blocks that catch and immediately re-throw without adding value.

```php
// WRONG - Redundant try-catch
try {
    $order = $this->orderRepository->find($orderId);
} catch (OrderNotFoundException $e) {
    throw $e;
}

// CORRECT - Let it propagate
$order = $this->orderRepository->find($orderId);

// CORRECT - Add value in catch
try {
    $order = $this->orderRepository->find($orderId);
} catch (OrderNotFoundException $e) {
    Log::warning('Order not found', ['order_id' => $orderId]);
    throw new OrderLookupException("Order {$orderId} not found", 0, $e);
}
```

---

## Documentation Standards

### Code Examples Must Match Actual API

**Problem**: Documentation examples that don't match the actual method signatures or behavior.

**Example from PR #227**:
```php
// WRONG - Documentation says:
$order = $orderManager->createOrder($customerId, $items);

// But actual API is:
$order = $orderManager->createOrder(new CreateOrderDTO(
    customerId: $customerId,
    items: $items,
    shippingAddress: $address
));
```

**Prevention**:
- Copy actual code from tests or implementation
- Verify method names match exactly
- Verify parameter names and types
- Run documentation examples as part of test suite when possible

### Markdown Standards

**Rule**: All fenced code blocks must have language tags.

```markdown
<!-- WRONG -->
```
public function getOrder(): Order
```

<!-- CORRECT -->
```php
public function getOrder(): Order
```

**Common language tags**:
- `php` for PHP code
- `bash` or `shell` for terminal commands
- `json` for JSON data
- `yaml` for YAML configuration
- `markdown` or `md` for markdown examples
- `sql` for SQL queries

### Anchor Links

**Problem**: Broken anchor links in documentation.

```markdown
<!-- WRONG -->
See [Getting Started](#getting-started) for details.
<!-- But heading is ## Getting Started Guide -->

<!-- CORRECT -->
See [Getting Started Guide](#getting-started-guide) for details.
<!-- Matches: ## Getting Started Guide -->
```

**Prevention**:
- Anchor links are lowercase, spaces become hyphens
- Punctuation is removed
- Verify links work after writing documentation

---

## Code Quality Checklist

Before submitting any code, verify:

### Critical
- [ ] No stub implementations (empty returns, hardcoded values)
- [ ] All database operations are atomic (wrapped in transactions)
- [ ] FK constraints added for all relational fields
- [ ] Proper ID generation (not `random_bytes`)

### Code Quality
- [ ] PSR-4 compliance (one class per file, namespace matches path)
- [ ] Type validation/coercion in place for external inputs
- [ ] No redundant try-catch blocks
- [ ] Float comparisons use tolerance or Money library
- [ ] Proper return types declared

### Database
- [ ] Indexes added for frequently queried columns
- [ ] Migration naming follows convention
- [ ] Rollback tested (down method works)

### Documentation
- [ ] Code examples match actual API
- [ ] All code blocks have language tags
- [ ] Anchor links verified
- [ ] Method names in docs match implementation

### Performance
- [ ] No N+1 query issues
- [ ] Logging is conditional, not always on
- [ ] Expensive operations are cached when appropriate

---

## Common Patterns to Avoid

### 1. Silent Failures

```php
// WRONG - Silent failure
public function processOrder(OrderId $orderId): void
{
    $order = $this->orderRepository->find($orderId);
    if ($order === null) {
        return; // Silent failure
    }
    // ...
}

// CORRECT - Explicit failure
public function processOrder(OrderId $orderId): void
{
    $order = $this->orderRepository->find($orderId);
    if ($order === null) {
        throw new OrderNotFoundException($orderId);
    }
    // ...
}
```

### 2. Magic Strings

```php
// WRONG - Magic strings
if ($order->status === 'completed') {
    // ...
}

// CORRECT - Use constants or enums
if ($order->status === OrderStatus::COMPLETED) {
    // ...
}

// Or with PHP 8.1+ Enums
if ($order->status === OrderStatus::Completed) {
    // ...
}
```

### 3. God Classes

**Problem**: Classes with too many responsibilities.

**Prevention**:
- Follow Single Responsibility Principle
- If class name contains "And", split it
- If constructor has more than 5 dependencies, consider splitting
- Use Value Objects for complex data structures

### 4. Missing Null Checks

```php
// WRONG - No null check
$customerName = $order->getCustomer()->getName();

// CORRECT - Null safe operator (PHP 8.0+)
$customerName = $order->getCustomer()?->getName();

// CORRECT - Explicit null check
$customer = $order->getCustomer();
if ($customer === null) {
    throw new OrderMissingCustomerException($order->getId());
}
$customerName = $customer->getName();
```

---

## Error Signatures from PaymentGateway & Payroll Refactoring

> Identified during code review on 2026-02-19

### Security Issues (Webhook Verification)

#### 1. Incorrect Webhook Signature Algorithm

**Problem**: Using the wrong cryptographic algorithm for webhook signature verification. Each payment provider has specific algorithm requirements.

**Example from Stripe Webhook Handler**:
```php
// WRONG - Using HMAC-SHA256 when Stripe requires ECDSA
public function verifySignature(string $payload, string $signature, string $secret): bool
{
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// CORRECT - Using Stripe's official verification method
public function verifySignature(string $payload, string $signature, string $secret): bool
{
    try {
        \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            $secret
        );
        return true;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        return false;
    }
}
```

**Why It Matters**: Using the wrong algorithm allows attackers to forge webhook payloads, potentially leading to:
- Fraudulent payment confirmations
- Unauthorized order fulfillment
- Financial loss

**Prevention**:
- Always use the provider's official SDK for signature verification
- Never implement custom cryptographic verification unless absolutely necessary
- Document the required algorithm in code comments

#### 2. Missing Certificate Validation

**Problem**: Not fetching and validating certificates from provider URLs for webhook signature verification.

**Example from PayPal Webhook Handler**:
```php
// WRONG - Not validating certificate chain
public function verifySignature(string $payload, array $headers): bool
{
    $signature = $headers['paypal-transmission-sig'];
    // Directly verifying without certificate validation
    return openssl_verify($payload, base64_decode($signature), $publicKey) === 1;
}

// CORRECT - Full certificate validation
public function verifySignature(string $payload, array $headers): bool
{
    $certUrl = $headers['paypal-cert-url'];
    
    // 1. Validate URL is from PayPal
    if (!str_starts_with($certUrl, 'https://api.paypal.com/')) {
        throw new InvalidCertificateUrlException('Certificate URL must be from PayPal domain');
    }
    
    // 2. Fetch and validate certificate
    $certificate = $this->fetchCertificate($certUrl);
    $publicKey = openssl_pkey_get_public($certificate);
    
    // 3. Verify certificate chain
    if (!$this->validateCertificateChain($certificate)) {
        throw new CertificateValidationException('Invalid certificate chain');
    }
    
    // 4. Now verify signature
    $signature = base64_decode($headers['paypal-transmission-sig']);
    return openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
}
```

**Why It Matters**: Without certificate validation:
- Attackers can use self-signed certificates to forge signatures
- Man-in-the-middle attacks become possible
- Webhook authenticity cannot be trusted

**Prevention**:
- Always fetch certificates from provider URLs
- Validate certificate chain and expiration
- Verify certificate is from expected domain
- Cache validated certificates with appropriate TTL

#### 3. Incorrect Signed String Format

**Problem**: Not following the provider's exact specification for constructing the signed string.

**Example from Square Webhook Handler**:
```php
// WRONG - Incorrect signed string format
$signedString = $payload;

// CORRECT - Square requires specific format
$signedString = implode('|', [
    $notificationId,
    $eventType,
    $payload,
    $timestamp
]);

// Example from PayPal Webhook Handler
// WRONG - Missing required components
$signedString = $payload;

// CORRECT - PayPal requires specific format
$signedString = implode('|', [
    $transmissionId,
    $timestamp,
    $webhookId,
    crc32($payload)
]);
```

**Why It Matters**: Incorrect signed string format:
- Valid webhooks will be rejected
- Invalid webhooks might be accepted
- Security verification becomes meaningless

**Prevention**:
- Read provider documentation carefully
- Follow exact format specification
- Include all required components in correct order
- Add unit tests with provider's example payloads

### Logic Issues

#### 4. Missing Tenant Context

**Problem**: Not requiring tenant ID in multi-tenant operations, leading to potential data leakage.

**Example from Payroll Service**:
```php
// WRONG - No tenant context
public function calculatePayroll(string $employeeId): PayrollResult
{
    $employee = $this->employeeRepository->find($employeeId);
    // Could return data from any tenant!
    return $this->calculate($employee);
}

// CORRECT - Tenant context required
public function calculatePayroll(string $employeeId, string $tenantId): PayrollResult
{
    $employee = $this->employeeRepository->findByTenantAndId($tenantId, $employeeId);
    if ($employee === null) {
        throw new EmployeeNotFoundException($employeeId, $tenantId);
    }
    return $this->calculate($employee);
}

// CORRECT - Using TenantContext service
public function calculatePayroll(string $employeeId): PayrollResult
{
    $tenantId = $this->tenantContext->getRequiredTenantId();
    $employee = $this->employeeRepository->findByTenantAndId($tenantId, $employeeId);
    // ...
}
```

**Why It Matters**: Missing tenant context can lead to:
- Cross-tenant data leakage
- Unauthorized access to sensitive payroll data
- Compliance violations (GDPR, SOC2)

**Prevention**:
- Always require tenant ID in multi-tenant operations
- Use a TenantContext service to manage current tenant
- Add middleware to validate tenant context
- Test with multiple tenants to verify isolation

#### 5. Deprecated Method Inconsistency

**Problem**: Deprecated methods not passing required parameters to new implementations.

**Example from PaymentGateway**:
```php
// WRONG - Deprecated method missing required parameter
/**
 * @deprecated Use processRefund() instead
 */
public function refund(string $transactionId, Money $amount): RefundResult
{
    // Missing tenantId parameter!
    return $this->processRefund($transactionId, $amount);
}

// CORRECT - Passing all required parameters
/**
 * @deprecated Use processRefund() instead
 */
public function refund(string $transactionId, Money $amount): RefundResult
{
    // Use default or extract from context
    $tenantId = $this->tenantContext->getRequiredTenantId();
    return $this->processRefund($transactionId, $amount, $tenantId);
}

public function processRefund(string $transactionId, Money $amount, string $tenantId): RefundResult
{
    // Full implementation with tenant context
}
```

**Why It Matters**: Inconsistent deprecated methods:
- Break backward compatibility silently
- Can cause runtime errors
- May bypass security checks (like tenant validation)

**Prevention**:
- Ensure deprecated methods pass ALL required parameters
- Add deprecation tests to verify behavior consistency
- Document migration path clearly
- Set a removal timeline in deprecation notice

### Type Safety Issues

#### 6. Mutable DateTime in Value Objects

**Problem**: Using `\DateTime` instead of `\DateTimeImmutable` in value objects breaks immutability.

**Example from Payroll Value Objects**:
```php
// WRONG - Mutable DateTime
class PayPeriod
{
    public function __construct(
        public readonly \DateTime $startDate,
        public readonly \DateTime $endDate
    ) {}
}

// This allows mutation!
$period = new PayPeriod($start, $end);
$period->startDate->modify('+1 day'); // Value object is now mutated!

// CORRECT - Immutable DateTime
class PayPeriod
{
    public function __construct(
        public readonly \DateTimeImmutable $startDate,
        public readonly \DateTimeImmutable $endDate
    ) {}
}

// Now safe from mutation
$period = new PayPeriod($start, $end);
$newStart = $period->startDate->modify('+1 day'); // Returns new instance
// Original $period unchanged
```

**Why It Matters**: Mutable DateTime in value objects:
- Breaks value object semantics
- Can cause unexpected side effects
- Makes debugging difficult
- Can lead to incorrect payroll calculations

**Prevention**:
- Always use `\DateTimeImmutable` in value objects
- Use static analysis tools to catch this (PHPStan/Psalm)
- Add coding standards rules to enforce this

#### 7. Missing Type Conversion

**Problem**: Not handling string-to-DateTimeInterface conversion in filters and queries.

**Example from Payroll Filters**:
```php
// WRONG - Assuming type without conversion
public function findByDateRange(string $startDate, string $endDate): array
{
    return $this->repository->createQueryBuilder('p')
        ->where('p.payDate >= :start')
        ->andWhere('p.payDate <= :end')
        ->setParameter('start', $startDate) // String passed to DateTime column!
        ->setParameter('end', $endDate)
        ->getQuery()
        ->getResult();
}

// CORRECT - Explicit type conversion
public function findByDateRange(string $startDate, string $endDate): array
{
    $start = new \DateTimeImmutable($startDate);
    $end = new \DateTimeImmutable($endDate);
    
    return $this->repository->createQueryBuilder('p')
        ->where('p.payDate >= :start')
        ->andWhere('p.payDate <= :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getResult();
}

// BETTER - With validation
public function findByDateRange(string $startDate, string $endDate): array
{
    try {
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
    } catch (\Exception $e) {
        throw new InvalidDateRangeException("Invalid date format: {$startDate} - {$endDate}");
    }
    
    if ($start > $end) {
        throw new InvalidDateRangeException("Start date must be before end date");
    }
    
    // ... query
}
```

**Why It Matters**: Missing type conversion:
- May work with some databases but fail with others
- Can cause timezone issues
- Makes debugging difficult
- May lead to SQL injection if not careful

**Prevention**:
- Always convert string dates to DateTimeImmutable
- Validate date formats
- Handle timezone explicitly
- Add type hints in method signatures when possible

### Code Quality Issues

#### 8. Duplicate Business Logic

**Problem**: Same calculation logic duplicated across multiple services.

**Example from Payroll Services**:
```php
// WRONG - Duplicated in multiple services
class PayrollEngine
{
    private function calculateOvertimePay(float $regularRate, float $hours): float
    {
        if ($hours <= 40) {
            return 0;
        }
        $overtimeHours = $hours - 40;
        return $overtimeHours * $regularRate * 1.5;
    }
}

class PayrollReportService
{
    private function calculateOvertimePay(float $regularRate, float $hours): float
    {
        // Same logic duplicated!
        if ($hours <= 40) {
            return 0;
        }
        $overtimeHours = $hours - 40;
        return $overtimeHours * $regularRate * 1.5;
    }
}

// CORRECT - Extract to dedicated calculator
class OvertimeCalculator
{
    public function calculate(Money $regularRate, float $hours): Money
    {
        if ($hours <= 40) {
            return Money::zero($regularRate->getCurrency());
        }
        $overtimeHours = $hours - 40;
        $overtimeRate = $regularRate->multiply(1.5);
        return $overtimeRate->multiply($overtimeHours);
    }
}

class PayrollEngine
{
    public function __construct(
        private readonly OvertimeCalculator $overtimeCalculator
    ) {}
    
    private function calculateOvertimePay(Money $regularRate, float $hours): Money
    {
        return $this->overtimeCalculator->calculate($regularRate, $hours);
    }
}
```

**Why It Matters**: Duplicate business logic:
- Changes must be made in multiple places
- Risk of inconsistency when one location is updated
- Harder to test thoroughly
- Violates DRY principle

**Prevention**:
- Extract shared calculations to dedicated services
- Use composition over duplication
- Add static analysis to detect code duplication
- Review codebase for similar logic before implementing

---

## Lessons from FinanceOperations PR #235

> Identified during code review on 2026-02-22

### 1. Float Arithmetic for Monetary Values

**Problem**: Using PHP floats for monetary calculations leads to precision errors.

**Solution**: Use BCMath functions (`bcadd`, `bcsub`, `bcmul`, `bcdiv`) or the moneyphp library.

```php
// WRONG - Float arithmetic
$total = $amount * $quantity;

// CORRECT - BCMath
$total = bcmul((string)$amount, (string)$quantity, 2);
```

### 2. Type Safety with Enums

**Problem**: Using string literals instead of typed enums leads to type errors and typos.

**Solution**: Use PHP 8.1+ native enums for type safety.

```php
// WRONG - String literals
public function setType(string $type): void { ... }

// CORRECT - Native enum
public function setType(SubledgerType $type): void { ... }
```

### 3. Listener Services Must Actually Invoke Dependencies

**Problem**: Listener classes were created but the injected services were never called.

**Solution**: Always verify that injected dependencies are actually invoked in the method body.

```php
// WRONG - Empty listener
public function handle(BudgetExceededEvent $event): void
{
    $this->notificationService; // Never called!
}

// CORRECT - Actually invoke
public function handle(BudgetExceededEvent $event): void
{
    $this->notificationService->sendBudgetAlert($event->getBudgetId());
}
```

### 4. Correct Array Key Usage

**Problem**: Using incorrect array keys leads to null values and logic errors.

**Solution**: Always verify the exact key names from provider/data source.

```php
// WRONG - Wrong key
$accountId = $data['accountId']; // Should be 'bank_account_id'

// CORRECT - Correct key
$accountId = $data['bank_account_id'];
```

### 5. Always Use Validation Results

**Problem**: Rules were validated but results were ignored.

**Solution**: Always use the validation result to make decisions.

```php
// WRONG - Validation ignored
$this->ruleValidator->validate($request);
return $this->processRequest($request); // Proceeds regardless

// CORRECT - Use validation result
$result = $this->ruleValidator->validate($request);
if (!$result->isValid()) {
    throw new ValidationException($result->getErrors());
}
```

### 6. DTOs for Monetary Values Should Use String

**Problem**: Using `float` for monetary DTO properties loses precision.

**Solution**: Use `string` for monetary values in DTOs.

```php
// WRONG - Float for money
class BudgetCheckResult {
    public float $availableAmount;
}

// CORRECT - String for money
class BudgetCheckResult {
    public string $availableAmount; // Store as string, convert when needed
}
```

---

## Lessons from CodeRabbit PR #237

> Identified during code review on 2026-02-23

### 1. PII Leakage Prevention in Logging

**Problem**: Logging full user data arrays or raw sensitive data like email addresses can leak PII (Personally Identifiable Information).

**Example**:
```php
// WRONG - Logging full user data
$this->logger->info('User data', ['user' => $userData]);

// WRONG - Logging raw email
$this->logger->info('Processing user', ['email' => $user->email]);

// CORRECT - Log only keys
$this->logger->info('User data', ['keys' => array_keys($userData)]);

// CORRECT - Hash sensitive data before logging
$this->logger->info('Processing user', ['email_hash' => hash('sha256', $user->email)]);
```

**Prevention**:
- Never log full user data arrays
- Always log only keys: `['keys' => array_keys($data)]`
- Always hash sensitive data like email addresses before logging: use `hash('sha256', $email)`
- Use logging libraries that support PII redaction

### 2. PHP 8.3 Readonly Properties

**Problem**: Exception properties that don't change should be marked as `readonly` for PHP 8.3 compliance.

**Example**:
```php
// WRONG - Non-readonly exception property
class UserCreationException extends \Exception
{
    private string $context;
    
    public function __construct(string $message, string $context)
    {
        parent::__construct($message);
        $this->context = $context;
    }
}

// CORRECT - Readonly exception property
class UserCreationException extends \Exception
{
    readonly private string $context;
    
    public function __construct(string $message, string $context)
    {
        parent::__construct($message);
        $this->context = $context;
    }
}
```

**Prevention**:
- All exception properties that don't change after construction should be marked as `readonly`
- This ensures immutability and PHP 8.3 compliance
- Use `readonly private string $propertyName;` syntax

### 3. Error Message Security

**Problem**: Returning raw exception messages (`$e->getMessage()`) to callers can leak internal system details.

**Example**:
```php
// WRONG - Leaking internal error details
public function createUser(array $data): UserCreateResult
{
    try {
        // ... user creation logic
    } catch (\Exception $e) {
        return UserCreateResult::failure(
            message: 'Failed to create user: ' . $e->getMessage()
        );
    }
}

// CORRECT - Generic failure message
public function createUser(array $data): UserCreateResult
{
    try {
        // ... user creation logic
    } catch (\Exception $e) {
        // Log the actual error internally, but don't expose to caller
        $this->logger->error('User creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return UserCreateResult::failure();
    }
}
```

**Prevention**:
- Never return raw exception messages (`$e->getMessage()`) to callers
- Always use generic failure messages to prevent leaking internal system details
- Log detailed errors internally for debugging
- Return user-friendly messages that don't expose implementation details

### 4. DateTime Format Standards

**Problem**: Using `DateTime::ISO8601` produces a non-standard ISO-8601 string (missing the colon in timezone offset). Use `DateTimeInterface::ATOM` for standard ISO-8601 output.

**Example**:
```php
// WRONG - Using non-standard ISO8601 constant
$dateString = $date->format(\DateTime::ISO8601);

// CORRECT - Using ATOM constant for RFC3339/ISO-8601 compliance
$dateString = $date->format(DateTimeInterface::ATOM);
```

**Prevention**:
- Never use `DateTime::ISO8601` as it produces non-standard output
- Always use `DateTimeInterface::ATOM` for ISO-8601 formatted dates
- This ensures the timezone offset includes the colon (e.g., `+00:00` instead of `+0000`)
- Use `DateTimeInterface::ATOM` for RFC3339 compliance

### 5. Interface Definition Requirements

**Problem**: Missing interface definitions cause critical runtime errors when interfaces are referenced but not defined.

**Example**:
```php
// WRONG - Interface not defined
class UserService implements UserServiceInterface // UserServiceInterface doesn't exist!
{
    public function getUser(string $id): User
    {
        // ...
    }
}

// CORRECT - Define the interface first
interface UserServiceInterface
{
    public function getUser(string $id): User;
}

class UserService implements UserServiceInterface
{
    public function getUser(string $id): User
    {
        // ...
    }
}
```

**Prevention**:
- Always ensure all referenced interfaces are properly defined/imported before using them
- Run static analysis tools (PHPStan, Psalm) to catch missing interfaces
- Verify all `implements` statements have corresponding interface definitions
- Missing interface definitions cause critical runtime errors

---

## Testing Guidelines

### Test What Can Break

```php
// WRONG - Testing framework behavior
public function test_getter_returns_value(): void
{
    $dto = new OrderDTO(id: 1, total: 100.0);
    $this->assertEquals(1, $dto->id);
}

// CORRECT - Testing business logic
public function test_order_total_includes_tax(): void
{
    $order = new Order(items: $items, taxRate: 0.1);
    $this->assertEquals(110.0, $order->getTotal());
}
```

### Test Edge Cases

- Empty collections
- Null values
- Boundary values (0, -1, max int)
- Invalid inputs
- Concurrent operations

---

## Changelog

### 2026-02-23 - CodeRabbit PR #237 Review
- Added new section: "Lessons from CodeRabbit PR #237"
- PII Leakage Prevention: Never log full user data arrays, hash sensitive data like emails
- PHP 8.3 Readonly Properties: Mark exception properties as readonly for PHP 8.3 compliance
- Error Message Security: Never return raw exception messages to callers
- DateTime Format Standards: Use DateTimeInterface::ATOM instead of deprecated ISO8601
- Interface Definition Requirements: Ensure all referenced interfaces are properly defined

### 2026-02-22 - FinanceOperations PR #235 Review
- Added new section: "Lessons from FinanceOperations PR #235"
- Float Arithmetic for Monetary Values: Use BCMath or moneyphp library instead of PHP floats
- Type Safety with Enums: Use PHP 8.1+ native enums instead of string literals
- Listener Services Must Actually Invoke Dependencies: Verify injected services are called
- Correct Array Key Usage: Always verify exact key names from provider/data source
- Always Use Validation Results: Don't ignore validation results in decision making
- DTOs for Monetary Values Should Use String: Use string instead of float for money in DTOs

### 2026-02-19 - PaymentGateway & Payroll Refactoring Review
- Added new section: "Error Signatures from PaymentGateway & Payroll Refactoring"
- Security Issues: Incorrect webhook signature algorithm, missing certificate validation, incorrect signed string format
- Logic Issues: Missing tenant context, deprecated method inconsistency
- Type Safety Issues: Mutable DateTime in value objects, missing type conversion
- Code Quality Issues: Duplicate business logic
- Each pattern includes: problem description, incorrect code example, correct code example, and impact explanation

### 2026-02-19 - Initial Creation from PR #227
- Added rules from Sales Package refactoring review
- Categories: Critical, Database, PHP/Laravel, Documentation
- Critical rules: No stubs, atomicity, FK constraints, proper ID generation
- Database standards: FK constraints, indexes, naming conventions
- PHP/Laravel guidelines: PSR-4, type safety, float comparisons, redundant code
- Documentation standards: Code examples, markdown, anchor links
- Added code quality checklist
- Added common patterns to avoid
- Added testing guidelines

---

## How to Use This Document

1. **Before Starting Work**: Review relevant sections for the type of work you're doing
2. **During Development**: Reference the examples and patterns
3. **Before Submission**: Run through the checklist
4. **After Code Review**: Update this document with any new patterns discovered

## Contributing

When you discover a new pattern of error:

1. Add it to the appropriate section
2. Include a concrete example of the problem
3. Include the correct approach
4. Add to the checklist if applicable
5. Update the changelog with date and description

---

## Lessons from FixedAssetDepreciation Package Review (PR #233)

> Identified during code review on 2026-02-21

### 1. Service Classes Must Be Final

**Problem**: Service classes were missing the `final` keyword, allowing them to be extended when they shouldn't be.

**Example**:
```php
// WRONG - Service class can be extended
readonly class DepreciationCalculator implements DepreciationCalculatorInterface

// CORRECT - Service class is final
final readonly class DepreciationCalculator implements DepreciationCalculatorInterface
```

**Prevention**:
- All service classes in `src/Services/` must be declared as `final readonly class`
- This follows the service class guidelines and prevents unintended extension

### 2. Exception Error Codes Must Be Immutable

**Problem**: Exception classes had mutable `$errorCode` properties.

**Example**:
```php
// WRONG - Mutable error code
protected string $errorCode = 'DEPRECIATION_ERROR';

// CORRECT - Use a constant
protected const ERROR_CODE = 'DEPRECIATION_ERROR';

public function getErrorCode(): string
{
    return static::ERROR_CODE;
}
```

**Prevention**:
- Use class constants for error codes instead of mutable properties
- This ensures exception metadata remains immutable

### 3. Stub Implementations Must Not Be Silent

**Problem**: Methods like `runPeriodicDepreciation()` returned empty results without doing any work, silently failing.

**Example**:
```php
// WRONG - Silent no-op
public function runPeriodicDepreciation(string $periodId): DepreciationRunResult
{
    $this->logger->info('Running periodic depreciation');
    return new DepreciationRunResult(/* empty */);
}

// CORRECT - Either implement or throw
public function runPeriodicDepreciation(string $periodId): DepreciationRunResult
{
    throw new \RuntimeException('runPeriodicDepreciation not yet implemented');
}
```

**Prevention**:
- Never merge code with stub methods that return empty/hardcoded values without clear justification
- If a feature isn't implemented, throw `NotImplementedException` or `RuntimeException`
- Silent failures hide bugs and mislead callers

### 4. Configuration Flags Must Have Effect

**Problem**: Methods accepted configuration parameters that were never used.

**Example**:
```php
// WRONG - Parameter ignored
public function __construct(
    private bool $applyToFullCost = false,
) {}
// $this->applyToFullCost never used in calculate()

// CORRECT - Use the configuration
public function calculate(...): DepreciationAmount
{
    $basis = $this->applyToFullCost ? $cost : ($cost - $salvageValue);
    // ...
}
```

**Prevention**:
- Every constructor parameter must be used in the class
- If a parameter isn't needed, remove it rather than ignoring it
- Configuration that has no effect is misleading to users

### 5. Method Return Values Must Match Documentation

**Problem**: Methods like `supportsProrate()` returned true but the functionality wasn't implemented.

**Example**:
```php
// WRONG - Advertises feature not implemented
public function supportsProrate(): bool
{
    return true; // But calculate() doesn't prorate
}

// CORRECT - Return false until implemented
public function supportsProrate(): bool
{
    return false;
}
```

**Prevention**:
- Don't advertise functionality that doesn't exist
- Return values should match actual behavior
- Update the return value when the feature is implemented

### 6. Coverage Exclusions Hide Business Logic

**Problem**: `DepreciationSchedule.php` was excluded from coverage, hiding 16 public methods.

**Prevention**:
- Don't exclude files from coverage unless absolutely necessary
- If a file has business logic, it should be tested and included in coverage
- Remove coverage exclusions and add tests for uncovered methods

### 7. Accumulated Value Calculations Must Not Double-Count

**Problem**: `DepreciationAmount.add()` was adding the amount twice.

**Example**:
```php
// WRONG - Double counting
accumulatedDepreciation: $this->accumulatedDepreciation + $other->accumulatedDepreciation + $other->amount

// CORRECT - Sum only once
accumulatedDepreciation: $this->accumulatedDepreciation + $other->accumulatedDepreciation
```

**Prevention**:
- Be careful with arithmetic in accumulation calculations
- Test edge cases where values are added/subtracted
- Verify totals match expected values

### 8. Guard Against Division by Zero

**Problem**: Methods could divide by zero when depreciable base or useful life was zero.

**Prevention**:
- Always validate inputs before division operations
- Add guards for zero/negative values in financial calculations
- Throw clear exceptions with meaningful messages

### 9. Contract Interfaces Must Support Real Use Cases

**Problem**: `AssetDataProviderInterface.getAccumulatedDepreciation()` didn't support depreciation type or as-of date.

**Prevention**:
- Design interfaces for real-world queries
- Consider multi-tenancy, point-in-time, and type-specific lookups
- Don't simplify interfaces in ways that lose important capabilities

---

## Test Writing Guidelines (from FixedAssetDepreciation PR #234)

> Identified during code review on 2026-02-22

### 1. Namespace Consistency

**Rule**: Always use the correct namespace segment "Tests" (plural), not "Test" (singular).

```php
// WRONG
namespace Nexus\FixedAssetDepreciation\Test\Unit\Entities;

// CORRECT
namespace Nexus\FixedAssetDepreciation\Tests\Unit\Entities;
```

### 2. Test Class Modifiers

**Rule**: Mark test classes as `final` for consistency and to prevent unintended extension.

```php
// CORRECT
final class AssetRevaluationTest extends TestCase
```

### 3. Date Test Values Must Be Deterministic

**Rule**: Avoid boundary dates that can produce ambiguous inclusive/exclusive behavior. Use dates clearly inside the expected period.

```php
// WRONG - boundary date
$asOfDate = new DateTimeImmutable('2024-02-01'); // Exactly on period boundary

// CORRECT - date inside period
$asOfDate = new DateTimeImmutable('2024-01-15'); // Clearly in period 1
// or
$asOfDate = new DateTimeImmutable('2024-01-31'); // End of period 1
```

### 4. String Interpolation for Dates

**Rule**: Use proper zero-padding for date components to avoid invalid months.

```php
// WRONG - produces invalid months for periodNumber >= 10
$date = "2024-0{$periodNumber}-01"; // "2024-010-01" is invalid

// CORRECT - use sprintf or str_pad
$date = sprintf('%04d-%02d-%02d', 2024, $periodNumber, 1);
// or
$date = '2024-' . str_pad($periodNumber, 2, '0', STR_PAD_LEFT) . '-01';
```

### 5. Match Test Assertions to Implementation

**Rule**: Verify the actual implementation behavior before writing assertions. Don't assume behavior—check the source code.

```php
// WRONG - assuming supportsProrate returns true
$this->assertTrue($method->supportsProrate());

// CORRECT - check implementation and assert accordingly
$this->assertFalse($method->supportsProrate()); // Based on actual behavior
```

### 6. Use Public API Instead of Reflection

**Rule**: Test through public methods, not reflection. If you need reflection to test, the API may need improvement.

```php
// WRONG - testing private state via reflection
$reflection = new ReflectionClass($method);
$property = $reflection->getProperty('bonusRate');
$property->setAccessible(true);
$value = $property->getValue($method);

// CORRECT - use public accessors
$value = $method->getBonusRate();
```

### 7. Add Readonly to Test Properties

**Rule**: Use PHP 8.3 readonly modifier for properties that are only assigned once (in setUp or constructor).

```php
// CORRECT
private readonly DepreciationSchedule $schedule;
private readonly array $testPeriods;
```

### 8. Exact Value Assertions

**Rule**: Use exact assertions (assertEquals/assertSame) instead of loose assertions when possible.

```php
// WRONG - too loose
$this->assertGreaterThanOrEqual(3000.00, $result);

// CORRECT - exact value
$this->assertSame(3000.00, $result);
```

### 9. Match Test Method Names to Assertions

**Rule**: Keep test method names in sync with what they actually test.

```php
// WRONG
public function testGetMinimumUsefulLifeMonths_returns12(): void
{
    $this->assertEquals(1, $this->method->getMinimumUsefulLifeMonths());
}

// CORRECT
public function testGetMinimumUsefulLifeMonths_returns1(): void
{
    $this->assertEquals(1, $this->method->getMinimumUsefulLifeMonths());
}
```

### 10. Remove Unused Imports

**Rule**: Clean up any unused use statements to keep code tidy.

```php
// Remove this if not used
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;
```

### 11. Test Production Behavior, Not Assumptions

**Rule**: When testing methods that may have fallback behavior, verify the actual behavior instead of expecting exceptions.

```php
// WRONG - assuming it throws
$this->expectException(InvalidArgumentException::class);
$method->calculate($cost, $life, 0); // interest rate

// CORRECT - test actual fallback behavior
$result = $method->calculate($cost, $life, 0);
$this->assertInstanceOf(DepreciationAmount::class, $result);
```

### 12. Validate toArray Key Formats

**Rule**: Ensure toArray() methods return consistent key formats (camelCase or snake_case) and match test expectations.

```php
// If implementation uses camelCase
return [
    'periodId' => $this->periodId,
    'totalAssets' => $this->totalAssets,
];

// Test must match
$this->assertArrayHasKey('periodId', $array);
$this->assertArrayHasKey('totalAssets', $array);
```

### 13. JSON Serialization Tests

**Rule**: Test actual JSON encoding, not just that jsonSerialize() returns an array.

```php
// WRONG - only checks return type
$this->assertIsArray($adjustment->jsonSerialize());

// CORRECT - tests actual JSON encoding
$json = json_encode($adjustment);
$this->assertNotFalse($json);
$this->assertJson($json);
$decoded = json_decode($json, true);
$this->assertEquals('adj_015', $decoded['id']);
```

---
