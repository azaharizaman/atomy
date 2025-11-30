# API Reference: FeatureFlags

## Interfaces

### FeatureFlagManagerInterface

**Location:** `src/Contracts/FeatureFlagManagerInterface.php`

**Purpose:** Main service for evaluating feature flags.

#### evaluate()
```php
public function evaluate(string $flagKey, EvaluationContext $context): bool;
```
Evaluates a single flag. Returns `false` if flag not found (fail-closed).

#### evaluateBulk()
```php
public function evaluateBulk(array $flagKeys, EvaluationContext $context): array;
```
Evaluates multiple flags in one operation. Returns `array<string, bool>`.

---

### FlagRepositoryInterface

**Location:** `src/Contracts/FlagRepositoryInterface.php`

#### findByKey()
```php
public function findByKey(string $key, ?string $tenantId = null): FlagDefinition;
```
**Throws:** `FlagNotFoundException`

#### findBulk()
```php
public function findBulk(array $keys, ?string $tenantId = null): array;
```

---

### FlagEvaluatorInterface

**Location:** `src/Contracts/FlagEvaluatorInterface.php`

#### evaluate()
```php
public function evaluate(FlagDefinition $flag, EvaluationContext $context): bool;
```

---

### CustomEvaluatorInterface

**Location:** `src/Contracts/CustomEvaluatorInterface.php`

For business-specific evaluation logic.

---

## Value Objects

### FlagDefinition

**Location:** `src/ValueObjects/FlagDefinition.php`

**Properties:**
- `key` (string)
- `tenantId` (?string)
- `strategy` (FlagStrategy)
- `enabled` (bool)
- `percentage` (?int)
- `tenantIds` (array)
- `userIds` (array)
- `evaluatorName` (?string)
- `override` (FlagOverride)
- `checksum` (string)

### EvaluationContext

**Location:** `src/ValueObjects/EvaluationContext.php`

**Properties:**
- `tenantId` (?string)
- `userId` (?string)
- `attributes` (array)

---

## Enums

### FlagStrategy

**Cases:**
- `SystemWide` - Boolean ON/OFF
- `PercentageRollout` - 0-100% gradual rollout
- `TenantList` - Specific tenants only
- `UserList` - Specific users only
- `CustomEvaluator` - Custom logic

### FlagOverride

**Cases:**
- `None` - Normal evaluation
- `ForceOn` - Always enabled
- `ForceOff` - Always disabled (kill switch)

---

## Exceptions

### FlagNotFoundException
```php
public static function forKey(string $key): self;
```

### StaleCacheException
```php
public static function forFlag(string $key, string $expected, string $actual): self;
```

### InvalidStrategyException
```php
public static function unsupported(string $strategy): self;
```

---

**Last Updated:** November 24, 2025
