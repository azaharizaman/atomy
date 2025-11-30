# Integration Guide: Connector

This guide shows how to integrate the Connector package into your application framework.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/connector:"*@dev"
```

### Step 2: Create Database Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('service_name', 100)->index();
            $table->string('endpoint', 500);
            $table->string('method', 10);
            $table->string('status', 20)->index();
            $table->integer('http_status_code')->nullable();
            $table->integer('duration_ms');
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->timestamp('created_at')->index();
            
            $table->index(['service_name', 'status', 'created_at']);
        });
        
        Schema::create('connector_credentials', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->nullable();
            $table->string('service_name', 100);
            $table->string('auth_method', 20);
            $table->text('credential_data'); // Encrypted
            $table->timestamp('expires_at')->nullable();
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['service_name', 'tenant_id']);
        });
    }
};
```

### Step 3: Create Eloquent Models

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id', 'tenant_id', 'service_name', 'endpoint', 'method',
        'status', 'http_status_code', 'duration_ms', 'request_data',
        'response_data', 'error_message', 'attempt_number'
    ];
    
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];
    
    const UPDATED_AT = null; // No updated_at column
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectorCredential extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id', 'tenant_id', 'service_name', 'auth_method',
        'credential_data', 'expires_at', 'refresh_token', 'is_active'
    ];
    
    protected $casts = [
        'credential_data' => 'encrypted:array',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
```

### Step 4: Implement Storage Interfaces

```php
<?php
// app/Repositories/RedisCircuitBreakerStorage.php

namespace App\Repositories;

use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Connector\ValueObjects\CircuitBreakerState;
use Illuminate\Support\Facades\Redis;

final readonly class RedisCircuitBreakerStorage implements CircuitBreakerStorageInterface
{
    public function getState(string $serviceName): CircuitBreakerState
    {
        $data = Redis::get("circuit:{$serviceName}");
        
        if (!$data) {
            return CircuitBreakerState::closed();
        }
        
        return CircuitBreakerState::fromArray(json_decode($data, true));
    }
    
    public function saveState(string $serviceName, CircuitBreakerState $state): void
    {
        Redis::setex(
            "circuit:{$serviceName}",
            3600,
            json_encode($state->toArray())
        );
    }
}
```

```php
<?php
// app/Repositories/RedisRateLimiterStorage.php

namespace App\Repositories;

use Nexus\Connector\Contracts\RateLimiterStorageInterface;
use Illuminate\Support\Facades\Redis;

final readonly class RedisRateLimiterStorage implements RateLimiterStorageInterface
{
    public function getTokens(string $key): int
    {
        return (int) Redis::get("ratelimit:{$key}") ?: 0;
    }
    
    public function consumeTokens(string $key, int $tokens = 1): bool
    {
        $current = $this->getTokens($key);
        
        if ($current < $tokens) {
            return false;
        }
        
        Redis::decrby("ratelimit:{$key}", $tokens);
        return true;
    }
    
    public function refillTokens(string $key, int $tokens, int $maxTokens): void
    {
        $current = $this->getTokens($key);
        $newTokens = min($current + $tokens, $maxTokens);
        
        Redis::setex("ratelimit:{$key}", 60, $newTokens);
    }
}
```

```php
<?php
// app/Repositories/DbIntegrationLogger.php

namespace App\Repositories;

use Nexus\Connector\Contracts\IntegrationLoggerInterface;
use Nexus\Connector\ValueObjects\IntegrationLog;
use App\Models\IntegrationLog as IntegrationLogModel;
use Symfony\Component\Uid\Ulid;

final readonly class DbIntegrationLogger implements IntegrationLoggerInterface
{
    public function log(IntegrationLog $log): void
    {
        IntegrationLogModel::create([
            'id' => (string) new Ulid(),
            'tenant_id' => $log->tenantId,
            'service_name' => $log->serviceName,
            'endpoint' => $log->endpoint,
            'method' => $log->method->value,
            'status' => $log->status->value,
            'http_status_code' => $log->httpStatusCode,
            'duration_ms' => $log->durationMs,
            'request_data' => $log->requestData,
            'response_data' => $log->responseData,
            'error_message' => $log->errorMessage,
            'attempt_number' => $log->attemptNumber,
        ]);
    }
    
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $query = IntegrationLogModel::query();
        
        if (isset($filters['service_name'])) {
            $query->where('service_name', $filters['service_name']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->limit($limit)->offset($offset)->get()->all();
    }
    
    public function getMetrics(string $serviceName, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $logs = IntegrationLogModel::where('service_name', $serviceName)
            ->whereBetween('created_at', [$from, $to])
            ->get();
        
        $success = $logs->where('status', 'success')->count();
        $failed = $logs->where('status', '!=', 'success')->count();
        $total = $logs->count();
        
        return [
            'success_count' => $success,
            'failure_count' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
            'avg_duration_ms' => round($logs->avg('duration_ms'), 2),
        ];
    }
}
```

### Step 5: Create Vendor Adapters

```php
<?php
// app/Connectors/Adapters/SendGridEmailAdapter.php

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use SendGrid;
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
        
        $sendgrid = new SendGrid($this->apiKey);
        $response = $sendgrid->send($email);
        
        return $response->statusCode() === 202;
    }
    
    public function sendBulkEmail(array $emails, array $options = []): array
    {
        // Implement bulk sending
        return ['sent' => 0, 'failed' => 0, 'errors' => []];
    }
    
    public function validateAddress(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function getStatistics(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
```

### Step 6: Create Service Provider

```php
<?php
// app/Providers/ConnectorServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Connector\Contracts\RateLimiterStorageInterface;
use Nexus\Connector\Contracts\IntegrationLoggerInterface;
use App\Connectors\Adapters\SendGridEmailAdapter;
use App\Repositories\RedisCircuitBreakerStorage;
use App\Repositories\RedisRateLimiterStorage;
use App\Repositories\DbIntegrationLogger;

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
        
        $this->app->singleton(
            IntegrationLoggerInterface::class,
            DbIntegrationLogger::class
        );
        
        // Bind email connector
        $this->app->singleton(
            EmailServiceConnectorInterface::class,
            fn() => new SendGridEmailAdapter(
                apiKey: config('services.sendgrid.api_key'),
                fromEmail: config('mail.from.address'),
                fromName: config('mail.from.name')
            )
        );
    }
}
```

### Step 7: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\ConnectorServiceProvider::class,
],
```

### Step 8: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function __construct(
        private readonly EmailServiceConnectorInterface $emailConnector
    ) {}
    
    public function sendWelcome(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
        ]);
        
        $this->emailConnector->sendTransactionalEmail(
            recipient: $validated['email'],
            subject: 'Welcome!',
            body: view('emails.welcome', ['name' => $validated['name']])->render()
        );
        
        return response()->json(['message' => 'Email sent']);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/connector:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php
// src/Entity/IntegrationLog.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'integration_logs')]
class IntegrationLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 100)]
    private string $serviceName;
    
    #[ORM\Column(type: 'string', length: 500)]
    private string $endpoint;
    
    // ... other properties
}
```

### Step 3: Configure Services

`config/services.yaml`:

```yaml
services:
    # Storage implementations
    App\Repository\RedisCircuitBreakerStorage: ~
    
    App\Repository\RedisRateLimiterStorage: ~
    
    App\Repository\DbIntegrationLogger: ~
    
    # Bind interfaces
    Nexus\Connector\Contracts\CircuitBreakerStorageInterface:
        alias: App\Repository\RedisCircuitBreakerStorage
        
    Nexus\Connector\Contracts\RateLimiterStorageInterface:
        alias: App\Repository\RedisRateLimiterStorage
        
    Nexus\Connector\Contracts\IntegrationLoggerInterface:
        alias: App\Repository\DbIntegrationLogger
        
    # Email adapter
    App\Connector\Adapters\SendGridEmailAdapter:
        arguments:
            $apiKey: '%env(SENDGRID_API_KEY)%'
            $fromEmail: '%env(MAIL_FROM_ADDRESS)%'
            $fromName: '%env(MAIL_FROM_NAME)%'
            
    Nexus\Connector\Contracts\EmailServiceConnectorInterface:
        alias: App\Connector\Adapters\SendGridEmailAdapter
```

### Step 4: Use in Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;

class WelcomeController extends AbstractController
{
    public function __construct(
        private readonly EmailServiceConnectorInterface $emailConnector
    ) {}
    
    public function send(): JsonResponse
    {
        $this->emailConnector->sendTransactionalEmail(
            recipient: 'user@example.com',
            subject: 'Welcome!',
            body: '<h1>Welcome!</h1>'
        );
        
        return $this->json(['message' => 'Email sent']);
    }
}
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT
public function __construct(
    private readonly EmailServiceConnectorInterface $emailConnector
) {}

// ❌ WRONG
public function __construct(
    private readonly SendGridEmailAdapter $sendGrid  // Concrete class!
) {}
```

### Pattern 2: Error Handling

```php
use Nexus\Connector\Exceptions\CircuitBreakerOpenException;
use Nexus\Connector\Exceptions\ConnectionException;

try {
    $emailConnector->sendTransactionalEmail(...);
} catch (CircuitBreakerOpenException $e) {
    // Queue for later retry
    dispatch(new SendEmailJob(...))->delay(now()->addMinutes(5));
} catch (ConnectionException $e) {
    // All retries failed
    Log::error('Email failed', ['error' => $e->getMessage()]);
}
```

### Pattern 3: Multi-Tenant Isolation

```php
// Credentials are isolated by tenant
$credentials = $credentialProvider->getCredentials(
    serviceName: 'stripe',
    tenantId: $currentTenantId
);
```

---

## Performance Optimization

### Database Indexes

Always index integration_logs for efficient queries:

```php
$table->index(['service_name', 'created_at']);
$table->index(['service_name', 'status', 'created_at']);
$table->index('tenant_id');
```

### Redis Caching

Use Redis for circuit breaker and rate limiter state to ensure fast lookups across all workers.

---

## Testing

### Unit Testing

```php
use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use PHPUnit\Framework\TestCase;

class WelcomeServiceTest extends TestCase
{
    public function test_sends_welcome_email(): void
    {
        $emailConnector = $this->createMock(EmailServiceConnectorInterface::class);
        
        $emailConnector->expects($this->once())
            ->method('sendTransactionalEmail')
            ->with(
                $this->equalTo('user@example.com'),
                $this->equalTo('Welcome'),
                $this->anything()
            );
        
        $service = new WelcomeService($emailConnector);
        $service->sendWelcome('user@example.com', 'John');
    }
}
```

### Integration Testing (Laravel)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_email_is_sent_and_logged(): void
    {
        $response = $this->postJson('/api/welcome', [
            'email' => 'user@example.com',
            'name' => 'John',
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('integration_logs', [
            'service_name' => 'sendgrid',
            'status' => 'success',
        ]);
    }
}
```

---

## Troubleshooting

### Issue: Interface not bound

**Error:**
```
Target interface [Nexus\Connector\Contracts\EmailServiceConnectorInterface] is not instantiable.
```

**Solution:**
Ensure service provider is registered and interface is bound to concrete implementation.

### Issue: Circuit breaker always open

**Error:**
```
CircuitBreakerOpenException: Circuit breaker is open for service 'sendgrid'
```

**Solution:**
- Check if external service is actually down
- Verify credentials are correct
- Check integration logs for actual error
- Wait 60 seconds for circuit to half-open

### Issue: Rate limit exceeded

**Error:**
```
RateLimitExceededException: Rate limit exceeded for service 'stripe'
```

**Solution:**
Configure rate limit to match vendor quota:

```php
$endpoint = Endpoint::create('https://api.stripe.com/v1/charges')
    ->withRateLimit(RateLimitConfig::perSecond(100)); // Stripe limit
```
