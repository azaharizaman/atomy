# Getting Started with Nexus Messaging

## Prerequisites

- PHP 8.3 or higher
- Composer
- A Nexus-compatible application (Laravel, Symfony, etc.)

## Installation

```bash
composer require nexus/messaging:"*@dev"
```

## Basic Configuration

### Step 1: Implement Required Interfaces

Check the [API Reference](api-reference.md) for the list of interfaces that need to be implemented.

### Step 2: Bind Interfaces in Service Provider

```php
// Laravel example
$this->app->singleton(
    RepositoryInterface::class,
    EloquentRepository::class
);
```

### Step 3: Use the Package

Inject the manager interface into your service classes.

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples  
- See [Examples](examples/) for code samples
