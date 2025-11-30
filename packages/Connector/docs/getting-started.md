# Getting Started with Nexus Connector

## Prerequisites

- PHP 8.3 or higher
- Composer
- A supported framework (Laravel 11+, Symfony 7+, or any PHP framework with PSR-11 container)

## Installation

```bash
composer require nexus/connector:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Integrating with external APIs (email, SMS, payment, storage, shipping services)
- ✅ Ensuring resilient external communication with circuit breakers and retries
- ✅ Vendor abstraction (swap Mailchimp for SendGrid with zero code changes)
- ✅ Comprehensive audit logging of all external API calls
- ✅ Multi-tenant credential isolation

Do NOT use this package for:
- ❌ Internal service-to-service communication within your application
- ❌ Database queries or ORM operations
- ❌ Direct HTTP client usage without resilience patterns

## Core Concepts

### Concept 1: Domain-Specific Interfaces

The Connector package provides **domain-specific interfaces** rather than generic HTTP client interfaces. This makes your code's intent clear and provides type safety.

**Available Interfaces:**
- `EmailServiceConnectorInterface` - Email operations (transactional, bulk, validation)
- `SmsServiceConnectorInterface` - SMS operations (send, bulk, validate phone)
- `PaymentGatewayConnectorInterface` - Payment processing (charge, refund, authorization)
- `CloudStorageConnectorInterface` - File storage (upload, download, signed URLs)
- `ShippingProviderConnectorInterface` - Shipping operations (create, track, rates)

### Concept 2: Plugin/Adapter Pattern

Your application implements **vendor-specific adapters** that conform to domain interfaces. This allows zero-code vendor swapping via configuration.

```
┌─────────────────────────────────────────┐
│  Your Application Code                  │
│  (Uses EmailServiceConnectorInterface)  │
└──────────────┬──────────────────────────┘
               │
               │ Dependency Injection
               ▼
┌─────────────────────────────────────────┐
│  Adapter (Configured via .env)          │
│  • MailchimpEmailAdapter                │
│  • SendGridEmailAdapter  ← swappable    │
│  • PostmarkEmailAdapter                 │
└─────────────────────────────────────────┘
```

### Concept 3: Resilience Patterns

All external calls automatically benefit from:
- **Circuit Breaker**: Stops calls to failing services (opens after 5 failures, closes after 60s)
- **Retry Logic**: Exponential backoff (max 3 attempts by default)
- **Rate Limiting**: Respects vendor quotas (token bucket algorithm)
- **Timeout Enforcement**: Prevents indefinite blocking

### Concept 4: Stateless Architecture

All state (circuit breaker, rate limiter, idempotency) is delegated to **storage interfaces** you implement. This ensures horizontal scalability across PHP-FPM workers and Laravel Octane.

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires you to implement storage interfaces for state management:

```php
// app/Repositories/RedisCircuitBreakerStorage.php
namespace App\Repositories;

use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Connector\ValueObjects\CircuitBreakerState;

final readonly class RedisCircuitBreakerStorage implements CircuitBreakerStorageInterface
{
    public function __construct(
        private \Illuminate\Redis\RedisManager $redis
    ) {}
    
    public function getState(string $serviceName): CircuitBreakerState
    {
        $data = $this->redis->get("circuit:{$serviceName}");
        
        if (!$data) {
            return CircuitBreakerState::closed();
        }
        
        return CircuitBreakerState::fromArray(json_decode($data, true));
    }
    
    public function saveState(string $serviceName, CircuitBreakerState $state): void
    {
        $this->redis->setex(
            "circuit:{$serviceName}",
            3600,
            json_encode($state->toArray())
        );
    }
}
```

### Step 2: Implement Vendor Adapter

Create an adapter for your chosen vendor:

```php
// app/Connectors/Adapters/SendGridEmailAdapter.php
namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use SendGrid\Mail\Mail;

final readonly class SendGridEmailAdapter implements EmailServiceConnectorInterface
{
    public function __construct(
        private string $apiKey,
        private string $fromEmail,
        private string $fromName
    ) {}
    
    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        $email = new Mail();
        $email->setFrom($this->fromEmail, $this->fromName);
        $email->addTo($recipient);
        $email->setSubject($subject);
        $email->addContent('text/html', $body);
        
        $sendgrid = new \SendGrid($this->apiKey);
        $response = $sendgrid->send($email);
        
        return $response->statusCode() === 202;
    }
    
    // ... implement other interface methods
}
```

### Step 3: Bind Interfaces in Service Provider

```php
// app/Providers/ConnectorServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Connector\Contracts\RateLimiterStorageInterface;
use App\Connectors\Adapters\SendGridEmailAdapter;
use App\Repositories\RedisCircuitBreakerStorage;
use App\Repositories\RedisRateLimiterStorage;

class ConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind storage interfaces
        $this->app->singleton(
            CircuitBreakerStorageInterface::class,
            RedisCircuitBreakerStorage::class
        );
        
        $this->app->singleton(
            RateLimiterStorageInterface::class,
            RedisRateLimiterStorage::class
        );
        
        // Bind email adapter based on config
        $this->app->singleton(
            EmailServiceConnectorInterface::class,
            fn() => match(config('connector.email_vendor')) {
                'sendgrid' => new SendGridEmailAdapter(
                    apiKey: config('services.sendgrid.api_key'),
                    fromEmail: config('mail.from.address'),
                    fromName: config('mail.from.name')
                ),
                default => throw new \InvalidArgumentException('Unsupported email vendor')
            }
        );
    }
}
```

### Step 4: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\ConnectorServiceProvider::class,
],
```

### Step 5: Configure Environment

Add to `.env`:

```env
# Email Service
CONNECTOR_EMAIL_VENDOR=sendgrid
SENDGRID_API_KEY=SG.your_api_key_here
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App"
```

---

## Your First Integration

### Sending an Email

```php
<?php

namespace App\Services;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

final readonly class WelcomeEmailService
{
    public function __construct(
        private EmailServiceConnectorInterface $emailConnector
    ) {}
    
    public function send(string $userEmail, string $userName): void
    {
        $this->emailConnector->sendTransactionalEmail(
            recipient: $userEmail,
            subject: 'Welcome to Our Platform!',
            body: "<h1>Welcome {$userName}!</h1><p>Thanks for joining us.</p>"
        );
    }
}
```

### Handling Errors

The Connector automatically handles transient failures with retries and circuit breakers. You only need to catch permanent failures:

```php
use Nexus\Connector\Exceptions\CircuitBreakerOpenException;
use Nexus\Connector\Exceptions\ConnectionException;

try {
    $emailService->send($user->email, $user->name);
} catch (CircuitBreakerOpenException $e) {
    // Service is down - queue for later
    dispatch(new SendWelcomeEmailJob($user))->delay(now()->addMinutes(5));
} catch (ConnectionException $e) {
    // All retries failed - log for manual review
    Log::error('Welcome email failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage()
    ]);
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples
- Review [CONNECTOR_IMPLEMENTATION.md](../../docs/CONNECTOR_IMPLEMENTATION.md) for comprehensive implementation guide

## Troubleshooting

### Common Issues

**Issue 1: "Interface not bound" error**
- **Cause:** Service provider not registered or interface not bound
- **Solution:** Ensure `ConnectorServiceProvider` is registered in `config/app.php` and interfaces are bound correctly

**Issue 2: Circuit breaker always open**
- **Cause:** External service is down or credentials are invalid
- **Solution:** Check integration logs, verify credentials, wait for circuit to half-open (60s default)

**Issue 3: Rate limit exceeded**
- **Cause:** Too many requests sent to vendor API
- **Solution:** Configure `RateLimitConfig` per endpoint to match vendor quotas

**Issue 4: OAuth token expired**
- **Cause:** Access token not automatically refreshed
- **Solution:** Implement `OAuthTokenRefresher` service or use credential provider with auto-refresh
