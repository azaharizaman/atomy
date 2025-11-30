# API Reference: Connector

This document provides comprehensive documentation of all public interfaces, value objects, and exceptions in the Nexus Connector package.

---

## Interfaces

### EmailServiceConnectorInterface

**Location:** `src/Contracts/EmailServiceConnectorInterface.php`

**Purpose:** Abstraction for email service providers (Mailchimp, SendGrid, Amazon SES, Postmark)

**Methods:**

#### sendTransactionalEmail()

```php
public function sendTransactionalEmail(
    string $recipient,
    string $subject,
    string $body,
    array $options = []
): bool;
```

**Description:** Send a single transactional email

**Parameters:**
- `$recipient` (string) - Email address of the recipient
- `$subject` (string) - Email subject line
- `$body` (string) - Email body (HTML or plain text)
- `$options` (array) - Optional: cc, bcc, attachments, reply-to

**Returns:** `bool` - True if email was successfully sent

**Throws:**
- `ConnectionException` - When email service is unreachable or returns error

**Example:**
```php
$sent = $emailConnector->sendTransactionalEmail(
    recipient: 'customer@example.com',
    subject: 'Your Order Confirmation',
    body: '<h1>Thank you for your order!</h1>',
    options: ['cc' => ['manager@example.com']]
);
```

#### sendBulkEmail()

```php
public function sendBulkEmail(array $emails, array $options = []): array;
```

**Description:** Send multiple emails in a single batch operation

**Parameters:**
- `$emails` (array) - Array of email data: `[['recipient' => '...', 'subject' => '...', 'body' => '...'], ...]`
- `$options` (array) - Optional batch settings

**Returns:** `array` - `['sent' => int, 'failed' => int, 'errors' => array]`

**Example:**
```php
$result = $emailConnector->sendBulkEmail([
    ['recipient' => 'user1@example.com', 'subject' => 'Newsletter', 'body' => '...'],
    ['recipient' => 'user2@example.com', 'subject' => 'Newsletter', 'body' => '...'],
]);
// Returns: ['sent' => 2, 'failed' => 0, 'errors' => []]
```

#### validateAddress()

```php
public function validateAddress(string $email): bool;
```

**Description:** Validate email address format and optionally check deliverability

**Returns:** `bool` - True if email is valid

#### getStatistics()

```php
public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array;
```

**Description:** Get email sending statistics for date range

**Returns:** `array` - Statistics including sent count, bounce rate, open rate

---

### SmsServiceConnectorInterface

**Location:** `src/Contracts/SmsServiceConnectorInterface.php`

**Purpose:** Abstraction for SMS service providers (Twilio, Nexmo, AWS SNS)

**Methods:**

#### send()

```php
public function send(string $phoneNumber, string $message, array $options = []): string;
```

**Description:** Send a single SMS message

**Parameters:**
- `$phoneNumber` (string) - Recipient phone number (E.164 format: +1234567890)
- `$message` (string) - SMS message body (max 160 characters for single SMS)
- `$options` (array) - Optional: sender ID, delivery notification callback

**Returns:** `string` - Message ID from provider

**Throws:**
- `ConnectionException` - When SMS service is unreachable
- `RateLimitExceededException` - When provider rate limit is hit

**Example:**
```php
$messageId = $smsConnector->send(
    phoneNumber: '+60123456789',
    message: 'Your verification code is 123456'
);
```

#### sendBulk()

```php
public function sendBulk(array $messages, array $options = []): array;
```

**Description:** Send multiple SMS messages in batch

**Parameters:**
- `$messages` (array) - Array of `['phone' => '...', 'message' => '...']`

**Returns:** `array` - `['sent' => int, 'failed' => int, 'message_ids' => array]`

#### validatePhoneNumber()

```php
public function validatePhoneNumber(string $phoneNumber): array;
```

**Description:** Validate phone number format and lookup carrier info

**Returns:** `array` - `['valid' => bool, 'carrier' => string, 'country' => string]`

#### checkBalance()

```php
public function checkBalance(): array;
```

**Description:** Check SMS account balance and quota

**Returns:** `array` - `['balance' => float, 'currency' => string, 'quota_remaining' => int]`

---

### PaymentGatewayConnectorInterface

**Location:** `src/Contracts/PaymentGatewayConnectorInterface.php`

**Purpose:** Abstraction for payment gateways (Stripe, PayPal, Square, Authorize.net)

**Methods:**

#### charge()

```php
public function charge(float $amount, string $currency, array $paymentMethod, array $options = []): array;
```

**Description:** Process immediate payment charge

**Parameters:**
- `$amount` (float) - Amount to charge
- `$currency` (string) - Currency code (USD, EUR, MYR, etc.)
- `$paymentMethod` (array) - Payment method data (card token, PayPal account, etc.)
- `$options` (array) - Optional: description, metadata, customer_id

**Returns:** `array` - `['transaction_id' => string, 'status' => string, 'receipt_url' => string]`

**Throws:**
- `PaymentDeclinedException` - When payment is declined by issuer
- `AuthenticationException` - When 3D Secure authentication required

**Example:**
```php
$result = $paymentGateway->charge(
    amount: 99.99,
    currency: 'MYR',
    paymentMethod: ['token' => 'tok_visa_1234'],
    options: ['description' => 'Invoice #INV-001']
);
```

#### refund()

```php
public function refund(string $transactionId, ?float $amount = null, array $options = []): array;
```

**Description:** Refund a previous charge (full or partial)

**Parameters:**
- `$transactionId` (string) - Original transaction ID to refund
- `$amount` (float|null) - Amount to refund (null = full refund)
- `$options` (array) - Optional: reason, metadata

**Returns:** `array` - `['refund_id' => string, 'status' => string, 'amount_refunded' => float]`

#### createPaymentIntent()

```php
public function createPaymentIntent(float $amount, string $currency, array $options = []): array;
```

**Description:** Create payment intent for deferred authorization (useful for 3D Secure)

**Returns:** `array` - `['intent_id' => string, 'client_secret' => string, 'status' => string]`

#### captureAuthorization()

```php
public function captureAuthorization(string $authorizationId, ?float $amount = null): array;
```

**Description:** Capture a previously authorized payment

---

### CloudStorageConnectorInterface

**Location:** `src/Contracts/CloudStorageConnectorInterface.php`

**Purpose:** Abstraction for cloud storage providers (AWS S3, Google Cloud Storage, Azure Blob)

**Methods:**

#### upload()

```php
public function upload(string $path, string $contents, array $options = []): string;
```

**Description:** Upload file to cloud storage

**Parameters:**
- `$path` (string) - Destination path (e.g., 'invoices/2025/invoice-001.pdf')
- `$contents` (string) - File contents
- `$options` (array) - Optional: content_type, acl, metadata

**Returns:** `string` - Public URL or storage key

**Example:**
```php
$url = $cloudStorage->upload(
    path: 'documents/contract-2025.pdf',
    contents: file_get_contents('/tmp/contract.pdf'),
    options: ['content_type' => 'application/pdf', 'acl' => 'private']
);
```

#### download()

```php
public function download(string $path): string;
```

**Description:** Download file contents from cloud storage

**Returns:** `string` - File contents

**Throws:**
- `FileNotFoundException` - When file does not exist

#### delete()

```php
public function delete(string $path): bool;
```

**Description:** Delete file from cloud storage

**Returns:** `bool` - True if deleted successfully

#### generateSignedUrl()

```php
public function generateSignedUrl(string $path, int $expiresInSeconds = 3600): string;
```

**Description:** Generate temporary signed URL for private file access

**Parameters:**
- `$path` (string) - File path
- `$expiresInSeconds` (int) - URL validity duration (default: 1 hour)

**Returns:** `string` - Signed URL

---

### ShippingProviderConnectorInterface

**Location:** `src/Contracts/ShippingProviderConnectorInterface.php`

**Purpose:** Abstraction for shipping providers (FedEx, UPS, DHL, USPS)

**Methods:**

#### createShipment()

```php
public function createShipment(array $fromAddress, array $toAddress, array $package, array $options = []): array;
```

**Description:** Create a shipping label

**Returns:** `array` - `['tracking_number' => string, 'label_url' => string, 'cost' => float]`

#### trackShipment()

```php
public function trackShipment(string $trackingNumber): array;
```

**Description:** Get shipment tracking status

**Returns:** `array` - `['status' => string, 'location' => string, 'estimated_delivery' => string, 'events' => array]`

#### calculateRates()

```php
public function calculateRates(array $fromAddress, array $toAddress, array $package): array;
```

**Description:** Get shipping rate quotes

**Returns:** `array` - Array of service options with rates

---

### CredentialProviderInterface

**Location:** `src/Contracts/CredentialProviderInterface.php`

**Purpose:** Secure credential retrieval for external services

**Methods:**

#### getCredentials()

```php
public function getCredentials(string $serviceName, ?string $tenantId = null): Credentials;
```

**Description:** Retrieve credentials for a service

**Parameters:**
- `$serviceName` (string) - Service identifier (e.g., 'stripe', 'mailchimp')
- `$tenantId` (string|null) - Optional tenant ID for multi-tenant isolation

**Returns:** `Credentials` - Value object containing auth method and credential data

**Throws:**
- `CredentialNotFoundException` - When credentials not found

#### hasCredentials()

```php
public function hasCredentials(string $serviceName, ?string $tenantId = null): bool;
```

**Description:** Check if credentials exist for a service

#### refreshCredentials()

```php
public function refreshCredentials(string $serviceName, ?string $tenantId = null): Credentials;
```

**Description:** Refresh OAuth access token using refresh token

**Throws:**
- `CredentialRefreshException` - When token refresh fails

---

### IntegrationLoggerInterface

**Location:** `src/Contracts/IntegrationLoggerInterface.php`

**Purpose:** Audit logging for all external API calls

**Methods:**

#### log()

```php
public function log(IntegrationLog $log): void;
```

**Description:** Persist integration log entry

#### getLogs()

```php
public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array;
```

**Description:** Retrieve integration logs with filtering

**Parameters:**
- `$filters` (array) - Optional: `['service_name' => '...', 'status' => '...', 'from_date' => ...]`
- `$limit` (int) - Max results per page
- `$offset` (int) - Pagination offset

**Returns:** `array` - Array of `IntegrationLog` objects

#### getMetrics()

```php
public function getMetrics(string $serviceName, \DateTimeInterface $from, \DateTimeInterface $to): array;
```

**Description:** Get aggregated metrics for a service

**Returns:** `array` - `['success_count' => int, 'failure_count' => int, 'success_rate' => float, 'avg_duration_ms' => float]`

---

### CircuitBreakerStorageInterface

**Location:** `src/Contracts/CircuitBreakerStorageInterface.php`

**Purpose:** Storage backend for circuit breaker state (ensures stateless services)

**Methods:**

#### getState()

```php
public function getState(string $serviceName): CircuitBreakerState;
```

**Description:** Retrieve current circuit breaker state for a service

**Returns:** `CircuitBreakerState` - Value object containing state, failure count, opened at timestamp

#### saveState()

```php
public function saveState(string $serviceName, CircuitBreakerState $state): void;
```

**Description:** Persist circuit breaker state

---

### RateLimiterStorageInterface

**Location:** `src/Contracts/RateLimiterStorageInterface.php`

**Purpose:** Storage backend for rate limiter tokens (ensures stateless services)

**Methods:**

#### getTokens()

```php
public function getTokens(string $key): int;
```

**Description:** Get available tokens for rate limit key

#### consumeTokens()

```php
public function consumeTokens(string $key, int $tokens = 1): bool;
```

**Description:** Attempt to consume tokens from bucket

**Returns:** `bool` - True if tokens consumed, false if insufficient

#### refillTokens()

```php
public function refillTokens(string $key, int $tokens, int $maxTokens): void;
```

**Description:** Add tokens back to bucket (automatic refill based on time)

---

### WebhookVerifierInterface

**Location:** `src/Contracts/WebhookVerifierInterface.php`

**Purpose:** Verify incoming webhook signatures

**Methods:**

#### verify()

```php
public function verify(string $payload, string $signature, string $secret): bool;
```

**Description:** Verify webhook signature using HMAC

**Parameters:**
- `$payload` (string) - Raw webhook payload
- `$signature` (string) - Signature from webhook header
- `$secret` (string) - Webhook signing secret

**Returns:** `bool` - True if signature is valid

---

## Value Objects

### Credentials

**Location:** `src/ValueObjects/Credentials.php`

**Purpose:** Immutable credential container

**Properties:**
- `authMethod` (AuthMethod enum) - Authentication method (API_KEY, BEARER_TOKEN, OAUTH2, BASIC_AUTH, HMAC)
- `credentialData` (array) - Credential data (keys, tokens, etc.)

**Methods:**

#### create()

```php
public static function create(AuthMethod $authMethod, array $credentialData): self
```

**Example:**
```php
$credentials = Credentials::create(
    authMethod: AuthMethod::API_KEY,
    credentialData: ['api_key' => 'sk_live_1234567890']
);
```

---

### Endpoint

**Location:** `src/ValueObjects/Endpoint.php`

**Purpose:** API endpoint configuration

**Properties:**
- `url` (string) - Full endpoint URL
- `method` (HttpMethod enum) - HTTP method (GET, POST, PUT, DELETE, PATCH)
- `headers` (array) - Request headers
- `timeoutSeconds` (int) - Request timeout
- `retryPolicy` (RetryPolicy|null) - Retry configuration
- `rateLimitConfig` (RateLimitConfig|null) - Rate limiting configuration

**Methods:**

#### create()

```php
public static function create(string $url, HttpMethod $method = HttpMethod::GET): self
```

#### withTimeout()

```php
public function withTimeout(int $seconds): self
```

#### withRetryPolicy()

```php
public function withRetryPolicy(RetryPolicy $policy): self
```

#### withRateLimit()

```php
public function withRateLimit(RateLimitConfig $config): self
```

**Example:**
```php
$endpoint = Endpoint::create('https://api.stripe.com/v1/charges', HttpMethod::POST)
    ->withTimeout(30)
    ->withRetryPolicy(RetryPolicy::default())
    ->withRateLimit(RateLimitConfig::perSecond(100));
```

---

### RetryPolicy

**Location:** `src/ValueObjects/RetryPolicy.php`

**Purpose:** Retry configuration with exponential backoff

**Properties:**
- `maxAttempts` (int) - Maximum retry attempts
- `initialDelayMs` (int) - Initial delay in milliseconds
- `multiplier` (float) - Backoff multiplier
- `maxDelayMs` (int) - Maximum delay cap

**Methods:**

#### default()

```php
public static function default(): self  // 3 attempts, 1s initial, 2x multiplier, 30s max
```

#### aggressive()

```php
public static function aggressive(): self  // 5 attempts, 500ms initial
```

#### conservative()

```php
public static function conservative(): self  // 2 attempts, 2s initial
```

**Example:**
```php
$policy = RetryPolicy::create(
    maxAttempts: 3,
    initialDelayMs: 1000,
    multiplier: 2.0,
    maxDelayMs: 30000
);
```

---

### IntegrationLog

**Location:** `src/ValueObjects/IntegrationLog.php`

**Purpose:** Immutable log entry for external API call

**Properties:**
- `serviceName` (string)
- `endpoint` (string)
- `method` (HttpMethod)
- `status` (IntegrationStatus enum)
- `httpStatusCode` (int|null)
- `durationMs` (int)
- `requestData` (array|null)
- `responseData` (array|null)
- `errorMessage` (string|null)
- `attemptNumber` (int)
- `tenantId` (string|null)

**Methods:**

#### success()

```php
public static function success(string $serviceName, string $endpoint, int $durationMs, ...): self
```

#### failure()

```php
public static function failure(string $serviceName, string $endpoint, string $errorMessage, ...): self
```

---

### CircuitBreakerState

**Location:** `src/ValueObjects/CircuitBreakerState.php`

**Purpose:** Circuit breaker state tracking

**Properties:**
- `state` (CircuitState enum) - CLOSED, OPEN, HALF_OPEN
- `failureCount` (int)
- `lastFailureAt` (DateTimeImmutable|null)
- `openedAt` (DateTimeImmutable|null)

**Methods:**

#### closed()

```php
public static function closed(): self
```

#### open()

```php
public function open(): self
```

#### halfOpen()

```php
public function halfOpen(): self
```

#### shouldAttemptRequest()

```php
public function shouldAttemptRequest(int $timeoutSeconds): bool
```

---

### RateLimitConfig

**Location:** `src/ValueObjects/RateLimitConfig.php`

**Purpose:** Rate limiting configuration

**Properties:**
- `maxRequests` (int) - Maximum requests
- `perSeconds` (int) - Time window in seconds
- `burstCapacity` (int) - Burst allowance

**Methods:**

#### perSecond()

```php
public static function perSecond(int $maxRequests): self
```

#### perMinute()

```php
public static function perMinute(int $maxRequests): self
```

#### perHour()

```php
public static function perHour(int $maxRequests): self
```

**Example:**
```php
// Stripe: 100 req/sec
$stripeLimit = RateLimitConfig::perSecond(100);

// Mailchimp: 10 req/sec
$mailchimpLimit = RateLimitConfig::perSecond(10);
```

---

## Enums

### AuthMethod

**Location:** `src/ValueObjects/AuthMethod.php`

**Cases:**
- `API_KEY` - API key authentication
- `BEARER_TOKEN` - Bearer token authentication
- `OAUTH2` - OAuth 2.0 authentication
- `BASIC_AUTH` - HTTP Basic authentication
- `HMAC` - HMAC signature authentication

**Example:**
```php
$authMethod = AuthMethod::API_KEY;
```

---

### HttpMethod

**Location:** `src/ValueObjects/HttpMethod.php`

**Cases:**
- `GET`
- `POST`
- `PUT`
- `PATCH`
- `DELETE`

---

### IntegrationStatus

**Location:** `src/ValueObjects/IntegrationStatus.php`

**Cases:**
- `SUCCESS` - Request completed successfully
- `FAILED` - Request failed
- `TIMEOUT` - Request timed out
- `RATE_LIMITED` - Rate limit exceeded
- `CIRCUIT_OPEN` - Circuit breaker is open

---

### CircuitState

**Location:** `src/ValueObjects/CircuitState.php`

**Cases:**
- `CLOSED` - Normal operation, requests allowed
- `OPEN` - Service failing, requests blocked
- `HALF_OPEN` - Testing if service recovered

---

## Exceptions

### ConnectorException

**Location:** `src/Exceptions/ConnectorException.php`

**Extends:** `\RuntimeException`

**Purpose:** Base exception for all Connector errors

---

### ConnectionException

**Location:** `src/Exceptions/ConnectionException.php`

**Purpose:** Thrown when connection to external service fails (after retries)

**Factory Methods:**

#### timeout()

```php
public static function timeout(string $serviceName, int $timeoutSeconds): self
```

#### networkError()

```php
public static function networkError(string $serviceName, string $reason): self
```

---

### CircuitBreakerOpenException

**Location:** `src/Exceptions/CircuitBreakerOpenException.php`

**Purpose:** Thrown when circuit breaker is open and request is blocked

**Factory Methods:**

#### forService()

```php
public static function forService(string $serviceName): self
```

---

### RateLimitExceededException

**Location:** `src/Exceptions/RateLimitExceededException.php`

**Purpose:** Thrown when rate limit is exceeded

**Properties:**
- `retryAfterSeconds` (int) - How long to wait before retry

**Factory Methods:**

#### create()

```php
public static function create(string $serviceName, int $retryAfterSeconds): self
```

---

### AuthenticationException

**Location:** `src/Exceptions/AuthenticationException.php`

**Purpose:** Thrown when authentication fails (invalid credentials)

---

### PaymentDeclinedException

**Location:** `src/Exceptions/PaymentDeclinedException.php`

**Purpose:** Thrown when payment is declined by issuer

**Properties:**
- `declineCode` (string)
- `declineReason` (string)

---

### CredentialNotFoundException

**Location:** `src/Exceptions/CredentialNotFoundException.php`

**Purpose:** Thrown when credentials not found for service

---

### CredentialRefreshException

**Location:** `src/Exceptions/CredentialRefreshException.php`

**Purpose:** Thrown when OAuth token refresh fails

---

### FileNotFoundException

**Location:** `src/Exceptions/FileNotFoundException.php`

**Purpose:** Thrown when cloud storage file not found

---

## Usage Patterns

### Pattern 1: Simple Email Sending

```php
$emailConnector->sendTransactionalEmail(
    recipient: 'customer@example.com',
    subject: 'Your receipt',
    body: $html
);
```

### Pattern 2: With Error Handling

```php
try {
    $emailConnector->sendTransactionalEmail(...);
} catch (CircuitBreakerOpenException $e) {
    // Queue for later
} catch (ConnectionException $e) {
    // Log error
}
```

### Pattern 3: Custom Endpoint with Retry

```php
$endpoint = Endpoint::create('https://api.vendor.com/resource')
    ->withRetryPolicy(RetryPolicy::aggressive())
    ->withRateLimit(RateLimitConfig::perSecond(50));
```

### Pattern 4: Payment with Idempotency

```php
$result = $paymentGateway->charge(
    amount: 99.99,
    currency: 'USD',
    paymentMethod: ['token' => $cardToken],
    options: ['idempotency_key' => $orderId]
);
```
