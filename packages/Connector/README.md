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
- **Rate Limiter**: Token bucket algorithm per endpoint
- **Timeout Management**: Prevents indefinite blocking

### 3. Supporting Contracts

- `CredentialProviderInterface`: Secure credential retrieval
- `IntegrationLoggerInterface`: Audit trail for all external calls
- `WebhookVerifierInterface`: Signature validation

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

## Integration

Works seamlessly with other Nexus packages:

- **Nexus\AuditLogger**: Automatic audit trail for all external calls
- **Nexus\Setting**: Retrieve endpoint configurations and credentials
- **Nexus\Tenant**: Multi-tenant credential isolation
- **Nexus\Workflow**: Orchestrate multi-step integrations

## License

MIT License. See LICENSE file for details.
