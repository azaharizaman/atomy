# Nexus\Crypto

Framework-agnostic cryptographic abstraction layer providing **algorithm agility** and **post-quantum readiness** for the Nexus ERP ecosystem.

## Overview

The **Nexus\Crypto** package provides a complete cryptographic primitive library designed for long-term security and future-proofing against quantum computing threats. By abstracting all cryptographic operations behind interfaces, the package enables seamless migration from classical algorithms (RSA, ECDSA, AES) to post-quantum alternatives (Dilithium, Kyber) simply by changing bindings in the application layer.

### Core Principles

1. **Algorithm Agility**: All crypto operations via interfaces, enabling algorithm swapping without code changes
2. **Post-Quantum Readiness**: Hybrid mode support planned for dual classical + PQC signing/encryption
3. **Stateless Design**: All persistence via `KeyStorageInterface` - no framework dependencies
4. **Immutable Value Objects**: All cryptographic results as readonly data structures
5. **Audit Trail**: All operations compatible with `Nexus\AuditLogger` for compliance

## Features

### Phase 1 (Current - Classical Algorithms)

- **Hashing**: SHA-256/384/512, BLAKE2b via Sodium
- **Symmetric Encryption**: AES-256-GCM (authenticated), ChaCha20-Poly1305
- **Asymmetric Signing**: Ed25519, RSA-2048/4096, ECDSA P-256, HMAC-SHA256
- **Key Management**: Envelope encryption, automated rotation via Scheduler
- **Feature Flags**: Gradual rollout support with `CRYPTO_LEGACY_MODE`

### Phase 2 (Planned - Hybrid PQC)

- **Hybrid Signing**: Dual ECDSA + Dilithium signatures
- **Hybrid KEM**: Dual X25519 + Kyber key encapsulation
- **Transparent Migration**: Existing code works unchanged

### Phase 3 (Future - Pure PQC)

- **Pure PQC Algorithms**: ML-DSA, ML-KEM (NIST standards)
- **Classical Deprecation**: Full quantum-resistant operation

## Installation

### 1. Install Package

```bash
composer require nexus/crypto:*@dev
```

### 2. Verify Extensions

```bash
php -m | grep -E "(sodium|openssl)"
```

### 3. Implement Required Contracts in Your Application

```php
// app/Services/LaravelKeyStorage.php
class LaravelKeyStorage implements KeyStorageInterface
{
    public function store(string $keyId, EncryptionKey $key): void { /* ... */ }
    public function retrieve(string $keyId): EncryptionKey { /* ... */ }
    // ... other methods
}
```

### 4. Bind Interfaces in Service Provider

```php
// app/Providers/CryptoServiceProvider.php
public function register(): void
{
    // Core crypto services
    $this->app->singleton(HasherInterface::class, NativeHasher::class);
    $this->app->singleton(SymmetricEncryptorInterface::class, SodiumEncryptor::class);
    $this->app->singleton(AsymmetricSignerInterface::class, SodiumSigner::class);
    $this->app->singleton(KeyGeneratorInterface::class, KeyGenerator::class);
    
    // Key storage
    $this->app->singleton(KeyStorageInterface::class, LaravelKeyStorage::class);
    
    // Facade
    $this->app->singleton(CryptoManager::class);
    
    // Register key rotation handler for Scheduler
    $this->app->tag([KeyRotationHandler::class], 'scheduler.handlers');
}
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      DOMAIN PACKAGE                              │
│  (e.g., Nexus\Export, Nexus\Connector)                          │
│                                                                   │
│  Calls: $crypto->encrypt(data)                                  │
│  Calls: $crypto->sign(data)                                     │
│  Consumes: HashResult, EncryptedData, SignedData                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   NEXUS\CRYPTO (THIS PACKAGE)                   │
│                                                                   │
│  CryptoManager ──► HasherInterface                              │
│         │          SymmetricEncryptorInterface                   │
│         │          AsymmetricSignerInterface                     │
│         │          KeyGeneratorInterface                         │
│         └──► KeyStorageInterface                                │
│                                                                   │
│  Enums: HashAlgorithm, SymmetricAlgorithm, AsymmetricAlgorithm │
│  Value Objects: HashResult, EncryptedData, SignedData, KeyPair │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                            │
│                      (Nexus\Atomy)                               │
│                                                                   │
│  LaravelKeyStorage ──► Eloquent Model (encryption_keys)         │
│  SodiumEncryptor ──► ext-sodium functions                       │
│  KeyRotationHandler ──► Nexus\Scheduler integration            │
└─────────────────────────────────────────────────────────────────┘
```

## Usage

### 1. Hash Data (Integrity Checking)

```php
use Nexus\Crypto\Services\CryptoManager;
use Nexus\Crypto\Enums\HashAlgorithm;

$crypto = app(CryptoManager::class);

// Hash with default SHA-256
$result = $crypto->hash('sensitive data');
// HashResult(hash: '5d41...', algorithm: SHA256, salt: null)

// Hash with specific algorithm
$result = $crypto->hash('sensitive data', HashAlgorithm::BLAKE2B);

// Verify hash
if ($crypto->verifyHash('sensitive data', $result)) {
    // Data integrity confirmed
}
```

### 2. Encrypt Data (Symmetric Encryption)

```php
use Nexus\Crypto\Enums\SymmetricAlgorithm;

// Encrypt with default AES-256-GCM
$encrypted = $crypto->encrypt('confidential information');
// EncryptedData(
//   ciphertext: '8f3a...',
//   iv: '4b2c...',
//   tag: '9d1e...',
//   algorithm: AES256GCM,
//   metadata: []
// )

// Decrypt
$plaintext = $crypto->decrypt($encrypted);

// Encrypt with specific key
$keyId = 'tenant-123-finance';
$encrypted = $crypto->encryptWithKey('payroll data', $keyId);
$plaintext = $crypto->decryptWithKey($encrypted, $keyId);
```

### 3. Sign Data (Digital Signatures)

```php
use Nexus\Crypto\Enums\AsymmetricAlgorithm;

// Generate key pair
$keyPair = $crypto->generateKeyPair(AsymmetricAlgorithm::ED25519);

// Sign data
$signed = $crypto->sign('financial report', $keyPair->privateKey);
// SignedData(
//   data: 'financial report',
//   signature: 'a8f3...',
//   algorithm: ED25519,
//   publicKey: '7b2c...'
// )

// Verify signature
if ($crypto->verifySignature($signed)) {
    // Signature valid - data authentic and unmodified
}
```

### 4. HMAC Signing (Webhook Verification)

```php
// Generate HMAC signature
$signature = $crypto->hmac($payload, $secret);

// Verify HMAC
if ($crypto->verifyHmac($payload, $signature, $secret)) {
    // Webhook authentic
}
```

### 5. Key Rotation (Automated via Scheduler)

```php
// Manual rotation
$newKey = $crypto->rotateKey('tenant-123-finance');

// Automated rotation (configured in CryptoServiceProvider)
// KeyRotationHandler checks all keys daily and rotates expired ones
// Based on expiresAt field in EncryptionKey value object
```

## Value Objects

### HashResult

```php
final readonly class HashResult
{
    public function __construct(
        public string $hash,              // Hex-encoded hash
        public HashAlgorithm $algorithm,  // Algorithm used
        public ?string $salt = null       // Optional salt (for KDF)
    ) {}
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### EncryptedData

```php
final readonly class EncryptedData
{
    public function __construct(
        public string $ciphertext,               // Base64-encoded encrypted data
        public string $iv,                       // Initialization vector
        public string $tag,                      // Authentication tag (GCM/Poly1305)
        public SymmetricAlgorithm $algorithm,    // Algorithm used
        public array $metadata = []              // Additional context
    ) {}
    
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### SignedData

```php
final readonly class SignedData
{
    public function __construct(
        public string $data,                    // Original data
        public string $signature,               // Signature bytes
        public AsymmetricAlgorithm $algorithm,  // Algorithm used
        public ?string $publicKey = null        // Public key (optional)
    ) {}
    
    public function isQuantumResistant(): bool; // Check if PQC algorithm
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### KeyPair

```php
final readonly class KeyPair
{
    public function __construct(
        public string $publicKey,               // Base64-encoded public key
        public string $privateKey,              // Base64-encoded private key
        public AsymmetricAlgorithm $algorithm   // Algorithm used
    ) {}
}
```

### EncryptionKey

```php
final readonly class EncryptionKey
{
    public function __construct(
        public string $key,                     // Base64-encoded key material
        public SymmetricAlgorithm $algorithm,   // Algorithm
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $expiresAt = null
    ) {}
    
    public function isExpired(ClockInterface $clock): bool;
}
```

## Enums

### HashAlgorithm

```php
enum HashAlgorithm: string
{
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    case BLAKE2B = 'blake2b';
    
    public function isQuantumResistant(): bool;
    public function getSecurityLevel(): int; // Bits (e.g., 256)
}
```

### SymmetricAlgorithm

```php
enum SymmetricAlgorithm: string
{
    case AES256GCM = 'aes-256-gcm';
    case AES256CBC = 'aes-256-cbc';
    case CHACHA20POLY1305 = 'chacha20-poly1305';
    
    public function isAuthenticated(): bool; // GCM/Poly1305 = true
    public function requiresIV(): bool;
    public function getKeyLength(): int; // Bytes
}
```

### AsymmetricAlgorithm

```php
enum AsymmetricAlgorithm: string
{
    case HMACSHA256 = 'hmac-sha256';
    case ED25519 = 'ed25519';
    case RSA2048 = 'rsa-2048';
    case RSA4096 = 'rsa-4096';
    case ECDSAP256 = 'ecdsa-p256';
    
    // Phase 2 (stubs for now)
    case DILITHIUM3 = 'dilithium3';
    case KYBER768 = 'kyber768';
    
    public function isQuantumResistant(): bool;
    public function getSecurityLevel(): int;
}
```

## Security Considerations

### Envelope Encryption

All data encryption keys (DEKs) are encrypted with the master key (`APP_KEY`) before storage:

```
Plaintext → Encrypt with DEK → Ciphertext
DEK → Encrypt with Master Key → Encrypted DEK (stored in database)
```

### Key Rotation

- **Automated**: `KeyRotationHandler` runs daily via `Nexus\Scheduler`
- **Frequency**: 90-day default (configurable)
- **Audit Trail**: All rotations logged to `key_rotation_history`
- **Zero Downtime**: Old keys retained for decryption of existing data

### Algorithm Selection

```php
// Recommended defaults (Phase 1)
HashAlgorithm::SHA256           // General hashing
SymmetricAlgorithm::AES256GCM   // Data encryption (authenticated)
AsymmetricAlgorithm::ED25519    // Digital signatures (fast)

// Legacy support
SymmetricAlgorithm::AES256CBC   // Backward compatibility
AsymmetricAlgorithm::RSA2048    // Standards compliance
```

## Migration from Legacy Code

### Feature Flag Approach

```php
// config/crypto.php
return [
    'legacy_mode' => env('CRYPTO_LEGACY_MODE', true),
];

// In consuming package
if (config('crypto.legacy_mode')) {
    // Use old direct hash() calls
    $checksum = hash('sha256', $data);
} else {
    // Use Nexus\Crypto
    $result = $crypto->hash($data);
    $checksum = $result->hash;
}
```

### Refactoring Checklist

1. ✅ Update package to inject `CryptoManager` (optional dependency)
2. ✅ Add feature flag check in relevant methods
3. ✅ Implement new crypto path using interfaces
4. ✅ Deploy with `CRYPTO_LEGACY_MODE=true` (safe)
5. ✅ Test in staging with `CRYPTO_LEGACY_MODE=false`
6. ✅ Roll out to production gradually
7. ✅ Remove legacy code path after full migration

## Error Handling

```php
use Nexus\Crypto\Exceptions\{
    EncryptionException,
    DecryptionException,
    SignatureException,
    InvalidKeyException,
    UnsupportedAlgorithmException,
    FeatureNotImplementedException
};

try {
    $encrypted = $crypto->encrypt($data);
} catch (EncryptionException $e) {
    // Handle encryption failure
    $logger->error('Encryption failed', ['error' => $e->getMessage()]);
}

try {
    $signed = $hybridSigner->sign($data); // Phase 2 feature
} catch (FeatureNotImplementedException $e) {
    // Fall back to classical signing
    $signed = $crypto->sign($data);
}
```

## Performance

### Benchmarks (Phase 1 - Classical Algorithms)

| Operation | Algorithm | Input Size | Target | Actual |
|-----------|-----------|------------|--------|--------|
| Hash | SHA-256 | 1 KB | < 1ms | ~0.3ms |
| Hash | BLAKE2b | 1 KB | < 1ms | ~0.2ms |
| Encrypt | AES-256-GCM | 1 KB | < 2ms | ~0.8ms |
| Decrypt | AES-256-GCM | 1 KB | < 2ms | ~0.9ms |
| Sign | Ed25519 | 1 KB | < 5ms | ~1.2ms |
| Verify | Ed25519 | 1 KB | < 5ms | ~1.5ms |

*Benchmarks on PHP 8.3, ext-sodium 2.0.23*

## Roadmap

### ✅ Phase 1: Classical Algorithms (Q4 2025)

- [x] Core interfaces and value objects
- [x] Sodium-based implementations
- [x] Key rotation handler
- [x] Feature flag support
- [x] Legacy code refactoring

### 🔄 Phase 2: Hybrid PQC Mode (Q3 2026)

- [ ] Monitor liboqs-php maturity
- [ ] Implement `HybridSignerInterface`
- [ ] Implement `HybridKEMInterface`
- [ ] Dual signature verification
- [ ] Migration tooling

### 🔮 Phase 3: Pure PQC (Post-2027)

- [ ] NIST ML-DSA/ML-KEM standards finalized
- [ ] Pure PQC implementations
- [ ] Classical algorithm deprecation
- [ ] Performance optimization

## Contributing

This package follows the Nexus architecture principles:

- **Framework-agnostic**: No Laravel dependencies in `src/`
- **Contract-driven**: All persistence via interfaces
- **Immutable**: Readonly value objects only
- **Testable**: All dependencies injected

## License

MIT License - see LICENSE file for details.

## Security Disclosure

For security vulnerabilities, please email: azahari@nexusnv.net (DO NOT file public issues)
