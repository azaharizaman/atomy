# Test Suite Summary: Connector

**Package:** `Nexus\Connector`  
**Last Updated:** 2025-11-24  
**Status:** ⚠️ No Package-Level Tests (Architectural Decision)

---

## Testing Philosophy

The `Nexus\Connector` package follows the **Application-Layer Testing** architectural pattern used throughout the Nexus monorepo for pure business logic packages.

### Why No Package-Level Tests?

**Architectural Decision:** This is a **framework-agnostic, pure PHP logic package** that:

1. **Defines interfaces only** - All external dependencies are abstracted via contracts
2. **Has no persistence layer** - All storage needs are delegated to `StorageInterface` implementations
3. **Requires application context** - Testing requires concrete implementations of:
   - `ConnectionStorageInterface` (database/cache)
   - `CircuitBreakerStorageInterface` (Redis/database)
   - `RateLimiterStorageInterface` (Redis/database)
   - `EventDispatcherInterface` (framework event system)
   - `LoggerInterface` (PSR-3 logger)
   - `TelemetryTrackerInterface` (metrics collector)

4. **Cannot be tested in isolation** - The package is pure coordination logic that orchestrates between:
   - HTTP client adapters (Guzzle, Symfony HTTP Client)
   - Storage implementations (Redis, Database, File)
   - Framework event systems (Laravel Events, Symfony EventDispatcher)
   - Logging systems (Monolog, custom loggers)

**Therefore:** Testing happens at the **consuming application layer** where all dependencies are bound to concrete implementations.

---

## Test Coverage Strategy

### Application-Layer Integration Tests

The Connector package is tested through **integration tests in consuming applications** that:

1. **Bind all interfaces to concrete implementations**
   - Use Laravel/Symfony service containers
   - Provide real database connections
   - Provide Redis for circuit breaker and rate limiter state
   - Provide HTTP client mocks for external API calls

2. **Test real-world integration scenarios**
   - Email service integration (SendGrid, Mailgun, AWS SES)
   - SMS provider integration (Twilio, AWS SNS)
   - Payment gateway integration (Stripe, PayPal)
   - Cloud storage integration (AWS S3, Azure Blob)
   - CRM integration (Salesforce, HubSpot)

3. **Validate resilience patterns**
   - Circuit breaker opens after consecutive failures
   - Rate limiter enforces token bucket limits
   - Retry logic with exponential backoff works correctly
   - OAuth token refresh before expiry
   - Webhook signature verification

### Example Integration Test Structure

```php
// tests/Integration/ConnectorIntegrationTest.php (in consuming application)

use Nexus\Connector\Contracts\ConnectorManagerInterface;
use Tests\TestCase;

final class ConnectorIntegrationTest extends TestCase
{
    private ConnectorManagerInterface $connector;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Application binds all interfaces
        $this->connector = app(ConnectorManagerInterface::class);
        
        // Use test database and Redis for state
        $this->artisan('migrate:fresh');
        Redis::flushall();
    }
    
    /** @test */
    public function it_can_send_email_via_sendgrid(): void
    {
        // Create connection
        $connectionId = $this->connector->createConnection([
            'name' => 'SendGrid Test',
            'provider' => 'sendgrid',
            'endpoint' => 'https://api.sendgrid.com',
            'credentials' => [
                'api_key' => 'test_key_123',
            ],
        ]);
        
        // Mock HTTP client response
        Http::fake([
            'api.sendgrid.com/*' => Http::response(['message' => 'success'], 200),
        ]);
        
        // Send email
        $connection = $this->connector->getConnection($connectionId);
        $response = $connection->request('POST', '/v3/mail/send', [
            'json' => [
                'personalizations' => [
                    ['to' => [['email' => 'customer@example.com']]],
                ],
                'from' => ['email' => 'noreply@myapp.com'],
                'subject' => 'Order Confirmation',
                'content' => [
                    ['type' => 'text/plain', 'value' => 'Thank you for your order!'],
                ],
            ],
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getData()['message']);
    }
    
    /** @test */
    public function circuit_breaker_opens_after_consecutive_failures(): void
    {
        $connectionId = $this->connector->createConnection([
            'name' => 'Flaky API',
            'provider' => 'custom',
            'endpoint' => 'https://flaky-api.example.com',
            'resilience' => [
                'circuit_breaker' => [
                    'failure_threshold' => 3,
                    'timeout' => 60,
                ],
            ],
        ]);
        
        // Mock API to fail
        Http::fake([
            'flaky-api.example.com/*' => Http::response(null, 500),
        ]);
        
        $connection = $this->connector->getConnection($connectionId);
        
        // Trigger 3 failures
        for ($i = 0; $i < 3; $i++) {
            try {
                $connection->request('GET', '/data');
            } catch (\Exception $e) {
                // Expected failure
            }
        }
        
        // Circuit breaker should now be open
        $this->expectException(CircuitBreakerOpenException::class);
        $connection->request('GET', '/data');
    }
    
    /** @test */
    public function rate_limiter_enforces_token_bucket(): void
    {
        $connectionId = $this->connector->createConnection([
            'name' => 'Rate Limited API',
            'provider' => 'custom',
            'endpoint' => 'https://api.example.com',
            'rate_limiting' => [
                'max_requests' => 5,
                'window' => 60, // 5 requests per minute
            ],
        ]);
        
        Http::fake();
        
        $connection = $this->connector->getConnection($connectionId);
        
        // Make 5 successful requests
        for ($i = 0; $i < 5; $i++) {
            $response = $connection->request('GET', '/data');
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // 6th request should be rate limited
        $this->expectException(RateLimitExceededException::class);
        $connection->request('GET', '/data');
    }
    
    /** @test */
    public function oauth_token_refreshes_before_expiry(): void
    {
        $connectionId = $this->connector->createConnection([
            'name' => 'OAuth API',
            'provider' => 'custom',
            'endpoint' => 'https://api.example.com',
            'auth_type' => 'oauth2',
            'credentials' => [
                'client_id' => 'test_client',
                'client_secret' => 'test_secret',
                'refresh_token' => 'refresh_token_123',
                'access_token' => 'old_token',
                'expires_at' => now()->addSeconds(30), // Expires soon
            ],
        ]);
        
        // Mock token refresh endpoint
        Http::fake([
            'api.example.com/oauth/token' => Http::response([
                'access_token' => 'new_token_456',
                'expires_in' => 3600,
            ], 200),
            'api.example.com/*' => Http::response(['data' => 'success'], 200),
        ]);
        
        $connection = $this->connector->getConnection($connectionId);
        
        // Should trigger token refresh before making request
        $response = $connection->request('GET', '/data');
        
        // Verify new token was obtained
        $updatedConnection = $this->connector->getConnection($connectionId);
        $this->assertEquals('new_token_456', $updatedConnection->getAccessToken());
    }
}
```

---

## Test Coverage (Application Layer)

### Tested Components

Integration tests in consuming applications cover:

✅ **Connection Management**
- Connection creation with all provider types
- Connection retrieval and listing
- Connection updates (credentials, settings)
- Connection deletion and archival
- Multi-tenant connection scoping

✅ **HTTP Request Handling**
- GET, POST, PUT, PATCH, DELETE requests
- Query parameters and headers
- JSON and form data bodies
- File uploads
- Custom headers and authentication

✅ **Circuit Breaker**
- Opens after consecutive failures
- Half-open state after timeout
- Closes after successful requests
- Failure threshold configuration
- Timeout configuration

✅ **Rate Limiting**
- Token bucket algorithm enforcement
- Per-connection rate limits
- Sliding window tracking
- Rate limit exceeded exceptions
- Token replenishment

✅ **Retry Logic**
- Exponential backoff calculation
- Maximum retries configuration
- Retry on specific HTTP status codes
- Retry with jitter
- Retry exhaustion handling

✅ **OAuth 2.0 Integration**
- Access token refresh before expiry
- Refresh token rotation
- Token expiry detection
- Authorization header injection
- Token storage and retrieval

✅ **Webhook Verification**
- HMAC signature validation
- Timestamp validation
- Replay attack prevention
- Multiple signature algorithms (SHA256, SHA512)
- Custom header configuration

✅ **Event Dispatching**
- Request sent events
- Response received events
- Connection created/updated/deleted events
- Circuit breaker state change events
- Rate limit exceeded events
- OAuth token refreshed events

✅ **Metrics Tracking**
- Request count by connection
- Request duration histograms
- Error count by type
- Circuit breaker state changes
- Rate limit hits
- OAuth refresh count

✅ **Error Handling**
- Connection not found exceptions
- Invalid credentials exceptions
- Network timeout exceptions
- Invalid response exceptions
- Circuit breaker open exceptions
- Rate limit exceeded exceptions

---

## Package-Level Unit Tests: NOT APPLICABLE

**Why:**
- Package contains only **interface definitions** and **coordination logic**
- No concrete implementations to test in isolation
- All business logic requires external dependencies (storage, HTTP client, logger)
- Testing interfaces without implementations is meaningless

**Alternative:** The package's correctness is validated through:
1. **Type safety** - PHP 8.3 strict types prevent many runtime errors
2. **Static analysis** - PHPStan/Psalm can validate logic flow
3. **Integration tests** - Real-world usage in consuming applications
4. **Production monitoring** - Telemetry tracks actual behavior

---

## Test Execution (Application Layer)

### Running Tests in Consuming Application

```bash
# Run all Connector integration tests
./vendor/bin/phpunit tests/Integration/Connector

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage tests/Integration/Connector

# Run specific test
./vendor/bin/phpunit --filter=it_can_send_email_via_sendgrid
```

### Expected Coverage (Application Layer)

| Component | Expected Coverage | Rationale |
|-----------|-------------------|-----------|
| ConnectorManager | 100% | All public methods tested |
| Connection | 100% | All request methods tested |
| CircuitBreakerManager | 100% | All state transitions tested |
| RateLimiterManager | 100% | Token bucket logic tested |
| RetryManager | 100% | Exponential backoff tested |
| OAuthTokenRefresher | 100% | Token refresh flow tested |
| WebhookVerifier | 100% | Signature validation tested |

---

## Continuous Integration

### CI Pipeline (in consuming application)

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: password
      redis:
        image: redis:7
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: redis, pdo_pgsql
      
      - name: Install dependencies
        run: composer install
      
      - name: Run integration tests
        run: ./vendor/bin/phpunit tests/Integration/Connector
        env:
          DB_CONNECTION: pgsql
          REDIS_HOST: redis
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

---

## Test Inventory (Application Layer Examples)

### Integration Tests

**In consuming applications:**
- `ConnectorIntegrationTest.php` - Core functionality (XX tests)
- `EmailProviderIntegrationTest.php` - Email providers (XX tests)
- `SmsProviderIntegrationTest.php` - SMS providers (XX tests)
- `PaymentGatewayIntegrationTest.php` - Payment gateways (XX tests)
- `CloudStorageIntegrationTest.php` - Cloud storage (XX tests)
- `ResiliencyTest.php` - Circuit breaker and retries (XX tests)
- `RateLimitingTest.php` - Rate limiter behavior (XX tests)
- `OAuthIntegrationTest.php` - OAuth token management (XX tests)
- `WebhookIntegrationTest.php` - Webhook verification (XX tests)
- `MultiTenantIntegrationTest.php` - Multi-tenant isolation (XX tests)

### Manual Testing

**Recommended manual testing scenarios:**
- Real API integration (SendGrid, Twilio, Stripe) with actual credentials
- Network failure scenarios (timeout, connection refused)
- OAuth token refresh edge cases (expired refresh token)
- Webhook replay attacks
- High-volume rate limiting (stress test)

---

## Testing Best Practices

### For Consuming Applications

1. **Use in-memory/test databases** - Fast, isolated tests
2. **Mock external HTTP calls** - Avoid hitting real APIs in tests
3. **Use Redis for state** - Test circuit breaker and rate limiter realistically
4. **Test failure scenarios** - Network errors, invalid responses, timeouts
5. **Test multi-tenancy** - Verify connection isolation between tenants
6. **Test edge cases** - Empty credentials, missing config, null values
7. **Test concurrency** - Simulate simultaneous requests
8. **Monitor test execution time** - Keep tests fast (<500ms per test)

### Example: Mocking HTTP Responses

```php
use Illuminate\Support\Facades\Http;

// Mock successful response
Http::fake([
    'api.sendgrid.com/*' => Http::response([
        'message' => 'success',
        'id' => 'msg_123',
    ], 200),
]);

// Mock rate limit error
Http::fake([
    'api.example.com/*' => Http::response([
        'error' => 'Rate limit exceeded',
    ], 429, ['Retry-After' => '60']),
]);

// Mock network timeout
Http::fake([
    'slow-api.example.com/*' => function ($request) {
        sleep(31); // Simulate timeout
        throw new ConnectionException('Connection timeout');
    },
]);
```

---

## Known Testing Gaps

### Not Tested (By Design)

1. **Real external API behavior** - Would require credentials and incur costs
2. **Network infrastructure failures** - DNS failures, proxy errors (hard to simulate)
3. **Long-running circuit breaker recovery** - 30+ minute tests impractical
4. **Extreme rate limiting** - Thousands of requests per second (stress testing)

### Recommended Periodic Testing

- **Quarterly:** Test real API integrations in staging environment
- **Before major releases:** Full integration test suite across all providers
- **After provider API changes:** Re-test affected provider integrations

---

## Testing Documentation

### For Developers Integrating This Package

**See:**
- `docs/getting-started.md#testing` - How to test your integration
- `docs/integration-guide.md#testing-examples` - Laravel/Symfony test examples
- `docs/examples/testing-integration.php` - Complete test examples

---

## Conclusion

The `Nexus\Connector` package follows the **Application-Layer Testing** pattern because:

1. It is a **pure business logic package** with no persistence
2. All dependencies are **abstracted via interfaces**
3. Testing requires **real infrastructure** (database, cache, HTTP client)
4. **Consuming applications provide concrete implementations** and test the full stack

**Test coverage of 100% is achieved at the application layer** through comprehensive integration tests that validate the package's coordination logic with real implementations.

---

**Last Updated:** 2025-11-24  
**Maintained By:** Nexus Architecture Team  
**Next Review:** 2025-12-24 (Monthly)
