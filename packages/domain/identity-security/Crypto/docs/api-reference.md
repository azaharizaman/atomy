# API Reference: Crypto

Complete documentation of all interfaces, services, value objects, enums, and exceptions in the Nexus\Crypto package.

---

## Interfaces

### HasherInterface

**Location:** `src/Contracts/HasherInterface.php`

**Purpose:** Defines hashing operations for data integrity verification.

**Methods:**

#### hash()

```php
public function hash(
    string $data,
    HashAlgorithm $algorithm = HashAlgorithm::SHA256
): HashResult;
```

**Description:** Computes a cryptographic hash of the input data.

**Parameters:**
- `$data` (string) - Data to hash
- `$algorithm` (HashAlgorithm) - Algorithm to use (default: SHA-256)

**Returns:** `HashResult` - Hash result with algorithm metadata

**Example:**
```php
$hasher = app(HasherInterface::class);
$result = $hasher->hash('sensitive data', HashAlgorithm::BLAKE2B);
echo $result->hash; // "3f5a2..."
```

#### verifyHash()

```php
public function verifyHash(string $data, HashResult $expectedHash): bool;
```

**Description:** Verifies data matches expected hash using constant-time comparison.

**Parameters:**
- `$data` (string) - Data to verify
- `$expectedHash` (HashResult) - Expected hash result

**Returns:** `bool` - True if match, false otherwise

**Example:**
```php
$isValid = $hasher->verifyHash('test data', $storedHash);
```

---

### SymmetricEncryptorInterface

**Location:** `src/Contracts/SymmetricEncryptorInterface.php`

**Purpose:** Defines symmetric encryption operations for data at rest.

**Methods:**

#### encrypt()

```php
public function encrypt(
    string $data,
    SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM
): EncryptedData;
```

**Description:** Encrypts data using symmetric encryption with auto-generated key.

**Parameters:**
- `$data` (string) - Plaintext to encrypt
- `$algorithm` (SymmetricAlgorithm) - Encryption algorithm (default: AES-256-GCM)

**Returns:** `EncryptedData` - Encrypted data with IV and tag

**Throws:**
- `EncryptionException` - If encryption fails

**Example:**
```php
$encryptor = app(SymmetricEncryptorInterface::class);
$encrypted = $encryptor->encrypt('confidential data');
```

#### decrypt()

```php
public function decrypt(EncryptedData $encryptedData): string;
```

**Description:** Decrypts data using symmetric encryption.

**Parameters:**
- `$encryptedData` (EncryptedData) - Encrypted data to decrypt

**Returns:** `string` - Decrypted plaintext

**Throws:**
- `DecryptionException` - If decryption fails
- `InvalidKeyException` - If key not found

**Example:**
```php
$plaintext = $encryptor->decrypt($encrypted);
```

#### encryptWithKey()

```php
public function encryptWithKey(
    string $data,
    string $keyId,
    SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM
): EncryptedData;
```

**Description:** Encrypts data using a named key stored via KeyStorageInterface.

**Parameters:**
- `$data` (string) - Plaintext to encrypt
- `$keyId` (string) - Named key identifier (e.g., "tenant-123-payroll")
- `$algorithm` (SymmetricAlgorithm) - Encryption algorithm

**Returns:** `EncryptedData` - Encrypted data

**Example:**
```php
$encrypted = $encryptor->encryptWithKey('salary: 5000', 'tenant-123-payroll');
```

#### decryptWithKey()

```php
public function decryptWithKey(
    EncryptedData $encryptedData,
    string $keyId
): string;
```

**Description:** Decrypts data using a named key.

**Parameters:**
- `$encryptedData` (EncryptedData) - Encrypted data
- `$keyId` (string) - Named key identifier

**Returns:** `string` - Decrypted plaintext

---

### AsymmetricSignerInterface

**Location:** `src/Contracts/AsymmetricSignerInterface.php`

**Purpose:** Defines digital signature operations for data authentication.

**Methods:**

#### sign()

```php
public function sign(
    string $data,
    string $privateKey,
    AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
): SignedData;
```

**Description:** Creates a digital signature for data.

**Parameters:**
- `$data` (string) - Data to sign
- `$privateKey` (string) - Private key (base64 encoded)
- `$algorithm` (AsymmetricAlgorithm) - Signing algorithm

**Returns:** `SignedData` - Signature with metadata

**Throws:**
- `SignatureException` - If signing fails

**Example:**
```php
$signer = app(AsymmetricSignerInterface::class);
$signed = $signer->sign($document, $privateKey, AsymmetricAlgorithm::ED25519);
```

#### verifySignature()

```php
public function verifySignature(
    SignedData $signedData,
    string $publicKey
): bool;
```

**Description:** Verifies a digital signature using constant-time comparison.

**Parameters:**
- `$signedData` (SignedData) - Signed data with signature
- `$publicKey` (string) - Public key (base64 encoded)

**Returns:** `bool` - True if signature valid

**Example:**
```php
$isValid = $signer->verifySignature($signed, $publicKey);
```

#### hmac()

```php
public function hmac(string $data, string $secret): string;
```

**Description:** Generates HMAC-SHA256 signature for webhook authentication.

**Parameters:**
- `$data` (string) - Payload to sign
- `$secret` (string) - Shared secret

**Returns:** `string` - HMAC signature (hex)

**Example:**
```php
$signature = $signer->hmac($webhookPayload, $webhookSecret);
```

#### verifyHmac()

```php
public function verifyHmac(string $data, string $signature, string $secret): bool;
```

**Description:** Verifies HMAC signature using constant-time comparison.

---

### KeyGeneratorInterface

**Location:** `src/Contracts/KeyGeneratorInterface.php`

**Purpose:** Generates cryptographic keys for encryption and signing.

**Methods:**

#### generateKey()

```php
public function generateKey(
    SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM
): string;
```

**Description:** Generates a random symmetric encryption key.

**Parameters:**
- `$algorithm` (SymmetricAlgorithm) - Algorithm (determines key length)

**Returns:** `string` - Base64-encoded random key

**Example:**
```php
$generator = app(KeyGeneratorInterface::class);
$key = $generator->generateKey(SymmetricAlgorithm::CHACHA20POLY1305);
```

#### generateKeyPair()

```php
public function generateKeyPair(
    AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
): KeyPair;
```

**Description:** Generates an asymmetric key pair for digital signatures.

**Parameters:**
- `$algorithm` (AsymmetricAlgorithm) - Algorithm for key pair

**Returns:** `KeyPair` - Public and private key pair

**Example:**
```php
$keyPair = $generator->generateKeyPair(AsymmetricAlgorithm::RSA4096);
```

---

### KeyStorageInterface

**Location:** `src/Contracts/KeyStorageInterface.php`

**Purpose:** Defines persistence layer for encryption keys (implemented by consuming applications).

**Methods:**

#### store()

```php
public function store(string $keyId, EncryptionKey $key): void;
```

**Description:** Stores an encryption key with envelope encryption.

**Parameters:**
- `$keyId` (string) - Unique key identifier
- `$key` (EncryptionKey) - Key to store

**Implementation Note:** MUST encrypt the key with master key before storage.

#### retrieve()

```php
public function retrieve(string $keyId, ?int $version = null): EncryptionKey;
```

**Description:** Retrieves an encryption key.

**Parameters:**
- `$keyId` (string) - Key identifier
- `$version` (int|null) - Specific version, or latest if null

**Returns:** `EncryptionKey` - Decrypted key

**Throws:**
- `InvalidKeyException` - If key not found

#### rotate()

```php
public function rotate(string $keyId): EncryptionKey;
```

**Description:** Rotates a key (creates new version, retains old).

**Parameters:**
- `$keyId` (string) - Key to rotate

**Returns:** `EncryptionKey` - New key with incremented version

#### listExpiring()

```php
public function listExpiring(int $warningDays = 7): array;
```

**Description:** Lists keys expiring within specified days.

**Parameters:**
- `$warningDays` (int) - Days before expiration

**Returns:** `array<string>` - Array of key IDs

---

### HybridSignerInterface (Phase 2 - Not Implemented)

**Location:** `src/Contracts/HybridSignerInterface.php`

**Purpose:** Defines hybrid classical + post-quantum signing (dual signatures).

**Status:** ⏳ Planned for Q3 2026

**Methods:** All throw `FeatureNotImplementedException`

---

### HybridKEMInterface (Phase 2 - Not Implemented)

**Location:** `src/Contracts/HybridKEMInterface.php`

**Purpose:** Defines hybrid key encapsulation mechanism (classical + PQC).

**Status:** ⏳ Planned for Q3 2026

**Methods:** All throw `FeatureNotImplementedException`

---

## Services

### CryptoManager

**Location:** `src/Services/CryptoManager.php`

**Purpose:** Unified facade providing access to all cryptographic operations.

**Constructor Dependencies:**
- `HasherInterface` - Hashing service
- `SymmetricEncryptorInterface` - Encryption service
- `AsymmetricSignerInterface` - Signing service
- `KeyGeneratorInterface` - Key generation service

**Public Methods:**

All methods delegate to the corresponding interface. See interface documentation above for details.

**Example:**
```php
$crypto = app(CryptoManager::class);

// Hash
$hash = $crypto->hash('data');

// Encrypt
$encrypted = $crypto->encrypt('secret');

// Sign
$signed = $crypto->sign('document', $privateKey);
```

---

## Value Objects

### HashResult

**Location:** `src/ValueObjects/HashResult.php`

**Purpose:** Immutable container for hash results.

**Properties:**
- `hash` (string) - Hex-encoded hash
- `algorithm` (HashAlgorithm) - Algorithm used
- `salt` (string|null) - Optional salt (for future use)

**Example:**
```php
$result = new HashResult(
    hash: '5d41402abc...',
    algorithm: HashAlgorithm::SHA256
);
```

---

### EncryptedData

**Location:** `src/ValueObjects/EncryptedData.php`

**Purpose:** Immutable container for encrypted data.

**Properties:**
- `ciphertext` (string) - Base64-encoded encrypted data
- `iv` (string) - Base64-encoded initialization vector
- `tag` (string|null) - Base64-encoded authentication tag (GCM/Poly1305)
- `algorithm` (SymmetricAlgorithm) - Algorithm used
- `keyVersion` (int) - Key version for decryption

**Methods:**

#### toJson()

```php
public function toJson(): string;
```

**Description:** Serializes to JSON for database storage.

**Returns:** `string` - JSON representation

**Example:**
```php
$json = $encrypted->toJson();
// Store in database
```

#### fromJson()

```php
public static function fromJson(string $json): self;
```

**Description:** Deserializes from JSON.

**Parameters:**
- `$json` (string) - JSON string

**Returns:** `EncryptedData` - Reconstructed object

---

### SignedData

**Location:** `src/ValueObjects/SignedData.php`

**Purpose:** Immutable container for signed data.

**Properties:**
- `data` (string) - Original data
- `signature` (string) - Base64-encoded signature
- `algorithm` (AsymmetricAlgorithm) - Algorithm used

---

### KeyPair

**Location:** `src/ValueObjects/KeyPair.php`

**Purpose:** Immutable container for asymmetric key pairs.

**Properties:**
- `publicKey` (string) - Base64-encoded public key
- `privateKey` (string) - Base64-encoded private key
- `algorithm` (AsymmetricAlgorithm) - Algorithm used

---

### EncryptionKey

**Location:** `src/ValueObjects/EncryptionKey.php`

**Purpose:** Immutable container for encryption keys.

**Properties:**
- `key` (string) - Base64-encoded key
- `algorithm` (SymmetricAlgorithm) - Algorithm
- `version` (int) - Key version
- `expiresAt` (DateTimeImmutable|null) - Expiration date

**Methods:**

#### isExpired()

```php
public function isExpired(): bool;
```

**Description:** Checks if key has expired.

**Returns:** `bool` - True if expired

---

## Enums

### HashAlgorithm

**Location:** `src/Enums/HashAlgorithm.php`

**Purpose:** Supported hashing algorithms.

**Cases:**
- `SHA256` - SHA-256 (256-bit, default)
- `SHA384` - SHA-384 (384-bit)
- `SHA512` - SHA-512 (512-bit)
- `BLAKE2B` - BLAKE2b (512-bit, fastest)

**Helper Methods:**

#### isQuantumResistant()

```php
public function isQuantumResistant(): bool;
```

**Returns:** `bool` - Always false (all classical algorithms)

#### getSecurityLevel()

```php
public function getSecurityLevel(): int;
```

**Returns:** `int` - Security level in bits (256, 384, or 512)

---

### SymmetricAlgorithm

**Location:** `src/Enums/SymmetricAlgorithm.php`

**Purpose:** Supported symmetric encryption algorithms.

**Cases:**
- `AES256GCM` - AES-256-GCM (authenticated, default)
- `AES256CBC` - AES-256-CBC (legacy, unauthenticated)
- `CHACHA20POLY1305` - ChaCha20-Poly1305 (authenticated, modern)

**Helper Methods:**

#### isAuthenticated()

```php
public function isAuthenticated(): bool;
```

**Returns:** `bool` - True for GCM and Poly1305

#### requiresIV()

```php
public function requiresIV(): bool;
```

**Returns:** `bool` - Always true

#### getKeyLength()

```php
public function getKeyLength(): int;
```

**Returns:** `int` - Key length in bytes (32 for all)

---

### AsymmetricAlgorithm

**Location:** `src/Enums/AsymmetricAlgorithm.php`

**Purpose:** Supported asymmetric algorithms.

**Cases (Phase 1 - Classical):**
- `HMACSHA256` - HMAC-SHA256 (webhook signing)
- `ED25519` - Ed25519 (fast, default)
- `RSA2048` - RSA-2048 (legacy)
- `RSA4096` - RSA-4096 (high security)
- `ECDSAP256` - ECDSA P-256 (enum defined, not implemented)

**Cases (Phase 2 - Post-Quantum):**
- `DILITHIUM3` - Dilithium3 (PQC digital signature)
- `KYBER768` - Kyber768 (PQC key encapsulation)

**Helper Methods:**

#### isQuantumResistant()

```php
public function isQuantumResistant(): bool;
```

**Returns:** `bool` - True for Dilithium3 and Kyber768

#### getSecurityLevel()

```php
public function getSecurityLevel(): int;
```

**Returns:** `int` - Security level in bits

---

## Exceptions

All exceptions extend `CryptoException` which extends PHP's base `\Exception`.

### CryptoException

**Location:** `src/Exceptions/CryptoException.php`

**Purpose:** Base exception for all crypto errors.

---

### EncryptionException

**Location:** `src/Exceptions/EncryptionException.php`

**Purpose:** Thrown when encryption operation fails.

**Factory Methods:**

```php
public static function failed(string $reason): self;
```

**Example:**
```php
throw EncryptionException::failed('Invalid key length');
```

---

### DecryptionException

**Location:** `src/Exceptions/DecryptionException.php`

**Purpose:** Thrown when decryption operation fails.

**Factory Methods:**

```php
public static function failed(string $reason): self;
public static function tamperedData(): self;
```

---

### SignatureException

**Location:** `src/Exceptions/SignatureException.php`

**Purpose:** Thrown when signing or verification fails.

**Factory Methods:**

```php
public static function verificationFailed(): self;
public static function signingFailed(string $reason): self;
```

---

### InvalidKeyException

**Location:** `src/Exceptions/InvalidKeyException.php`

**Purpose:** Thrown when key is invalid or not found.

**Factory Methods:**

```php
public static function notFound(string $keyId): self;
public static function expired(string $keyId): self;
```

---

### UnsupportedAlgorithmException

**Location:** `src/Exceptions/UnsupportedAlgorithmException.php`

**Purpose:** Thrown when algorithm is not supported.

**Factory Methods:**

```php
public static function forAlgorithm(string $algorithm): self;
```

---

### FeatureNotImplementedException

**Location:** `src/Exceptions/FeatureNotImplementedException.php`

**Purpose:** Thrown when calling Phase 2/3 PQC features.

**Factory Methods:**

```php
public static function pqcAlgorithm(string $algorithm): self;
```

**Example:**
```php
throw FeatureNotImplementedException::pqcAlgorithm('dilithium3');
```

---

## Handler

### KeyRotationHandler

**Location:** `src/Handlers/KeyRotationHandler.php`

**Purpose:** Automated key rotation via Nexus\Scheduler.

**Implements:** `Nexus\Scheduler\Contracts\JobHandlerInterface`

**Constructor Dependencies:**
- `KeyStorageInterface` - Key persistence
- `LoggerInterface|null` - Optional logging

**Method:**

#### handle()

```php
public function handle(array $parameters = []): JobResult;
```

**Description:** Rotates all keys expiring within 7 days.

**Returns:** `JobResult` - Result with rotation count

**Usage:**
```php
// Registered automatically with Scheduler
// Runs daily at 3 AM
```

---

## Usage Patterns

### Pattern 1: Encrypt Model Attribute

```php
use Nexus\Crypto\Services\CryptoManager;

final readonly class Employee
{
    public function __construct(
        private CryptoManager $crypto
    ) {}
    
    public function setSalary(float $salary): void
    {
        $encrypted = $this->crypto->encryptWithKey(
            (string) $salary,
            "tenant-{$this->tenantId}-payroll"
        );
        
        $this->encryptedSalary = $encrypted->toJson();
    }
    
    public function getSalary(): float
    {
        $encrypted = EncryptedData::fromJson($this->encryptedSalary);
        $decrypted = $this->crypto->decryptWithKey(
            $encrypted,
            "tenant-{$this->tenantId}-payroll"
        );
        
        return (float) $decrypted;
    }
}
```

### Pattern 2: Sign Document

```php
$generator = app(KeyGeneratorInterface::class);
$signer = app(AsymmetricSignerInterface::class);

// Generate key pair (once)
$keyPair = $generator->generateKeyPair(AsymmetricAlgorithm::ED25519);

// Store keys securely
$this->storePrivateKey($keyPair->privateKey);
$this->storePublicKey($keyPair->publicKey);

// Sign document
$signed = $signer->sign($documentContent, $keyPair->privateKey);

// Verify later
$isValid = $signer->verifySignature($signed, $keyPair->publicKey);
```

### Pattern 3: Webhook HMAC

```php
$signer = app(AsymmetricSignerInterface::class);

// Outgoing webhook
$signature = $signer->hmac($payload, $webhookSecret);
$headers = ['X-Signature' => $signature];

// Incoming webhook verification
$receivedSignature = $request->header('X-Signature');
$isValid = $signer->verifyHmac($payload, $receivedSignature, $secret);

if (!$isValid) {
    throw new UnauthorizedException('Invalid webhook signature');
}
```
