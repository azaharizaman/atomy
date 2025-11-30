<?php

declare(strict_types=1);

/**
 * Basic Cryptographic Operations
 * 
 * This file demonstrates basic usage of the Nexus\Crypto package.
 * 
 * Prerequisites:
 * - Package installed: composer require nexus/crypto:"*@dev"
 * - Interfaces bound in DI container
 * - ext-sodium and ext-openssl enabled
 */

use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Services\CryptoManager;

// ============================================================================
// Example 1: Hashing for Data Integrity
// ============================================================================

echo "=== Example 1: Hashing ===\n\n";

$hasher = app(HasherInterface::class);

// Hash with default SHA-256
$data = 'sensitive user data';
$hashResult = $hasher->hash($data);

echo "Original: {$data}\n";
echo "Algorithm: {$hashResult->algorithm->value}\n";
echo "Hash: {$hashResult->hash}\n\n";

// Verify hash
$isValid = $hasher->verifyHash($data, $hashResult);
echo "Verification: " . ($isValid ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Try with different algorithm
$blake2bResult = $hasher->hash($data, HashAlgorithm::BLAKE2B);
echo "BLAKE2b Hash: {$blake2bResult->hash}\n\n";

// ============================================================================
// Example 2: Symmetric Encryption (Data at Rest)
// ============================================================================

echo "=== Example 2: Symmetric Encryption ===\n\n";

$encryptor = app(SymmetricEncryptorInterface::class);

// Encrypt sensitive data (auto-generates key)
$plaintext = 'Employee Salary: $75,000';
$encrypted = $encryptor->encrypt($plaintext);

echo "Plaintext: {$plaintext}\n";
echo "Algorithm: {$encrypted->algorithm->value}\n";
echo "Ciphertext: {$encrypted->ciphertext}\n";
echo "IV: {$encrypted->iv}\n";
echo "Tag: {$encrypted->tag}\n\n";

// Decrypt
$decrypted = $encryptor->decrypt($encrypted);
echo "Decrypted: {$decrypted}\n";
echo "Match: " . ($plaintext === $decrypted ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Store in database (serialize to JSON)
$json = $encrypted->toJson();
echo "JSON for DB: {$json}\n\n";

// Later, retrieve from database
$restored = \Nexus\Crypto\ValueObjects\EncryptedData::fromJson($json);
$decryptedAgain = $encryptor->decrypt($restored);
echo "Restored & Decrypted: {$decryptedAgain}\n\n";

// ============================================================================
// Example 3: Encrypt with Named Key
// ============================================================================

echo "=== Example 3: Named Key Encryption ===\n\n";

// Encrypt with a specific key ID
$keyId = 'tenant-123-payroll';
$salary = '85000.50';

$encryptedSalary = $encryptor->encryptWithKey($salary, $keyId);
echo "Encrypted salary with key '{$keyId}'\n";
echo "Ciphertext: {$encryptedSalary->ciphertext}\n";
echo "Key Version: {$encryptedSalary->keyVersion}\n\n";

// Decrypt with named key
$decryptedSalary = $encryptor->decryptWithKey($encryptedSalary, $keyId);
echo "Decrypted: \${$decryptedSalary}\n\n";

// ============================================================================
// Example 4: Digital Signatures
// ============================================================================

echo "=== Example 4: Digital Signatures ===\n\n";

$signer = app(AsymmetricSignerInterface::class);
$generator = app(KeyGeneratorInterface::class);

// Generate key pair (do this once, store securely)
$keyPair = $generator->generateKeyPair(AsymmetricAlgorithm::ED25519);
echo "Algorithm: {$keyPair->algorithm->value}\n";
echo "Public Key: " . substr($keyPair->publicKey, 0, 20) . "...\n";
echo "Private Key: " . substr($keyPair->privateKey, 0, 20) . "... (KEEP SECRET!)\n\n";

// Sign a document
$document = 'Contract: Employee NDA v2.1';
$signed = $signer->sign($document, $keyPair->privateKey);

echo "Signed Document:\n";
echo "  Data: {$signed->data}\n";
echo "  Signature: " . substr($signed->signature, 0, 30) . "...\n";
echo "  Algorithm: {$signed->algorithm->value}\n\n";

// Verify signature
$isValidSignature = $signer->verifySignature($signed, $keyPair->publicKey);
echo "Signature Valid: " . ($isValidSignature ? "✅ YES" : "❌ NO") . "\n\n";

// Try tampering with data
$tamperedData = clone $signed;
$tamperedData = new \Nexus\Crypto\ValueObjects\SignedData(
    data: 'TAMPERED CONTRACT',
    signature: $signed->signature,
    algorithm: $signed->algorithm
);

$isTamperedValid = $signer->verifySignature($tamperedData, $keyPair->publicKey);
echo "Tampered Signature Valid: " . ($isTamperedValid ? "❌ PROBLEM!" : "✅ REJECTED") . "\n\n";

// ============================================================================
// Example 5: HMAC for Webhook Signing
// ============================================================================

echo "=== Example 5: HMAC Webhook Signatures ===\n\n";

$webhookSecret = bin2hex(random_bytes(32)); // Shared secret
$payload = json_encode(['event' => 'invoice.paid', 'invoice_id' => 'INV-12345']);

// Generate HMAC signature
$signature = $signer->hmac($payload, $webhookSecret);
echo "Webhook Payload: {$payload}\n";
echo "HMAC Signature: {$signature}\n\n";

// Verify HMAC (on receiving end)
$isValidHmac = $signer->verifyHmac($payload, $signature, $webhookSecret);
echo "HMAC Valid: " . ($isValidHmac ? "✅ YES" : "❌ NO") . "\n\n";

// Wrong secret = invalid signature
$wrongSecret = 'wrong-secret';
$isInvalidHmac = $signer->verifyHmac($payload, $signature, $wrongSecret);
echo "Wrong Secret Valid: " . ($isInvalidHmac ? "❌ PROBLEM!" : "✅ REJECTED") . "\n\n";

// ============================================================================
// Example 6: Key Generation
// ============================================================================

echo "=== Example 6: Key Generation ===\n\n";

// Generate symmetric key for AES-256-GCM
$aesKey = $generator->generateKey(SymmetricAlgorithm::AES256GCM);
echo "AES-256-GCM Key: " . substr($aesKey, 0, 20) . "...\n";
echo "Key Length: " . strlen(base64_decode($aesKey)) . " bytes\n\n";

// Generate symmetric key for ChaCha20-Poly1305
$chachaKey = $generator->generateKey(SymmetricAlgorithm::CHACHA20POLY1305);
echo "ChaCha20 Key: " . substr($chachaKey, 0, 20) . "...\n\n";

// Generate RSA-4096 key pair
$rsaKeyPair = $generator->generateKeyPair(AsymmetricAlgorithm::RSA4096);
echo "RSA-4096 Key Pair Generated\n";
echo "Public Key Length: " . strlen($rsaKeyPair->publicKey) . " chars\n";
echo "Private Key Length: " . strlen($rsaKeyPair->privateKey) . " chars\n\n";

// ============================================================================
// Example 7: Using CryptoManager (Unified Facade)
// ============================================================================

echo "=== Example 7: CryptoManager Facade ===\n\n";

$crypto = app(CryptoManager::class);

// All operations through single interface
$hashResult = $crypto->hash('test data');
echo "Hash: {$hashResult->hash}\n";

$encrypted = $crypto->encrypt('secret');
echo "Encrypted: {$encrypted->ciphertext}\n";

$keyPair = $crypto->generateKeyPair();
echo "Key Pair: {$keyPair->algorithm->value}\n\n";

// ============================================================================
// Example 8: Algorithm Comparison
// ============================================================================

echo "=== Example 8: Algorithm Comparison ===\n\n";

$testData = 'Performance test data';

// Hash algorithms
$algorithms = [
    HashAlgorithm::SHA256,
    HashAlgorithm::SHA384,
    HashAlgorithm::SHA512,
    HashAlgorithm::BLAKE2B,
];

echo "Hash Algorithm Performance:\n";
foreach ($algorithms as $algo) {
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $hasher->hash($testData, $algo);
    }
    $duration = (microtime(true) - $start) * 1000;
    $securityLevel = $algo->getSecurityLevel();
    
    printf("  %s: %.2fms (1000 ops), Security: %d-bit\n", 
        $algo->value, $duration, $securityLevel);
}

echo "\n";

// Symmetric encryption algorithms
$encAlgorithms = [
    SymmetricAlgorithm::AES256GCM,
    SymmetricAlgorithm::AES256CBC,
    SymmetricAlgorithm::CHACHA20POLY1305,
];

echo "Encryption Algorithm Features:\n";
foreach ($encAlgorithms as $algo) {
    $authenticated = $algo->isAuthenticated() ? 'Yes' : 'No';
    $keyLength = $algo->getKeyLength();
    
    printf("  %s: Authenticated=%s, Key=%d bytes\n", 
        $algo->value, $authenticated, $keyLength);
}

echo "\n✅ All examples completed successfully!\n";
