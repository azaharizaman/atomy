# Nexus\Connector

A framework-agnostic PHP package providing a standardized, central gateway for external API communication with built-in resilience patterns.

## Purpose

`Nexus\Connector` solves the problem of **external communication complexity** by providing:

- **Vendor Abstraction**: Swap external providers (Mailchimp → SendGrid) without changing business logic
- **Resilience**: Built-in retry logic, circuit breaker, and rate limiting
- **Security**: Centralized credential management and request/response sanitization
- **Observability**: Comprehensive integration logging and metrics
- **Maintainability**: Clean separation between domain contracts and vendor implementations

## Installation

```bash
composer require nexus/connector
```

## Architecture

### The Plugin/Adapter Pattern

The package defines **domain-specific interfaces** (Email, SMS, Payment, etc.) that vendors adapt to. This enables zero-code vendor swapping.

```
┌─────────────────────────────────────────────────────┐
│         Nexus\Connector (Atomic Package)            │
│                                                      │
│  ┌────────────────────────────────────────────┐   │
│  │  Domain Interfaces (Contracts)             │   │
│  │  - EmailServiceConnectorInterface          │   │
│  │  - SmsServiceConnectorInterface            │   │
│  │  - PaymentGatewayConnectorInterface        │   │
│  └────────────────────────────────────────────┘   │
│                                                      │
│  ┌────────────────────────────────────────────┐   │
│  │  Core Services                              │   │
│  │  - ConnectorManager                         │   │
│  │  - RetryHandler                             │   │
│  │  - CircuitBreakerService                    │   │
│  │  - RateLimiterService                       │   │
│  └────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
                        ▲
                        │ implements
                        │
┌─────────────────────────────────────────────────────┐
│          Nexus\Atomy (Application Layer)            │
│                                                      │
│  ┌────────────────────────────────────────────┐   │
│  │  Vendor Adapters                            │   │
│  │  - MailchimpEmailAdapter                    │   │
│  │  - SendGridEmailAdapter                     │   │
│  │  - TwilioSmsAdapter                         │   │
│  └────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

## Core Concepts

### 1. Domain Interfaces

Each service type has a dedicated interface:

- `EmailServiceConnectorInterface`: Transactional & bulk email
- `SmsServiceConnectorInterface`: SMS messaging
- `PaymentGatewayConnectorInterface`: Payment processing
- `CloudStorageConnectorInterface`: File storage
- `ShippingProviderConnectorInterface`: Shipment tracking
- And 5 more...

### 2. Resilience Features

- **Retry Handler**: Exponential backoff with configurable attempts
- **Circuit Breaker**: Prevents cascading failures (opens after N failures)
- **Rate Limiter**: Token bucket algorithm per service/endpoint
- **Timeout Management**: Configurable request timeouts per endpoint
- **Idempotency Keys**: Prevents duplicate operations (e.g., duplicate payments)
- **OAuth Token Refresh**: Automatic access token renewal

### 3. Supporting Contracts

- `CredentialProviderInterface`: Secure credential retrieval with OAuth refresh
- `IntegrationLoggerInterface`: Audit trail for all external calls
- `WebhookVerifierInterface`: Signature validation
- `HttpClientInterface`: HTTP client abstraction for timeout enforcement
- `IdempotencyStoreInterface`: Response caching for duplicate prevention

## Usage

### Basic Example

```php
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

class NotificationService
{
    public function __construct(
        private readonly EmailServiceConnectorInterface $emailConnector
    ) {}

    public function sendWelcomeEmail(string $recipient): void
    {
        $this->emailConnector->sendTransactionalEmail(
            recipient: $recipient,
            subject: 'Welcome to Nexus',
            body: 'Thank you for joining us!'
        );
    }
}
```

### Advanced Usage with Resilience Patterns

```php
use Nexus\Connector\Services\ConnectorManager;
use Nexus\Connector\ValueObjects\{Endpoint, HttpMethod, RateLimitConfig, IdempotencyKey};

class PaymentService
{
    public function __construct(
        private readonly ConnectorManager $connector
    ) {}

    public function processPayment(float $amount, string $currency, array $paymentData): array
    {
        // Create endpoint with rate limiting and custom timeout
        $endpoint = Endpoint::create('https://api.stripe.com/v1/charges', HttpMethod::POST)
            ->withRateLimit(RateLimitConfig::perSecond(100))
            ->withTimeout(45);

        // Generate idempotency key to prevent duplicate charges
        $idempotencyKey = IdempotencyKey::generate('payment');

        // Execute with automatic retry, circuit breaker, and OAuth refresh
        return $this->connector->execute(
            serviceName: 'stripe',
            endpoint: $endpoint,
            payload: [
                'amount' => (int)($amount * 100),
                'currency' => $currency,
                'source' => $paymentData['token'],
            ],
            idempotencyKey: $idempotencyKey
        );
    }
}
```

### Vendor Swapping (Application Layer)

```php
// config/connector.php
return [
    'email_vendor' => env('EMAIL_VENDOR', 'mailchimp'), // or 'sendgrid'
];

// app/Providers/ConnectorServiceProvider.php
$vendor = config('connector.email_vendor');

$adapter = match($vendor) {
    'mailchimp' => MailchimpEmailAdapter::class,
    'sendgrid' => SendGridEmailAdapter::class,
    default => throw new \Exception("Unsupported email vendor: {$vendor}"),
};

$this->app->singleton(EmailServiceConnectorInterface::class, $adapter);
```

## Creating a Vendor Adapter

1. Implement the domain interface
2. Use vendor SDK internally
3. Map domain methods to vendor API calls

```php
namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Mailchimp\Marketing\ApiClient;

class MailchimpEmailAdapter implements EmailServiceConnectorInterface
{
    public function __construct(
        private readonly ApiClient $client,
        private readonly string $listId
    ) {}

    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body
    ): bool {
        $response = $this->client->messages->send([
            'message' => [
                'to' => [['email' => $recipient]],
                'subject' => $subject,
                'html' => $body,
            ],
        ]);

        return $response[0]['status'] === 'sent';
    }

    // Implement other interface methods...
}
```

## Requirements

- PHP 8.3 or higher
- No framework dependencies (pure PHP)

## Documentation

### 📘 Package Documentation

- **[Getting Started Guide](docs/getting-started.md)** - Quick start, core concepts, and basic configuration
- **[API Reference](docs/api-reference.md)** - Complete interface, value object, enum, and exception documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples with database migrations
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - Email, SMS, payment, and bulk email examples
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - Custom endpoints, multi-tenant, webhooks, OAuth, cloud storage, metrics

### 📊 Implementation & Planning

- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Implementation status, metrics, and key design decisions
- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Detailed requirements tracking (110 requirements across 10 categories)
- **[TEST_SUITE_SUMMARY.md](TEST_SUITE_SUMMARY.md)** - Testing philosophy and application-layer integration test examples
- **[VALUATION_MATRIX.md](VALUATION_MATRIX.md)** - Package valuation metrics and ROI analysis

### 🔗 Related Documentation

- **[Root Implementation Docs](../../docs/CONNECTOR_IMPLEMENTATION.md)** - Original comprehensive implementation document
- **[Resiliency & OAuth](../../docs/CONNECTOR_RESILIENCY_OAUTH_IMPLEMENTATION.md)** - Circuit breaker, retry logic, and OAuth implementation details

## Integration

Works seamlessly with other Nexus packages:

- **Nexus\AuditLogger**: Automatic audit trail for all external calls
- **Nexus\Setting**: Retrieve endpoint configurations and credentials
- **Nexus\Tenant**: Multi-tenant credential isolation
- **Nexus\Workflow**: Orchestrate multi-step integrations

## License

MIT License. See LICENSE file for details.
