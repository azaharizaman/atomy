# Runtime Settings Management

**Version:** 1.0  
**Last Updated:** December 9, 2025

---

## Overview

The `Nexus\Setting` package provides a **multi-tenancy-aware runtime settings management system**. Settings are resolved using a **hierarchical key resolution strategy** that automatically considers tenant context, enabling tenant-specific overrides while maintaining sensible global defaults.

---

## Key Resolution Hierarchy

Settings are resolved in the following order of precedence (highest to lowest):

```
1. Tenant-Specific Override (highest priority)
   └── Scoped to current tenant via TenantContextInterface

2. Tenant Group/Parent Override (if hierarchical tenancy enabled)
   └── Inherited from parent tenant

3. Global Default (lowest priority)
   └── System-wide default value
```

### Resolution Flow

```
┌─────────────────────────────────────┐
│        SettingsManager              │
│  ┌───────────────────────────────┐  │
│  │ 1. Get current tenant ID from │  │
│  │    TenantContextInterface     │  │
│  └──────────────┬────────────────┘  │
│                 ▼                   │
│  ┌───────────────────────────────┐  │
│  │ 2. Try tenant-scoped setting  │  │
│  │    (tenant_id + key)          │  │
│  └──────────────┬────────────────┘  │
│                 ▼                   │
│  ┌───────────────────────────────┐  │
│  │ 3. Fallback to global setting │  │
│  │    (key only)                 │  │
│  └──────────────┬────────────────┘  │
│                 ▼                   │
│  ┌───────────────────────────────┐  │
│  │ 4. Return default if not found│  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

---

## Key Design Pattern

### Dot-Notation Namespace Convention

Settings keys follow a **dot-notation namespace pattern**:

```
[domain].[category].[setting_name]
```

**Examples:**

| Key | Description |
|-----|-------------|
| `receivable.invoice.default_terms` | Default payment terms for invoices |
| `receivable.invoice.auto_number_prefix` | Invoice number prefix |
| `system.locale.timezone` | System timezone |
| `system.locale.date_format` | Date display format |
| `notification.email.from_address` | Default sender email |
| `security.password.min_length` | Minimum password length |

### Storage Key Patterns

The persistence layer stores settings with composite keys:

| Scenario | Storage Pattern | Example |
|----------|-----------------|---------|
| Global setting | `key` only | `receivable.invoice.default_terms` |
| Tenant setting | `tenant_id` + `key` | Stored with tenant association |
| Encrypted setting | Same key + encryption flag | Metadata indicates encryption |

---

## Core Interfaces

### SettingsManagerInterface

The primary interface for runtime settings operations:

```
SettingsManagerInterface
├── get(key, default): mixed
│   └── Auto-resolves tenant context, falls back to global
│
├── getString(key, default): string
├── getInt(key, default): int
├── getBool(key, default): bool
├── getArray(key, default): array
│   └── Type-safe getters with automatic casting
│
├── set(key, value): void
│   └── Writes to current tenant scope
│
├── getGlobal(key, default): mixed
│   └── Explicitly reads global scope (ignores tenant)
│
├── setGlobal(key, value): void
│   └── Writes to global scope (requires elevated permission)
│
├── getTenantSetting(tenantId, key, default): mixed
│   └── Reads specific tenant's setting
│
└── setTenantSetting(tenantId, key, value): void
    └── Writes to specific tenant's scope
```

### SettingRepositoryInterface

Persistence layer contract:

```
SettingRepositoryInterface
├── findByKey(key, tenantId?): ?SettingInterface
├── findByPrefix(prefix, tenantId?): array
├── save(setting): void
└── delete(key, tenantId?): void
```

---

## Usage Patterns

### Reading Settings with Automatic Tenant Resolution

```php
// Automatically checks tenant override, then falls back to global
$paymentTerms = $settings->get('receivable.invoice.payment_terms', 'Net 30');

// Type-safe getters
$maxRetries = $settings->getInt('api.max_retries', 3);
$debugMode = $settings->getBool('system.debug_mode', false);
$allowedTypes = $settings->getArray('upload.allowed_types', ['pdf', 'jpg']);
```

### Reading Global Settings (Ignoring Tenant Override)

```php
// Always reads system-wide value regardless of tenant context
$maintenanceMode = $settings->getGlobal('system.maintenance_mode', false);
```

### Writing Tenant-Specific Settings

```php
// Writes to current tenant's scope (determined by TenantContextInterface)
$settings->set('receivable.invoice.payment_terms', 'Net 45');
```

### Writing Global Defaults (Requires Elevated Permission)

```php
// Writes to global scope - affects all tenants without overrides
$settings->setGlobal('receivable.invoice.payment_terms', 'Net 30');
```

### Cross-Tenant Operations (Admin Use)

```php
// Read another tenant's setting
$otherTenantTerms = $settings->getTenantSetting(
    'tenant-xyz', 
    'receivable.invoice.payment_terms', 
    'Net 30'
);

// Write to another tenant's setting
$settings->setTenantSetting(
    'tenant-xyz', 
    'receivable.invoice.payment_terms', 
    'Net 60'
);
```

---

## Multi-Tenancy Guarantees

### Read Isolation

A tenant can only read:
- Their own tenant-specific overrides
- Global defaults (as fallback)

A tenant **cannot** read another tenant's settings through normal operations.

### Write Isolation

- `set()` writes to the current tenant context only
- `setGlobal()` writes to global scope (requires elevated permissions)
- `setTenantSetting()` writes to a specific tenant (admin operation)

### No Cross-Tenant Leakage

Repository implementations **must** enforce tenant boundaries:
- All queries scoped by tenant ID when tenant context is present
- Global queries explicitly exclude tenant-scoped settings

### Audit Trail Integration

Changes to settings should be logged via `AuditLogManagerInterface`:

```php
// Implementation should log setting changes
$auditLogger->log(
    entityId: $settingKey,
    action: 'setting_changed',
    description: 'Setting updated',
    metadata: [
        'key' => $key,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'scope' => 'tenant' // or 'global'
    ]
);
```

---

## Resolution Logic (Pseudocode)

```
FUNCTION get(key, default):
    tenantId = tenantContext.getCurrentTenantId()
    
    IF tenantId IS NOT NULL:
        // Try tenant-specific first
        tenantSetting = repository.findByKey(key, tenantId)
        IF tenantSetting EXISTS:
            RETURN decryptIfNeeded(tenantSetting.value)
    
    // Fallback to global
    globalSetting = repository.findByKey(key, tenantId: null)
    IF globalSetting EXISTS:
        RETURN decryptIfNeeded(globalSetting.value)
    
    RETURN default
```

---

## Encrypted Settings

Sensitive settings (API keys, secrets) support encryption:

```php
// Setting with encryption flag
$setting->setEncrypted(true);

// Manager automatically:
// 1. Encrypts before storage
// 2. Decrypts on retrieval
// Uses Nexus\Crypto\Contracts\EncryptionManagerInterface
```

---

## Setting Categories

### Recommended Key Namespaces

| Namespace | Purpose | Examples |
|-----------|---------|----------|
| `system.*` | Core system configuration | `system.timezone`, `system.locale` |
| `security.*` | Security settings | `security.password.min_length` |
| `notification.*` | Notification preferences | `notification.email.from_address` |
| `receivable.*` | Accounts receivable | `receivable.invoice.auto_number_prefix` |
| `payable.*` | Accounts payable | `payable.payment.default_method` |
| `inventory.*` | Inventory management | `inventory.valuation.method` |
| `hrm.*` | Human resources | `hrm.leave.approval_required` |
| `api.*` | API configuration | `api.rate_limit.requests_per_minute` |

---

## Best Practices

### DO

- Use consistent dot-notation naming
- Provide sensible defaults in code
- Use type-safe getters (`getInt`, `getBool`, etc.)
- Log setting changes for audit compliance
- Use encrypted storage for sensitive values

### DON'T

- Store large data structures in settings (use dedicated storage)
- Use settings for frequently changing data (use cache instead)
- Bypass tenant context for tenant-specific settings
- Store passwords or secrets without encryption flag

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\Tenant` | Provides `TenantContextInterface` for tenant resolution |
| `Nexus\Crypto` | Provides encryption for sensitive settings |
| `Nexus\AuditLogger` | Logs setting changes |
| `Nexus\Identity` | Enforces permissions for global settings |

---

## Example: Complete Service Implementation

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Service using settings with tenant awareness
 */
final readonly class InvoiceDefaultsService
{
    public function __construct(
        private SettingsManagerInterface $settings
    ) {}

    public function getDefaultPaymentTerms(): string
    {
        // Automatically resolves tenant override or global default
        return $this->settings->getString(
            'receivable.invoice.payment_terms',
            'Net 30'  // Fallback default
        );
    }

    public function getInvoicePrefix(): string
    {
        return $this->settings->getString(
            'receivable.invoice.number_prefix',
            'INV-'
        );
    }

    public function isAutoApprovalEnabled(): bool
    {
        return $this->settings->getBool(
            'receivable.invoice.auto_approval',
            false
        );
    }
}
```

---

## Migration Notes

When migrating from application-specific configuration to `Nexus\Setting`:

1. Identify settings that need tenant-specific overrides
2. Define global defaults in seeder/migration
3. Update code to use `SettingsManagerInterface`
4. Provide admin UI for tenant-specific overrides

---

**Related Documentation:**
- [Getting Started Guide](./getting-started.md)
- [API Reference](./api-reference.md)
- [Integration Guide](./integration-guide.md)
- [ARCHITECTURE.md](../../../ARCHITECTURE.md)
