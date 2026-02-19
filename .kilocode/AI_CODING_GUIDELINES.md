# AI Coding Agent Guidelines

> This is a living document that evolves with each discovered mistake. Last updated: 2026-02-19

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

### 2. Database Operations Must Be Atomic

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

### 3. Foreign Key Constraints Are Mandatory

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

### 4. Proper ID Generation

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

```
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
