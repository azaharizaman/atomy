# Nexus\Connector Implementation Documentation

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Package Structure](#package-structure)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Usage Examples](#usage-examples)
7. [Creating Vendor Adapters](#creating-vendor-adapters)
8. [API Reference](#api-reference)
9. [Database Schema](#database-schema)
10. [Monitoring and Observability](#monitoring-and-observability)
11. [Best Practices](#best-practices)

---

## Overview

`Nexus\Connector` is a framework-agnostic atomic package that provides a **standardized gateway** for all external API communication in the Nexus ERP system. It implements enterprise-grade resilience patterns including:

- **Circuit Breaker**: Prevents cascading failures
- **Retry Logic**: Exponential backoff for transient failures  
- **Rate Limiting**: Respects vendor API quotas
- **Integration Logging**: Complete audit trail
- **Credential Management**: Secure, centralized credential storage
- **Plugin/Adapter Pattern**: Zero-code vendor swapping

### Key Benefits

| Benefit | Description |
|---------|-------------|
| **Vendor Agnostic** | Swap Mailchimp for SendGrid with one config change |
| **Resilience** | Built-in retry, circuit breaker, and timeout handling |
| **Observability** | Comprehensive logging and metrics for all external calls |
| **Security** | Encrypted credential storage with automatic masking |
| **Maintainability** | Clean separation between domain logic and vendor implementation |

---

## Architecture

### The Atomic Design Pattern

The Connector package follows the **"Logic in Packages, Implementation in Applications"** philosophy:

```
┌──────────────────────────────────────────────────────────────┐
│              packages/Connector (Atomic Package)             │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Contracts (Interfaces)                            │    │
│  │  • EmailServiceConnectorInterface                  │    │
│  │  • SmsServiceConnectorInterface                    │    │
│  │  • PaymentGatewayConnectorInterface                │    │
│  │  • CredentialProviderInterface                     │    │
│  │  • IntegrationLoggerInterface                      │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Value Objects                                      │    │
│  │  • Credentials, Endpoint, RetryPolicy              │    │
│  │  • IntegrationLog, CircuitBreakerState             │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Services (Pure PHP Logic)                          │    │
│  │  • ConnectorManager                                 │    │
│  │  • RetryHandler                                     │    │
│  │  • WebhookVerifier                                  │    │
│  └────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
                           ▲
                           │ implements
                           │
┌──────────────────────────────────────────────────────────────┐
│              apps/Atomy (Application Layer)                  │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Vendor Adapters (Plugins)                          │    │
│  │  • MailchimpEmailAdapter                            │    │
│  │  • SendGridEmailAdapter                             │    │
│  │  • TwilioSmsAdapter                                 │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Implementations                                     │    │
│  │  • LaravelCredentialProvider                        │    │
│  │  • DbIntegrationLogger                              │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Database Layer                                      │    │
│  │  • integration_logs table                           │    │
│  │  • connector_credentials table                      │    │
│  │  • IntegrationLog model                             │    │
│  │  • ConnectorCredential model                        │    │
│  └────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

### Request Flow

```
┌──────────────┐
│ Application  │
│   Service    │
└──────┬───────┘
       │
       │ 1. Call domain interface method
       ▼
┌──────────────────────────┐
│ EmailServiceConnector    │ (Bound to vendor adapter)
│ Interface                │
└──────┬───────────────────┘
       │
       │ 2. Delegate to ConnectorManager
       ▼
┌──────────────────────────┐
│  ConnectorManager        │
│  • Check circuit breaker │
│  • Get credentials       │
│  • Execute with retry    │
│  • Log integration       │
└──────┬───────────────────┘
       │
       │ 3. Actual API call
       ▼
┌──────────────────────────┐
│  Vendor SDK/HTTP Client  │
│  (Mailchimp, Twilio...)  │
└──────────────────────────┘
```

---

## Package Structure

```
packages/Connector/
├── composer.json          # Package definition (pure PHP, no Laravel deps)
├── LICENSE                # MIT License
├── README.md              # Package documentation
└── src/
    ├── Contracts/         # Domain interfaces
    │   ├── EmailServiceConnectorInterface.php
    │   ├── SmsServiceConnectorInterface.php
    │   ├── PaymentGatewayConnectorInterface.php
    │   ├── CloudStorageConnectorInterface.php
    │   ├── ShippingProviderConnectorInterface.php
    │   ├── CredentialProviderInterface.php
    │   ├── IntegrationLoggerInterface.php
    │   └── WebhookVerifierInterface.php
    ├── ValueObjects/      # Immutable data structures
    │   ├── AuthMethod.php (enum)
    │   ├── Credentials.php
    │   ├── Endpoint.php
    │   ├── HttpMethod.php (enum)
    │   ├── RetryPolicy.php
    │   ├── IntegrationStatus.php (enum)
    │   ├── IntegrationLog.php
    │   ├── CircuitState.php (enum)
    │   ├── CircuitBreakerState.php
    │   └── RateLimitConfig.php
    ├── Exceptions/        # Domain-specific exceptions
    │   ├── ConnectorException.php (base)
    │   ├── ConnectionException.php
    │   ├── RateLimitException.php
    │   ├── AuthenticationException.php
    │   ├── CircuitBreakerOpenException.php
    │   ├── CredentialNotFoundException.php
    │   ├── CredentialRefreshException.php
    │   ├── PaymentDeclinedException.php
    │   └── FileNotFoundException.php
    └── Services/          # Pure business logic
        ├── ConnectorManager.php
        ├── RetryHandler.php
        └── WebhookVerifier.php
```

---

## Installation

### Step 1: Install Package

The package is already installed in the monorepo. For external use:

```bash
composer require nexus/connector
```

### Step 2: Run Migrations

```bash
php artisan migrate
```

This creates two tables:
- `integration_logs`: Stores all external API call history
- `connector_credentials`: Stores encrypted service credentials

### Step 3: Publish Configuration

```bash
php artisan vendor:publish --tag=connector-config
```

### Step 4: Configure Environment

Add to `.env`:

```env
# Email Service
CONNECTOR_EMAIL_VENDOR=mailchimp
MAILCHIMP_API_KEY=your_api_key_here
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App Name"

# SMS Service
CONNECTOR_SMS_VENDOR=twilio
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890

# Connector Settings
CONNECTOR_LOG_RETENTION_DAYS=90
CONNECTOR_CIRCUIT_FAILURE_THRESHOLD=5
CONNECTOR_CIRCUIT_TIMEOUT=60
```

---

## Configuration

### config/connector.php

```php
return [
    // Email vendor selection
    'email_vendor' => env('CONNECTOR_EMAIL_VENDOR', 'mailchimp'),
    
    // SMS vendor selection
    'sms_vendor' => env('CONNECTOR_SMS_VENDOR', 'twilio'),
    
    // Circuit breaker defaults
    'circuit_breaker' => [
        'failure_threshold' => 5,
        'timeout_seconds' => 60,
    ],
    
    // Retry policy defaults
    'retry_policy' => [
        'max_attempts' => 3,
        'initial_delay_ms' => 1000,
        'multiplier' => 2.0,
        'max_delay_ms' => 30000,
    ],
    
    // Log retention
    'log_retention_days' => 90,
];
```

---

## Usage Examples

### Example 1: Send Transactional Email

```php
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

class WelcomeEmailService
{
    public function __construct(
        private readonly EmailServiceConnectorInterface $emailConnector
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $this->emailConnector->sendTransactionalEmail(
            recipient: $user->email,
            subject: 'Welcome to Nexus ERP!',
            body: view('emails.welcome', ['user' => $user])->render()
        );
    }
}
```

**Key Point:** The service doesn't know (or care) if you're using Mailchimp or SendGrid. Swap vendors with one config change.

### Example 2: Send SMS Notification

```php
use Nexus\Connector\Contracts\SmsServiceConnectorInterface;

class OrderNotificationService
{
    public function __construct(
        private readonly SmsServiceConnectorInterface $smsConnector
    ) {}

    public function notifyOrderShipped(Order $order): void
    {
        $message = "Your order #{$order->number} has been shipped!";
        
        $this->smsConnector->send(
            phoneNumber: $order->customer->phone,
            message: $message
        );
    }
}
```

### Example 3: Handle Resilience Automatically

```php
// Circuit breaker, retry, and logging happen automatically!

try {
    $emailConnector->sendTransactionalEmail(
        recipient: 'customer@example.com',
        subject: 'Invoice Ready',
        body: $invoiceHtml
    );
} catch (CircuitBreakerOpenException $e) {
    // Service is down, circuit is open
    Log::warning("Email service unavailable: {$e->getMessage()}");
    // Queue for later retry
    
} catch (RateLimitException $e) {
    // Rate limit hit
    $retryAfter = $e->retryAfterSeconds;
    // Delay and retry
    
} catch (ConnectionException $e) {
    // All retries failed
    Log::error("Failed to send email: {$e->getMessage()}");
}
```

### Example 4: Retrieve Integration Metrics

```php
use Nexus\Connector\Contracts\IntegrationLoggerInterface;

class ConnectorDashboardController
{
    public function metrics(IntegrationLoggerInterface $logger)
    {
        $metrics = $logger->getMetrics(
            serviceName: 'mailchimp',
            from: new \DateTimeImmutable('-7 days'),
            to: new \DateTimeImmutable('now')
        );

        return response()->json($metrics);
        // {
        //   "success_count": 1543,
        //   "failure_count": 12,
        //   "success_rate": 99.23,
        //   "avg_duration_ms": 234.5
        // }
    }
}
```

---

## Creating Vendor Adapters

### Step 1: Implement the Domain Interface

```php
namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use PostmarkApp\PostmarkClient;

final readonly class PostmarkEmailAdapter implements EmailServiceConnectorInterface
{
    public function __construct(
        private string $serverToken,
        private string $fromEmail,
    ) {}

    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        $client = new PostmarkClient($this->serverToken);
        
        $result = $client->sendEmail(
            from: $this->fromEmail,
            to: $recipient,
            subject: $subject,
            htmlBody: $body
        );
        
        return $result->ErrorCode === 0;
    }

    public function sendBulkEmail(array $emails, array $options = []): array
    {
        // Implement bulk sending using Postmark batch API
        // ...
    }

    public function validateAddress(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // Query Postmark statistics API
        // ...
    }
}
```

### Step 2: Install Vendor SDK

```bash
composer require wildbit/postmark-php
```

### Step 3: Register in Service Provider

```php
// app/Providers/ConnectorServiceProvider.php

$this->app->singleton(EmailServiceConnectorInterface::class, function ($app) {
    $vendor = config('connector.email_vendor');

    return match ($vendor) {
        'mailchimp' => new MailchimpEmailAdapter(...),
        'sendgrid' => new SendGridEmailAdapter(...),
        'postmark' => new PostmarkEmailAdapter(
            serverToken: config('connector.email.postmark.server_token'),
            fromEmail: config('connector.email.postmark.from_email')
        ),
        default => throw new \InvalidArgumentException("Unsupported vendor: {$vendor}")
    };
});
```

### Step 4: Update Configuration

```php
// config/connector.php

'email_vendor' => env('CONNECTOR_EMAIL_VENDOR', 'postmark'),

'email' => [
    // ... existing vendors
    
    'postmark' => [
        'server_token' => env('POSTMARK_SERVER_TOKEN'),
        'from_email' => env('MAIL_FROM_ADDRESS'),
    ],
],
```

### Step 5: Swap Vendors

```env
# .env
CONNECTOR_EMAIL_VENDOR=postmark
POSTMARK_SERVER_TOKEN=your_token_here
```

**Done!** All existing code now uses Postmark with zero changes.

---

## API Reference

### Domain Interfaces

#### EmailServiceConnectorInterface

```php
interface EmailServiceConnectorInterface
{
    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool;

    public function sendBulkEmail(array $emails, array $options = []): array;

    public function validateAddress(string $email): bool;

    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array;
}
```

#### SmsServiceConnectorInterface

```php
interface SmsServiceConnectorInterface
{
    public function send(string $phoneNumber, string $message, array $options = []): string;

    public function sendBulk(array $messages, array $options = []): array;

    public function validatePhoneNumber(string $phoneNumber): array;

    public function checkBalance(): array;
}
```

#### CredentialProviderInterface

```php
interface CredentialProviderInterface
{
    public function getCredentials(string $serviceName, ?string $tenantId = null): Credentials;

    public function hasCredentials(string $serviceName, ?string $tenantId = null): bool;

    public function refreshCredentials(string $serviceName, ?string $tenantId = null): Credentials;
}
```

#### IntegrationLoggerInterface

```php
interface IntegrationLoggerInterface
{
    public function log(IntegrationLog $log): void;

    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array;

    public function getMetrics(string $serviceName, \DateTimeInterface $from, \DateTimeInterface $to): array;

    public function purgeOldLogs(int $retentionDays): int;
}
```

---

## Database Schema

### integration_logs Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID (26 chars) | Primary key |
| `tenant_id` | ULID (nullable) | Multi-tenant isolation |
| `service_name` | string(100) | External service identifier |
| `endpoint` | string(500) | Full API endpoint URL |
| `method` | string(10) | HTTP method (GET, POST, etc.) |
| `status` | string(20) | success, failed, timeout, rate_limited, circuit_open |
| `http_status_code` | int (nullable) | HTTP response code |
| `duration_ms` | int | Request duration in milliseconds |
| `request_data` | JSON (nullable) | Sanitized request payload |
| `response_data` | JSON (nullable) | Sanitized response payload |
| `error_message` | text (nullable) | Error details if failed |
| `attempt_number` | int | Retry attempt number (1 for first) |
| `created_at` | timestamp | When request occurred |

**Indexes:**
- `service_name`, `created_at`
- `service_name`, `status`, `created_at`
- `tenant_id`, `service_name`, `created_at`

### connector_credentials Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `tenant_id` | ULID (nullable) | Multi-tenant isolation |
| `service_name` | string(100) | External service identifier |
| `auth_method` | string(20) | api_key, bearer_token, oauth2, basic_auth, hmac |
| `credential_data` | text | Encrypted JSON of credentials |
| `expires_at` | timestamp (nullable) | Token expiration (for OAuth) |
| `refresh_token` | text (nullable) | Encrypted refresh token |
| `is_active` | boolean | Credential status |

**Unique Index:** `service_name`, `tenant_id`

---

## Monitoring and Observability

### API Endpoints

#### GET /api/connector/logs

Retrieve integration logs with filtering.

**Query Parameters:**
- `service`: Filter by service name
- `status`: Filter by status (success, failed, etc.)
- `tenant_id`: Filter by tenant
- `date_from`: Start date (ISO 8601)
- `date_to`: End date (ISO 8601)
- `limit`: Max results (default: 100, max: 500)
- `offset`: Pagination offset

**Example:**
```bash
GET /api/connector/logs?service=mailchimp&status=failed&limit=50
```

**Response:**
```json
{
  "data": [
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
      "service_name": "mailchimp",
      "endpoint": "https://mandrillapp.com/api/1.0/messages/send",
      "method": "POST",
      "status": "failed",
      "http_status_code": 429,
      "duration_ms": 1234,
      "error_message": "Rate limit exceeded",
      "created_at": "2025-11-18T01:20:00Z"
    }
  ],
  "meta": {
    "limit": 50,
    "offset": 0,
    "count": 1
  }
}
```

#### GET /api/connector/metrics/{service}

Get performance metrics for a specific service.

**Query Parameters:**
- `from`: Start date (default: -7 days)
- `to`: End date (default: now)

**Example:**
```bash
GET /api/connector/metrics/mailchimp?from=-30days
```

**Response:**
```json
{
  "service": "mailchimp",
  "period": {
    "from": "2025-10-18 00:00:00",
    "to": "2025-11-18 01:20:00"
  },
  "metrics": {
    "success_count": 45672,
    "failure_count": 234,
    "success_rate": 99.49,
    "avg_duration_ms": 287.3
  }
}
```

#### GET /api/connector/health

Health check endpoint.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-18T01:20:00+00:00",
  "services": {
    "database": "connected",
    "connector": "operational"
  }
}
```

#### GET /api/connector/status

Service status overview for all configured services.

**Response:**
```json
{
  "services": {
    "mailchimp": {
      "status": "healthy",
      "success_rate": 99.87,
      "avg_duration_ms": 245.2
    },
    "twilio": {
      "status": "degraded",
      "success_rate": 94.2,
      "avg_duration_ms": 567.8
    }
  },
  "timestamp": "2025-11-18T01:20:00+00:00"
}
```

### Console Commands

#### Purge Old Logs

```bash
# Use configured retention days
php artisan connector:purge-logs

# Specify custom retention
php artisan connector:purge-logs --days=30
```

**Recommended:** Schedule this in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('connector:purge-logs')
        ->daily()
        ->at('02:00');
}
```

---

## Best Practices

### 1. Always Use Contracts, Never Concrete Adapters

❌ **Bad:**
```php
public function __construct(
    private readonly MailchimpEmailAdapter $mailchimp // Tightly coupled!
) {}
```

✅ **Good:**
```php
public function __construct(
    private readonly EmailServiceConnectorInterface $emailConnector // Decoupled!
) {}
```

### 2. Let Resilience Patterns Handle Failures

The ConnectorManager automatically handles:
- Retries with exponential backoff
- Circuit breaker state management
- Integration logging

You only need to catch exceptions for business logic:

```php
try {
    $emailConnector->sendTransactionalEmail(...);
} catch (CircuitBreakerOpenException $e) {
    // Queue for later when service recovers
    dispatch(new SendEmailJob($recipient, $subject, $body))->delay(now()->addMinutes(5));
} catch (ConnectionException $e) {
    // All retries failed - alert admin or log for manual review
    Log::critical("Email delivery failed after retries", ['error' => $e->getMessage()]);
}
```

### 3. Store Credentials Securely

Use the `LaravelCredentialProvider` helper to store credentials:

```php
use App\Repositories\LaravelCredentialProvider;
use Nexus\Connector\ValueObjects\AuthMethod;

$provider = app(LaravelCredentialProvider::class);

$provider->storeCredentials(
    serviceName: 'stripe',
    authMethod: AuthMethod::API_KEY,
    credentialData: ['api_key' => $request->stripe_secret_key],
    tenantId: $currentTenant->id,
    expiresAt: null // API keys don't expire
);
```

Credentials are automatically encrypted at rest.

### 4. Monitor Integration Health

Set up alerts based on metrics:

```php
$metrics = $integrationLogger->getMetrics('mailchimp', $from, $to);

if ($metrics['success_rate'] < 95) {
    // Alert: Mailchimp success rate dropped below 95%
    Notification::send($admins, new IntegrationDegradedNotification('mailchimp', $metrics));
}

if ($metrics['avg_duration_ms'] > 5000) {
    // Alert: Mailchimp response time > 5 seconds
    Notification::send($admins, new SlowIntegrationNotification('mailchimp', $metrics));
}
```

### 5. Test with Mock Adapters

Create test doubles that implement the interface:

```php
class FakeEmailAdapter implements EmailServiceConnectorInterface
{
    public array $sent = [];

    public function sendTransactionalEmail(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        $this->sent[] = compact('recipient', 'subject', 'body');
        return true;
    }

    // ... implement other methods
}

// In tests:
$this->app->singleton(EmailServiceConnectorInterface::class, FakeEmailAdapter::class);

// Run your code
$service->sendWelcomeEmail($user);

// Assert
$adapter = app(EmailServiceConnectorInterface::class);
$this->assertCount(1, $adapter->sent);
$this->assertEquals($user->email, $adapter->sent[0]['recipient']);
```

### 6. Use Domain-Specific Interfaces

Don't use a generic "HttpClientInterface". Use domain-specific interfaces:

✅ **Good:**
- `EmailServiceConnectorInterface`
- `SmsServiceConnectorInterface`
- `PaymentGatewayConnectorInterface`

This makes the intent clear and allows type-safe method signatures.

### 7. Implement Webhook Verification

```php
use Nexus\Connector\Contracts\WebhookVerifierInterface;

Route::post('webhooks/mailchimp', function (Request $request, WebhookVerifierInterface $verifier) {
    $signature = $request->header('X-Mailchimp-Signature');
    $secret = config('connector.email.mailchimp.webhook_secret');
    
    if (!$verifier->verify($request->getContent(), $signature, $secret)) {
        abort(403, 'Invalid webhook signature');
    }
    
    // Process webhook...
});
```

---

## Summary

The `Nexus\Connector` package provides:

✅ **110 documented requirements** covering all aspects  
✅ **Framework-agnostic atomic package** (pure PHP)  
✅ **Plugin/adapter pattern** for vendor swapping  
✅ **Enterprise resilience** (circuit breaker, retry, rate limiting)  
✅ **Complete observability** (logging, metrics, health checks)  
✅ **Secure credential management** with encryption  
✅ **Zero-code vendor swapping** via configuration  
✅ **10 domain interfaces** for common integrations  

**Next Steps:**
1. Review the example adapters in `apps/Atomy/app/Connectors/Adapters/`
2. Implement your specific vendor adapters
3. Configure your services in `config/connector.php`
4. Monitor integration health via `/api/connector/status`
5. Set up alerts based on success rate and latency metrics

For questions or contributions, see the main [ARCHITECTURE.md](../ARCHITECTURE.md) document.
