<?php

declare(strict_types=1);

/**
 * Advanced Cryptographic Operations
 * 
 * This file demonstrates advanced patterns for the Nexus\Crypto package:
 * - Key rotation workflow
 * - Envelope encryption pattern
 * - Multi-tenant key isolation
 * - Performance benchmarks
 * - Error recovery patterns
 */

use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\DecryptionException;
use Nexus\Crypto\Exceptions\InvalidKeyException;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;

// ============================================================================
// Example 1: Key Rotation Workflow
// ============================================================================

echo "=== Example 1: Key Rotation Workflow ===\n\n";

$keyStorage = app(KeyStorageInterface::class);
$encryptor = app(SymmetricEncryptorInterface::class);

$keyId = 'tenant-456-financial-data';

// Step 1: Check if key exists, create if not
try {
    $currentKey = $keyStorage->retrieve($keyId);
    echo "✅ Key '{$keyId}' exists (version {$currentKey->version})\n";
} catch (InvalidKeyException $e) {
    echo "⚠️  Key not found, creating initial key...\n";
    $initialKey = new EncryptionKey(
        key: base64_encode(random_bytes(32)),
        algorithm: SymmetricAlgorithm::AES256GCM,
        version: 1,
        expiresAt: new \DateTimeImmutable('+1 year')
    );
    $keyStorage->store($keyId, $initialKey);
    $currentKey = $initialKey;
    echo "✅ Created version 1\n";
}

// Step 2: Encrypt data with current key
$sensitiveData = 'Q4 2024 Revenue: $12,500,000';
$encrypted = $encryptor->encryptWithKey($sensitiveData, $keyId);
echo "📦 Encrypted with version {$encrypted->keyVersion}\n";
echo "   Ciphertext: " . substr($encrypted->ciphertext, 0, 30) . "...\n\n";

// Step 3: Rotate the key
echo "🔄 Rotating key...\n";
$newKey = $keyStorage->rotate($keyId);
echo "✅ Rotated to version {$newKey->version}\n";
echo "   Old version {$currentKey->version} retained for decryption\n\n";

// Step 4: Decrypt old data with old version (still works!)
$decrypted = $encryptor->decryptWithKey($encrypted, $keyId);
echo "🔓 Decrypted old data: {$decrypted}\n";
echo "   Used key version: {$encrypted->keyVersion}\n\n";

// Step 5: Encrypt new data with new version
$newData = 'Q1 2025 Revenue: $14,200,000';
$newEncrypted = $encryptor->encryptWithKey($newData, $keyId);
echo "📦 New data encrypted with version {$newEncrypted->keyVersion}\n\n";

// ============================================================================
// Example 2: Envelope Encryption Pattern
// ============================================================================

echo "=== Example 2: Envelope Encryption ===\n\n";

/**
 * Envelope Encryption Workflow:
 * 1. Generate a Data Encryption Key (DEK) for each record
 * 2. Encrypt the data with the DEK
 * 3. Encrypt the DEK with a Master Key (stored in KeyStorage)
 * 4. Store both encrypted DEK and encrypted data
 */

// Generate unique DEK for this record
$dek = random_bytes(32); // 256-bit DEK
echo "🔑 Generated DEK: " . bin2hex(substr($dek, 0, 8)) . "...\n";

// Encrypt data with DEK
$recordData = 'Patient MRN: 123456, Diagnosis: Type 2 Diabetes';
$dataEncrypted = sodium_crypto_secretbox(
    $recordData,
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
    $dek
);

echo "📦 Data encrypted with DEK\n";
echo "   Ciphertext: " . bin2hex(substr($dataEncrypted, 0, 16)) . "...\n";
echo "   Nonce: " . bin2hex($nonce) . "\n\n";

// Encrypt DEK with Master Key (from KeyStorage)
$masterKeyId = 'tenant-789-master';
$dekBase64 = base64_encode($dek);
$encryptedDEK = $encryptor->encryptWithKey($dekBase64, $masterKeyId);

echo "🔐 DEK encrypted with Master Key\n";
echo "   Encrypted DEK: " . substr($encryptedDEK->ciphertext, 0, 30) . "...\n\n";

// Store both in database (pseudo-code)
// DB: medical_records
//   - encrypted_data: base64_encode($dataEncrypted)
//   - encrypted_dek: $encryptedDEK->toJson()
//   - nonce: base64_encode($nonce)

// Decryption process
echo "🔓 Decrypting...\n";

// 1. Decrypt DEK with Master Key
$dekDecrypted = $encryptor->decryptWithKey($encryptedDEK, $masterKeyId);
$dekBytes = base64_decode($dekDecrypted);
echo "   DEK decrypted\n";

// 2. Decrypt data with DEK
$dataDecrypted = sodium_crypto_secretbox_open($dataEncrypted, $nonce, $dekBytes);
echo "   Data decrypted: {$dataDecrypted}\n\n";

// ============================================================================
// Example 3: Multi-Tenant Key Isolation
// ============================================================================

echo "=== Example 3: Multi-Tenant Key Isolation ===\n\n";

/**
 * Pattern: Each tenant has isolated keys
 * Key naming: tenant-{tenantId}-{purpose}
 */

class TenantCryptoService
{
    public function __construct(
        private SymmetricEncryptorInterface $encryptor,
        private string $tenantId
    ) {}
    
    private function getKeyId(string $purpose): string
    {
        return "tenant-{$this->tenantId}-{$purpose}";
    }
    
    public function encryptPayroll(string $data): EncryptedData
    {
        return $this->encryptor->encryptWithKey(
            $data,
            $this->getKeyId('payroll')
        );
    }
    
    public function encryptCustomerData(string $data): EncryptedData
    {
        return $this->encryptor->encryptWithKey(
            $data,
            $this->getKeyId('customer-pii')
        );
    }
    
    public function decryptPayroll(EncryptedData $encrypted): string
    {
        return $this->encryptor->decryptWithKey(
            $encrypted,
            $this->getKeyId('payroll')
        );
    }
}

// Tenant A
$tenantA = new TenantCryptoService($encryptor, 'tenant-001');
$salaryA = $tenantA->encryptPayroll('$95,000');
echo "🏢 Tenant A encrypted payroll (key: tenant-001-payroll)\n";

// Tenant B (different key)
$tenantB = new TenantCryptoService($encryptor, 'tenant-002');
$salaryB = $tenantB->encryptPayroll('$102,000');
echo "🏢 Tenant B encrypted payroll (key: tenant-002-payroll)\n\n";

// Tenant A cannot decrypt Tenant B's data (different key)
try {
    $tenantA->decryptPayroll($salaryB); // Will fail!
} catch (InvalidKeyException | DecryptionException $e) {
    echo "✅ Cross-tenant decryption blocked: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// Example 4: Performance Benchmarks
// ============================================================================

echo "=== Example 4: Performance Benchmarks ===\n\n";

$iterations = 1000;
$testData = str_repeat('A', 1024); // 1KB of data

// Benchmark hashing
echo "Hashing 1KB data ({$iterations} iterations):\n";
$hasher = app(\Nexus\Crypto\Contracts\HasherInterface::class);

$algorithms = [
    \Nexus\Crypto\Enums\HashAlgorithm::SHA256,
    \Nexus\Crypto\Enums\HashAlgorithm::BLAKE2B,
];

foreach ($algorithms as $algo) {
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $hasher->hash($testData, $algo);
    }
    $duration = (hrtime(true) - $start) / 1e6; // Convert to ms
    $opsPerSec = ($iterations / $duration) * 1000;
    
    printf("  %s: %.2fms total, %.0f ops/sec\n", 
        $algo->value, $duration, $opsPerSec);
}

echo "\n";

// Benchmark encryption
echo "Encrypting 1KB data ({$iterations} iterations):\n";

$encAlgorithms = [
    SymmetricAlgorithm::AES256GCM,
    SymmetricAlgorithm::CHACHA20POLY1305,
];

foreach ($encAlgorithms as $algo) {
    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $encryptor->encrypt($testData, $algo);
    }
    $duration = (hrtime(true) - $start) / 1e6;
    $opsPerSec = ($iterations / $duration) * 1000;
    
    printf("  %s: %.2fms total, %.0f ops/sec\n", 
        $algo->value, $duration, $opsPerSec);
}

echo "\n";

// ============================================================================
// Example 5: Error Recovery Patterns
// ============================================================================

echo "=== Example 5: Error Recovery Patterns ===\n\n";

/**
 * Pattern 1: Graceful degradation on key expiration
 */
function safeDecrypt(
    EncryptedData $encrypted,
    string $keyId,
    SymmetricEncryptorInterface $encryptor,
    KeyStorageInterface $keyStorage
): ?string {
    try {
        return $encryptor->decryptWithKey($encrypted, $keyId);
    } catch (InvalidKeyException $e) {
        if (str_contains($e->getMessage(), 'expired')) {
            // Key expired - attempt rotation
            echo "⚠️  Key expired, rotating...\n";
            $keyStorage->rotate($keyId);
            
            // Retry with rotated key
            try {
                return $encryptor->decryptWithKey($encrypted, $keyId);
            } catch (\Throwable $retryError) {
                // Log for manual intervention
                error_log("Failed to decrypt even after rotation: {$retryError->getMessage()}");
                return null;
            }
        }
        
        // Key not found - cannot recover
        error_log("Key not found: {$keyId}");
        return null;
    } catch (DecryptionException $e) {
        // Data tampered or corrupted - cannot recover
        error_log("Decryption failed (possible tampering): {$e->getMessage()}");
        return null;
    }
}

// Test error recovery
$testKeyId = 'test-recovery-key';
$testEncrypted = $encryptor->encryptWithKey('test data', $testKeyId);

$recovered = safeDecrypt($testEncrypted, $testKeyId, $encryptor, $keyStorage);
echo "Recovery result: " . ($recovered ? "✅ {$recovered}" : "❌ Failed") . "\n\n";

/**
 * Pattern 2: Retry with exponential backoff (for transient errors)
 */
function encryptWithRetry(
    string $data,
    string $keyId,
    SymmetricEncryptorInterface $encryptor,
    int $maxRetries = 3
): ?EncryptedData {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $encryptor->encryptWithKey($data, $keyId);
        } catch (\Throwable $e) {
            $attempt++;
            $backoffMs = min(1000 * (2 ** $attempt), 10000); // Max 10s
            
            echo "⚠️  Attempt {$attempt} failed: {$e->getMessage()}\n";
            
            if ($attempt < $maxRetries) {
                echo "   Retrying in {$backoffMs}ms...\n";
                usleep($backoffMs * 1000);
            }
        }
    }
    
    error_log("Failed to encrypt after {$maxRetries} attempts");
    return null;
}

// ============================================================================
// Example 6: Key Expiration Monitoring
// ============================================================================

echo "=== Example 6: Key Expiration Monitoring ===\n\n";

// List keys expiring in next 7 days
$expiringKeys = $keyStorage->listExpiring(7);

if (empty($expiringKeys)) {
    echo "✅ No keys expiring in next 7 days\n";
} else {
    echo "⚠️  Keys expiring soon:\n";
    foreach ($expiringKeys as $keyId) {
        echo "   - {$keyId}\n";
        
        // Proactively rotate
        echo "     🔄 Auto-rotating...\n";
        $keyStorage->rotate($keyId);
    }
}

echo "\n";

// ============================================================================
// Example 7: Batch Encryption (Optimized)
// ============================================================================

echo "=== Example 7: Batch Encryption ===\n\n";

$records = [
    ['id' => 1, 'ssn' => '123-45-6789'],
    ['id' => 2, 'ssn' => '987-65-4321'],
    ['id' => 3, 'ssn' => '555-12-3456'],
];

$keyId = 'tenant-999-ssn';

echo "Encrypting {count($records)} records...\n";
$start = hrtime(true);

$encrypted = array_map(function ($record) use ($encryptor, $keyId) {
    return [
        'id' => $record['id'],
        'encrypted_ssn' => $encryptor->encryptWithKey($record['ssn'], $keyId)->toJson(),
    ];
}, $records);

$duration = (hrtime(true) - $start) / 1e6;
echo "✅ Completed in {$duration}ms\n";
echo "   Avg: " . ($duration / count($records)) . "ms per record\n\n";

// ============================================================================
// Example 8: Algorithm Migration
// ============================================================================

echo "=== Example 8: Algorithm Migration ===\n\n";

/**
 * Migrating from legacy AES-256-CBC to modern AES-256-GCM
 */
$legacyData = $encryptor->encrypt('legacy data', SymmetricAlgorithm::AES256CBC);
echo "📦 Legacy: {$legacyData->algorithm->value}\n";
echo "   Authenticated: " . ($legacyData->algorithm->isAuthenticated() ? 'Yes' : 'No') . "\n\n";

// Decrypt and re-encrypt with modern algorithm
$decrypted = $encryptor->decrypt($legacyData);
$modernData = $encryptor->encrypt($decrypted, SymmetricAlgorithm::AES256GCM);

echo "📦 Modern: {$modernData->algorithm->value}\n";
echo "   Authenticated: " . ($modernData->algorithm->isAuthenticated() ? 'Yes' : 'No') . "\n";
echo "✅ Migration complete\n\n";

echo "🎉 All advanced examples completed!\n";
