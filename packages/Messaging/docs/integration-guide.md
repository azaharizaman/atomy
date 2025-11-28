# Integration Guide: Messaging

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/messaging:"*@dev"
```

### Step 2: Create Repository Implementation

Implement the required repository interfaces using Eloquent.

### Step 3: Create Service Provider

Bind all interfaces to their concrete implementations.

### Step 4: Use in Controller

Inject the manager interface and use it in your controllers.

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/messaging:"*@dev"
```

### Step 2: Configure Services

Add service bindings in `config/services.yaml`.

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes.

### Pattern 2: Multi-Tenancy

All repositories should automatically scope by tenant.
