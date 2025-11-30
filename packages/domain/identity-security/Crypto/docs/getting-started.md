# Getting Started with Nexus Crypto

## Prerequisites

- **PHP 8.3 or higher**
- **Composer**
- **PHP Extensions:**
  - `ext-sodium` - Required for modern algorithms (ChaCha20, Ed25519, BLAKE2b)
  - `ext-openssl` - Required for legacy algorithms (RSA, AES-CBC)

Verify extensions:
```bash
php -m | grep -E "(sodium|openssl)"
```

## Installation

```bash
composer require nexus/crypto
```

## When to Use This Package

This package is designed for:
- ✅ Encrypting sensitive data at rest (payroll, financial records, customer PII)
- ✅ Digital signatures for document integrity (invoices, financial statements)
- ✅ Hash-based data integrity verification (snapshots, checksums)
- ✅ Webhook authentication via HMAC signatures
- ✅ Secure key management with automated rotation
- ✅ Future-proofing against quantum computing threats

Do NOT use this package for:
- ❌ Password hashing (use `password_hash()` instead)
- ❌ SSL/TLS encryption (handled by web server)
- ❌ Session management (use framework session handlers)

---

## Core Concepts

### Concept 1: Algorithm Agility

All cryptographic operations are abstracted behind interfaces, enabling seamless algorithm migration:

```php
// Today: Classical Ed25519
$signer = new SodiumSigner();
$signature = $signer->sign($data);

// Tomorrow: Hybrid Classical + PQC (Phase 2)
$signer = new HybridSigner();
$signature = $signer->sign($data); // Dual signature (Ed25519 + Dilithium3)
```

No code changes required - just rebind the interface in your service container.

### Concept 2: Envelope Encryption

Data Encryption Keys (DEKs) are encrypted with a Master Key before storage:

```
Plaintext → Encrypt with DEK → Ciphertext
DEK → Encrypt with Master Key → Encrypted DEK (stored)
```

This pattern:
- Allows key rotation without re-encrypting all data
- Keeps the master key separate from encrypted data
- Enables per-tenant or per-entity key isolation

### Concept 3: Key Versioning

Keys have version numbers that increment on rotation:

```
tenant-123-finance:v1 (active 2024-01-01 to 2024-04-01)
tenant-123-finance:v2 (active 2024-04-01 onwards)
```

Old keys are retained for decryption of existing data, ensuring backward compatibility.

### Concept 4: Stateless Package Design

The package contains **zero persistence logic**. All storage is delegated to consuming applications via:

- `KeyStorageInterface` - Store/retrieve encryption keys
- PSR-3 `LoggerInterface` - Optional audit logging

This makes the package framework-agnostic and testable.

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires your application to implement `KeyStorageInterface` for key persistence:

```php
<?php

namespace App\Services\Crypto;

use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\ValueObjects\EncryptionKey;

final readonly class DatabaseKeyStorage implements KeyStorageInterface
{
    public function __construct(
        private \PDO $db,
        private string $masterKey // From environment (e.g., APP_KEY)
    ) {}
    
    public function store(string $keyId, EncryptionKey $key): void
    {
        // Encrypt DEK with master key before storing
        $encryptedKey = sodium_crypto_secretbox(
            $key->key,
            $this->generateNonce(),
            $this->masterKey
        );
        
        $stmt = $this->db->prepare("
            INSERT INTO encryption_keys (key_id, encrypted_key, algorithm, version, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $keyId,
            base64_encode($encryptedKey),
            $key->algorithm->value,
            $key->version,
            $key->expiresAt?->format('Y-m-d H:i:s')
        ]);
    }
    
    public function retrieve(string $keyId, ?int $version = null): EncryptionKey
    {
        $sql = "SELECT * FROM encryption_keys WHERE key_id = ?";
        
        if ($version !== null) {
            $sql .= " AND version = ?";
            $params = [$keyId, $version];
        } else {
            $sql .= " ORDER BY version DESC LIMIT 1";
            $params = [$keyId];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new \RuntimeException("Key not found: {$keyId}");
        }
        
        // Decrypt DEK with master key
        $decryptedKey = sodium_crypto_secretbox_open(
            base64_decode($row['encrypted_key']),
            $this->extractNonce($row['encrypted_key']),
            $this->masterKey
        );
        
        return new EncryptionKey(
            key: $decryptedKey,
            algorithm: SymmetricAlgorithm::from($row['algorithm']),
            version: $row['version'],
            expiresAt: $row['expires_at'] ? new \DateTimeImmutable($row['expires_at']) : null
        );
    }
    
    // Implement other interface methods...
}
```

### Step 2: Bind Interfaces in Service Container

#### Laravel Example

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Crypto\Contracts\{
    HasherInterface,
    SymmetricEncryptorInterface,
    AsymmetricSignerInterface,
    KeyGeneratorInterface,
    KeyStorageInterface
};
use Nexus\Crypto\Services\{
    NativeHasher,
    SodiumEncryptor,
    SodiumSigner,
    KeyGenerator,
    CryptoManager
};
use App\Services\Crypto\DatabaseKeyStorage;

class CryptoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind crypto services
        $this->app->singleton(HasherInterface::class, NativeHasher::class);
        $this->app->singleton(SymmetricEncryptorInterface::class, SodiumEncryptor::class);
        $this->app->singleton(AsymmetricSignerInterface::class, SodiumSigner::class);
        $this->app->singleton(KeyGeneratorInterface::class, KeyGenerator::class);
        
        // Bind key storage (your implementation)
        $this->app->singleton(KeyStorageInterface::class, function ($app) {
            return new DatabaseKeyStorage(
                db: $app->make('db')->getPdo(),
                masterKey: config('app.key')
            );
        });
        
        // Bind facade
        $this->app->singleton(CryptoManager::class);
        
        // Optional: Register key rotation handler with Scheduler
        $this->app->tag([KeyRotationHandler::class], 'scheduler.handlers');
    }
}
```

#### Symfony Example

```yaml
# config/services.yaml

services:
    # Crypto services
    Nexus\Crypto\Contracts\HasherInterface:
        class: Nexus\Crypto\Services\NativeHasher
        
    Nexus\Crypto\Contracts\SymmetricEncryptorInterface:
        class: Nexus\Crypto\Services\SodiumEncryptor
        
    Nexus\Crypto\Contracts\AsymmetricSignerInterface:
        class: Nexus\Crypto\Services\SodiumSigner
        
    Nexus\Crypto\Contracts\KeyGeneratorInterface:
        class: Nexus\Crypto\Services\KeyGenerator
        
    # Key storage (your implementation)
    Nexus\Crypto\Contracts\KeyStorageInterface:
        class: App\Services\Crypto\DatabaseKeyStorage
        arguments:
            $db: '@doctrine.orm.entity_manager'
            $masterKey: '%env(APP_KEY)%'
            
    # Facade
    Nexus\Crypto\Services\CryptoManager: ~
```

### Step 3: Create Database Schema

Your application needs tables for key storage:

```sql
-- Encryption keys with envelope encryption
CREATE TABLE encryption_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_id VARCHAR(191) NOT NULL,
    encrypted_key TEXT NOT NULL,
    algorithm VARCHAR(50) NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_key_version (key_id, version),
    INDEX idx_key_id (key_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Key rotation audit trail
CREATE TABLE key_rotation_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_id VARCHAR(191) NOT NULL,
    old_version INT UNSIGNED NOT NULL,
    new_version INT UNSIGNED NOT NULL,
    rotated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(100) NOT NULL DEFAULT 'scheduled_rotation',
    scheduled_job_id VARCHAR(26) NULL,
    notes TEXT NULL,
    
    INDEX idx_key_rotated (key_id, rotated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Your First Integration

### Example: Encrypt Employee Salary

```php
<?php

use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\Enums\SymmetricAlgorithm;

// Inject CryptoManager
final readonly class EmployeeService
{
    public function __construct(
        private CryptoManager $crypto
    ) {}
    
    public function storeSalary(string $employeeId, float $salary): void
    {
        // Generate tenant-specific key (first time only)
        $keyId = "tenant-{$this->tenantId}-payroll";
        
        if (!$this->crypto->keyExists($keyId)) {
            $this->crypto->generateEncryptionKey(
                keyId: $keyId,
                algorithm: SymmetricAlgorithm::AES256GCM,
                expirationDays: 90
            );
        }
        
        // Encrypt salary
        $encryptedSalary = $this->crypto->encryptWithKey(
            data: (string) $salary,
            keyId: $keyId
        );
        
        // Store encrypted data (ciphertext, IV, tag)
        $this->database->execute("
            UPDATE employees 
            SET encrypted_salary = ?,
                salary_iv = ?,
                salary_tag = ?,
                encryption_key_version = ?
            WHERE id = ?
        ", [
            $encryptedSalary->ciphertext,
            $encryptedSalary->iv,
            $encryptedSalary->tag,
            $encryptedSalary->keyVersion,
            $employeeId
        ]);
    }
    
    public function getSalary(string $employeeId): float
    {
        $row = $this->database->fetchOne("
            SELECT encrypted_salary, salary_iv, salary_tag, encryption_key_version
            FROM employees WHERE id = ?
        ", [$employeeId]);
        
        $encrypted = new EncryptedData(
            ciphertext: $row['encrypted_salary'],
            iv: $row['salary_iv'],
            tag: $row['salary_tag'],
            algorithm: SymmetricAlgorithm::AES256GCM,
            keyVersion: $row['encryption_key_version']
        );
        
        $keyId = "tenant-{$this->tenantId}-payroll";
        $decrypted = $this->crypto->decryptWithKey($encrypted, $keyId);
        
        return (float) $decrypted;
    }
}
```

---

## Next Steps

- **[API Reference](api-reference.md)** - Detailed documentation of all interfaces and value objects
- **[Integration Guide](integration-guide.md)** - Complete Laravel and Symfony examples
- **[Basic Usage Examples](examples/basic-usage.php)** - Common cryptographic operations
- **[Advanced Usage Examples](examples/advanced-usage.php)** - Key rotation, envelope encryption, hybrid PQC

---

## Troubleshooting

### Common Issues

**Issue 1: `ext-sodium` not found**
- **Cause:** Sodium extension not installed
- **Solution:**
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php8.3-sodium
  
  # macOS (Homebrew)
  brew install libsodium
  pecl install libsodium
  
  # Verify
  php -m | grep sodium
  ```

**Issue 2: `KeyStorageInterface` not bound**
- **Cause:** Missing service provider registration
- **Solution:** Ensure your `KeyStorage` implementation is bound in the service container

**Issue 3: Key rotation not executing**
- **Cause:** `KeyRotationHandler` not registered with Scheduler
- **Solution:** Tag the handler: `$this->app->tag([KeyRotationHandler::class], 'scheduler.handlers');`

**Issue 4: "Unsupported algorithm" exception**
- **Cause:** Using Phase 2 PQC algorithms (Dilithium3, Kyber768) which aren't implemented yet
- **Solution:** Use Phase 1 classical algorithms (Ed25519, RSA, AES-256-GCM)
