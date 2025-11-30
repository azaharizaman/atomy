# Integration Guide: Crypto

Complete guide for integrating Nexus\Crypto into Laravel and Symfony applications.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/crypto:"*@dev"
```

### Step 2: Create Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encryption_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key_id')->unique(); // e.g., "tenant-123-payroll"
            $table->text('encrypted_key'); // Envelope-encrypted with master key
            $table->string('algorithm'); // e.g., "AES256GCM"
            $table->integer('version')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->ulid('tenant_id')->index();
            $table->timestamps();
            
            $table->index(['key_id', 'version']);
        });
        
        Schema::create('key_rotation_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key_id');
            $table->integer('old_version');
            $table->integer('new_version');
            $table->timestamp('rotated_at');
            $table->ulid('tenant_id')->index();
            
            $table->index('rotated_at');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('key_rotation_history');
        Schema::dropIfExists('encryption_keys');
    }
};
```

### Step 3: Create Eloquent Models

```php
<?php

namespace App\Models\Crypto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class EncryptionKey extends Model
{
    use HasUlids;
    
    protected $fillable = [
        'key_id',
        'encrypted_key',
        'algorithm',
        'version',
        'expires_at',
        'tenant_id',
    ];
    
    protected $casts = [
        'version' => 'integer',
        'expires_at' => 'datetime',
    ];
}

final class KeyRotationHistory extends Model
{
    use HasUlids;
    
    public $timestamps = false;
    
    protected $table = 'key_rotation_history';
    
    protected $fillable = [
        'key_id',
        'old_version',
        'new_version',
        'rotated_at',
        'tenant_id',
    ];
    
    protected $casts = [
        'old_version' => 'integer',
        'new_version' => 'integer',
        'rotated_at' => 'datetime',
    ];
}
```

### Step 4: Implement KeyStorageInterface

```php
<?php

namespace App\Services\Crypto;

use App\Models\Crypto\EncryptionKey as EncryptionKeyModel;
use App\Models\Crypto\KeyRotationHistory;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\InvalidKeyException;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class DatabaseKeyStorage implements KeyStorageInterface
{
    private const MASTER_KEY_ENV = 'CRYPTO_MASTER_KEY';
    
    public function __construct(
        private SymmetricEncryptorInterface $encryptor,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function store(string $keyId, EncryptionKey $key): void
    {
        // Envelope encryption: Encrypt the DEK with master key
        $masterKey = $this->getMasterKey();
        $encryptedKey = $this->encryptor->encryptWithKey(
            $key->key,
            $masterKey,
            SymmetricAlgorithm::AES256GCM
        );
        
        EncryptionKeyModel::create([
            'key_id' => $keyId,
            'encrypted_key' => $encryptedKey->toJson(),
            'algorithm' => $key->algorithm->value,
            'version' => $key->version,
            'expires_at' => $key->expiresAt,
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
        ]);
    }
    
    public function retrieve(string $keyId, ?int $version = null): EncryptionKey
    {
        $query = EncryptionKeyModel::where('key_id', $keyId)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId());
        
        if ($version !== null) {
            $query->where('version', $version);
        } else {
            $query->orderByDesc('version');
        }
        
        $record = $query->first();
        
        if (!$record) {
            throw InvalidKeyException::notFound($keyId);
        }
        
        // Decrypt DEK with master key
        $encryptedData = EncryptedData::fromJson($record->encrypted_key);
        $masterKey = $this->getMasterKey();
        $decryptedKey = $this->encryptor->decryptWithKey($encryptedData, $masterKey);
        
        return new EncryptionKey(
            key: $decryptedKey,
            algorithm: SymmetricAlgorithm::from($record->algorithm),
            version: $record->version,
            expiresAt: $record->expires_at
        );
    }
    
    public function rotate(string $keyId): EncryptionKey
    {
        $currentKey = $this->retrieve($keyId);
        
        if ($currentKey->isExpired()) {
            throw InvalidKeyException::expired($keyId);
        }
        
        // Generate new version
        $newVersion = $currentKey->version + 1;
        $newKeyValue = random_bytes(32); // 256 bits
        
        $newKey = new EncryptionKey(
            key: base64_encode($newKeyValue),
            algorithm: $currentKey->algorithm,
            version: $newVersion,
            expiresAt: new \DateTimeImmutable('+1 year')
        );
        
        // Store new key
        $this->store($keyId, $newKey);
        
        // Log rotation
        KeyRotationHistory::create([
            'key_id' => $keyId,
            'old_version' => $currentKey->version,
            'new_version' => $newVersion,
            'rotated_at' => now(),
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
        ]);
        
        return $newKey;
    }
    
    public function listExpiring(int $warningDays = 7): array
    {
        $threshold = now()->addDays($warningDays);
        
        return EncryptionKeyModel::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', now())
            ->pluck('key_id')
            ->toArray();
    }
    
    private function getMasterKey(): string
    {
        $masterKey = env(self::MASTER_KEY_ENV);
        
        if (!$masterKey) {
            throw new \RuntimeException('CRYPTO_MASTER_KEY not configured');
        }
        
        return $masterKey;
    }
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use App\Services\Crypto\DatabaseKeyStorage;
use Illuminate\Support\ServiceProvider;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\Services\NativeHasher;
use Nexus\Crypto\Services\SodiumEncryptor;
use Nexus\Crypto\Services\SodiumSigner;

final class CryptoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->singleton(HasherInterface::class, NativeHasher::class);
        $this->app->singleton(SymmetricEncryptorInterface::class, SodiumEncryptor::class);
        $this->app->singleton(AsymmetricSignerInterface::class, SodiumSigner::class);
        $this->app->singleton(KeyGeneratorInterface::class, KeyGenerator::class);
        $this->app->singleton(KeyStorageInterface::class, DatabaseKeyStorage::class);
        
        // Bind facade
        $this->app->singleton(CryptoManager::class);
    }
}
```

### Step 6: Register Provider

```php
// config/app.php
'providers' => [
    // ... other providers
    App\Providers\CryptoServiceProvider::class,
],
```

### Step 7: Set Environment Variables

```bash
# .env
CRYPTO_MASTER_KEY=base64:your-256-bit-master-key-here
```

Generate master key:

```bash
php artisan tinker
>>> echo 'base64:' . base64_encode(random_bytes(32));
```

### Step 8: Usage in Application

```php
<?php

namespace App\Services;

use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\ValueObjects\EncryptedData;

final readonly class EmployeeService
{
    public function __construct(
        private CryptoManager $crypto
    ) {}
    
    public function encryptSalary(int $employeeId, float $salary): string
    {
        $encrypted = $this->crypto->encryptWithKey(
            (string) $salary,
            "employee-salary-{$employeeId}"
        );
        
        return $encrypted->toJson();
    }
    
    public function decryptSalary(string $encryptedJson): float
    {
        $encrypted = EncryptedData::fromJson($encryptedJson);
        $decrypted = $this->crypto->decrypt($encrypted);
        
        return (float) $decrypted;
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/crypto:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php

namespace App\Entity\Crypto;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'encryption_keys')]
#[ORM\Index(columns: ['key_id', 'version'])]
class EncryptionKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private string $id;
    
    #[ORM\Column(type: 'string', unique: true)]
    private string $keyId;
    
    #[ORM\Column(type: 'text')]
    private string $encryptedKey;
    
    #[ORM\Column(type: 'string')]
    private string $algorithm;
    
    #[ORM\Column(type: 'integer')]
    private int $version = 1;
    
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;
    
    #[ORM\Column(type: 'ulid')]
    private string $tenantId;
    
    // ... getters and setters
}
```

### Step 3: Implement KeyStorageInterface

```php
<?php

namespace App\Service\Crypto;

use App\Entity\Crypto\EncryptionKey as EncryptionKeyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\InvalidKeyException;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class DoctrineKeyStorage implements KeyStorageInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SymmetricEncryptorInterface $encryptor,
        private TenantContextInterface $tenantContext,
        private ParameterBagInterface $params
    ) {}
    
    public function store(string $keyId, EncryptionKey $key): void
    {
        $masterKey = $this->params->get('crypto.master_key');
        $encryptedKey = $this->encryptor->encryptWithKey(
            $key->key,
            $masterKey,
            SymmetricAlgorithm::AES256GCM
        );
        
        $entity = new EncryptionKeyEntity();
        $entity->setKeyId($keyId);
        $entity->setEncryptedKey($encryptedKey->toJson());
        $entity->setAlgorithm($key->algorithm->value);
        $entity->setVersion($key->version);
        $entity->setExpiresAt($key->expiresAt);
        $entity->setTenantId($this->tenantContext->getCurrentTenantId());
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
    
    public function retrieve(string $keyId, ?int $version = null): EncryptionKey
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('k')
            ->from(EncryptionKeyEntity::class, 'k')
            ->where('k.keyId = :keyId')
            ->andWhere('k.tenantId = :tenantId')
            ->setParameter('keyId', $keyId)
            ->setParameter('tenantId', $this->tenantContext->getCurrentTenantId());
        
        if ($version !== null) {
            $qb->andWhere('k.version = :version')
                ->setParameter('version', $version);
        } else {
            $qb->orderBy('k.version', 'DESC')
                ->setMaxResults(1);
        }
        
        $entity = $qb->getQuery()->getOneOrNullResult();
        
        if (!$entity) {
            throw InvalidKeyException::notFound($keyId);
        }
        
        $encryptedData = EncryptedData::fromJson($entity->getEncryptedKey());
        $masterKey = $this->params->get('crypto.master_key');
        $decryptedKey = $this->encryptor->decryptWithKey($encryptedData, $masterKey);
        
        return new EncryptionKey(
            key: $decryptedKey,
            algorithm: SymmetricAlgorithm::from($entity->getAlgorithm()),
            version: $entity->getVersion(),
            expiresAt: $entity->getExpiresAt()
        );
    }
    
    public function rotate(string $keyId): EncryptionKey
    {
        // Similar to Laravel implementation
    }
    
    public function listExpiring(int $warningDays = 7): array
    {
        // Similar to Laravel implementation
    }
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml
services:
    # Crypto interfaces
    Nexus\Crypto\Contracts\HasherInterface:
        class: Nexus\Crypto\Services\NativeHasher
    
    Nexus\Crypto\Contracts\SymmetricEncryptorInterface:
        class: Nexus\Crypto\Services\SodiumEncryptor
        arguments:
            $keyStorage: '@Nexus\Crypto\Contracts\KeyStorageInterface'
    
    Nexus\Crypto\Contracts\AsymmetricSignerInterface:
        class: Nexus\Crypto\Services\SodiumSigner
    
    Nexus\Crypto\Contracts\KeyGeneratorInterface:
        class: Nexus\Crypto\Services\KeyGenerator
    
    Nexus\Crypto\Contracts\KeyStorageInterface:
        class: App\Service\Crypto\DoctrineKeyStorage
    
    # Crypto manager facade
    Nexus\Crypto\Services\CryptoManager:
        arguments:
            $hasher: '@Nexus\Crypto\Contracts\HasherInterface'
            $encryptor: '@Nexus\Crypto\Contracts\SymmetricEncryptorInterface'
            $signer: '@Nexus\Crypto\Contracts\AsymmetricSignerInterface'
            $keyGenerator: '@Nexus\Crypto\Contracts\KeyGeneratorInterface'
```

### Step 5: Configure Parameters

```yaml
# config/parameters.yaml
parameters:
    crypto.master_key: '%env(CRYPTO_MASTER_KEY)%'
```

```bash
# .env
CRYPTO_MASTER_KEY=base64:your-256-bit-master-key-here
```

---

## Common Integration Patterns

### Pattern 1: Encrypted Model Attribute

```php
// Trait for automatic encryption/decryption
trait HasEncryptedAttributes
{
    protected function encryptAttribute(string $value, string $keyId): string
    {
        $crypto = app(CryptoManager::class);
        $encrypted = $crypto->encryptWithKey($value, $keyId);
        return $encrypted->toJson();
    }
    
    protected function decryptAttribute(string $encryptedJson): string
    {
        $crypto = app(CryptoManager::class);
        $encrypted = EncryptedData::fromJson($encryptedJson);
        return $crypto->decrypt($encrypted);
    }
}

// Usage in model
class Employee extends Model
{
    use HasEncryptedAttributes;
    
    public function setSalaryAttribute(float $value): void
    {
        $this->attributes['salary'] = $this->encryptAttribute(
            (string) $value,
            "employee-salary-{$this->id}"
        );
    }
    
    public function getSalaryAttribute(): float
    {
        return (float) $this->decryptAttribute($this->attributes['salary']);
    }
}
```

### Pattern 2: Multi-Tenant Key Isolation

```php
// Always scope keys by tenant
final readonly class TenantKeyManager
{
    public function __construct(
        private CryptoManager $crypto,
        private TenantContextInterface $tenantContext
    ) {}
    
    private function getTenantKeyId(string $purpose): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        return "tenant-{$tenantId}-{$purpose}";
    }
    
    public function encryptPayrollData(string $data): EncryptedData
    {
        return $this->crypto->encryptWithKey(
            $data,
            $this->getTenantKeyId('payroll')
        );
    }
}
```

### Pattern 3: Exception Handling

```php
use Nexus\Crypto\Exceptions\DecryptionException;
use Nexus\Crypto\Exceptions\InvalidKeyException;

try {
    $decrypted = $crypto->decrypt($encrypted);
} catch (InvalidKeyException $e) {
    // Key not found or expired - rotate?
    Log::warning("Key not found: {$e->getMessage()}");
    throw new ServiceException('Data unavailable', previous: $e);
} catch (DecryptionException $e) {
    // Tampered data or wrong key
    Log::error("Decryption failed: {$e->getMessage()}");
    throw new SecurityException('Data integrity violation', previous: $e);
}
```

### Pattern 4: Performance Optimization

```php
// Cache decrypted master key in memory (single request)
final class CachedKeyStorage implements KeyStorageInterface
{
    private array $cache = [];
    
    public function __construct(
        private KeyStorageInterface $inner
    ) {}
    
    public function retrieve(string $keyId, ?int $version = null): EncryptionKey
    {
        $cacheKey = $keyId . ($version ?? 'latest');
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $key = $this->inner->retrieve($keyId, $version);
        $this->cache[$cacheKey] = $key;
        
        return $key;
    }
    
    // ... delegate other methods
}
```

---

## Testing Integration

### Unit Testing (Mock Crypto)

```php
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\Enums\SymmetricAlgorithm;

class EmployeeServiceTest extends TestCase
{
    public function test_encrypt_salary(): void
    {
        $mockEncryptor = $this->createMock(SymmetricEncryptorInterface::class);
        $mockEncryptor->expects($this->once())
            ->method('encryptWithKey')
            ->with('5000.00', 'employee-salary-123')
            ->willReturn(new EncryptedData(
                ciphertext: 'encrypted',
                iv: 'iv',
                tag: 'tag',
                algorithm: SymmetricAlgorithm::AES256GCM,
                keyVersion: 1
            ));
        
        $service = new EmployeeService($mockEncryptor);
        $result = $service->encryptSalary(123, 5000.00);
        
        $this->assertStringContainsString('encrypted', $result);
    }
}
```

### Integration Testing

```php
class CryptoIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_full_encryption_cycle(): void
    {
        $crypto = app(CryptoManager::class);
        
        $original = 'sensitive data';
        $encrypted = $crypto->encrypt($original);
        $decrypted = $crypto->decrypt($encrypted);
        
        $this->assertEquals($original, $decrypted);
    }
}
```

---

## Troubleshooting

### Issue: "CRYPTO_MASTER_KEY not configured"

**Solution:** Set environment variable:
```bash
CRYPTO_MASTER_KEY=base64:$(openssl rand -base64 32)
```

### Issue: "Key not found"

**Solution:** Initialize keys before first use:
```php
$generator = app(KeyGeneratorInterface::class);
$storage = app(KeyStorageInterface::class);

$key = new EncryptionKey(
    key: $generator->generateKey(),
    algorithm: SymmetricAlgorithm::AES256GCM,
    version: 1,
    expiresAt: new \DateTimeImmutable('+1 year')
);

$storage->store('my-key-id', $key);
```

### Issue: "Decryption failed"

**Possible causes:**
- Data tampered with (GCM tag mismatch)
- Wrong key version
- Master key changed

**Solution:** Check key version and audit logs.

---

## Next Steps

- Review [API Reference](api-reference.md) for method details
- Check [Examples](examples/) for code samples
- Read [Getting Started](getting-started.md) for core concepts
