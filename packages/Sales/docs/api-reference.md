# API Reference: Sales

## Interfaces

### QuotationManagerInterface

**Location:** `src/Contracts/QuotationManagerInterface.php`

**Purpose:** Manages the lifecycle of sales quotations.

**Methods:**

#### create()

```php
public function create(array $data): QuotationInterface;
```

**Description:** Creates a new quotation.

**Parameters:**
- `$data` (array) - Quotation data (customer_id, items, etc.)

**Returns:** `QuotationInterface` - Created quotation

#### convertToOrder()

```php
public function convertToOrder(string $quotationId): SalesOrderInterface;
```

**Description:** Converts an accepted quotation to a sales order.

---

### SalesOrderManagerInterface

**Location:** `src/Contracts/SalesOrderManagerInterface.php`

**Purpose:** Manages sales orders.

**Methods:**

#### create()

```php
public function create(array $data): SalesOrderInterface;
```

**Description:** Creates a new sales order directly.

---

### PricingEngineInterface

**Location:** `src/Contracts/PricingEngineInterface.php`

**Purpose:** Calculates prices.

**Methods:**

#### calculatePrice()

```php
public function calculatePrice(string $productId, string $customerId, float $quantity): Money;
```

**Description:** Calculates the unit price for a product/customer combination.

---

## Services

### QuotationManager

**Location:** `src/Services/QuotationManager.php`

**Purpose:** Implementation of QuotationManagerInterface.

**Constructor Dependencies:**
- `QuotationRepositoryInterface`
- `PricingEngineInterface`
- `SequencingManagerInterface`

---

### SalesOrderManager

**Location:** `src/Services/SalesOrderManager.php`

**Purpose:** Implementation of SalesOrderManagerInterface.

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `StockReservationInterface`
- `CreditLimitCheckerInterface`

---

## Value Objects

### DiscountRule

**Location:** `src/ValueObjects/DiscountRule.php`

**Purpose:** Represents a discount rule (percentage or fixed amount).

**Properties:**
- `$type` (DiscountType)
- `$value` (float)

---

## Enums

### QuoteStatus

**Location:** `src/Enums/QuoteStatus.php`

**Cases:**
- `DRAFT`
- `SENT`
- `ACCEPTED`
- `REJECTED`
- `EXPIRED`
- `CONVERTED`

### SalesOrderStatus

**Location:** `src/Enums/SalesOrderStatus.php`

**Cases:**
- `DRAFT`
- `CONFIRMED`
- `PROCESSING`
- `SHIPPED`
- `DELIVERED`
- `CANCELLED`
