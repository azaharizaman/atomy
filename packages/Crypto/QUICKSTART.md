# Nexus\Crypto - Quick Start Guide

Get started with the Nexus\Crypto package in 5 minutes.

## 🚀 Installation

### 1. Install Package (Already Done in Monorepo)

The package is already installed as `nexus/crypto` in the monorepo.

### 2. Run Database Migrations

```bash
cd apps/Atomy
php artisan migrate
```

This creates:
- `encryption_keys` table
- `key_rotation_history` table

### 3. Configure Environment

Add to `apps/Atomy/.env`:

```bash
# Start with legacy mode enabled (safe)
CRYPTO_LEGACY_MODE=true

# Key rotation (optional customization)
CRYPTO_KEY_EXPIRATION_DAYS=90
CRYPTO_ROTATION_WARNING_DAYS=7
CRYPTO_ROTATION_TIME=03:00
```

## 📝 Basic Usage

### Hashing (Data Integrity)

```php
use Nexus\Crypto\Services\CryptoManager;

$crypto = app(CryptoManager::class);

// Hash data
$result = $crypto->hash('user input data');
echo $result->hash; // "5d41402abc4b2a76b9719d911017c592..."

// Verify hash
$isValid = $crypto->verifyHash('user input data', $result);
```

### Encryption (Data at Rest)

```php
// Simple encryption
$encrypted = $crypto->encrypt('sensitive data');

// Decrypt
$plaintext = $crypto->decrypt($encrypted);

// With named key (recommended for multi-tenant)
$crypto->generateEncryptionKey('tenant-123', expirationDays: 90);
$encrypted = $crypto->encryptWithKey('payroll data', 'tenant-123');
$plaintext = $crypto->decryptWithKey($encrypted, 'tenant-123');
```

### Digital Signatures

```php
// Generate key pair
$keyPair = $crypto->generateKeyPair();

// Sign data
$signed = $crypto->sign('document content', $keyPair->privateKey);

// Verify signature
if ($crypto->verifySignature($signed, $keyPair->publicKey)) {
    echo "Signature valid!";
}
```

### Webhook HMAC Signing

```php
// Generate signature for outgoing webhook
$signature = $crypto->hmac($jsonPayload, $webhookSecret);

// Verify incoming webhook
if ($crypto->verifyHmac($payload, $signature, $secret)) {
    // Process webhook
}
```

## 🔄 Migrating from Legacy Crypto

### Step 1: Test with Legacy Mode OFF

```bash
# In staging .env
CRYPTO_LEGACY_MODE=false
```

Run your test suite - everything should still work.

### Step 2: Monitor Production

Deploy to production with `CRYPTO_LEGACY_MODE=false` and monitor:
- Error rates
- Performance metrics
- Webhook verification success rates

### Step 3: Remove Legacy Code (After 30 Days)

Once stable, you can remove the dual code paths.

## 🔑 Key Management

### Generate Tenant-Specific Keys

```php
// For each tenant
$crypto->generateEncryptionKey(
    keyId: "tenant-{$tenantId}-finance",
    expirationDays: 90
);
```

### Manually Rotate a Key

```php
$newKey = $crypto->rotateKey('tenant-123-finance');
```

### Automated Rotation (via Scheduler)

Rotation happens automatically at 3 AM daily. Keys expiring within 7 days are rotated.

Check rotation history:

```sql
SELECT * FROM key_rotation_history 
WHERE key_id = 'tenant-123-finance' 
ORDER BY rotated_at DESC;
```

## 🧪 Testing

### Unit Test Example

```php
use Tests\TestCase;
use Nexus\Crypto\Services\CryptoManager;

class CryptoTest extends TestCase
{
    public function test_encrypt_decrypt_cycle(): void
    {
        $crypto = app(CryptoManager::class);
        
        $original = 'test data';
        $encrypted = $crypto->encrypt($original);
        $decrypted = $crypto->decrypt($encrypted);
        
        $this->assertEquals($original, $decrypted);
    }
}
```

## 📊 Common Patterns

### Pattern 1: Encrypt Model Attribute

```php
// In your Eloquent model
use Nexus\Crypto\Services\CryptoManager;

class Employee extends Model
{
    protected function salary(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSalary($value),
            set: fn ($value) => $this->encryptSalary($value),
        );
    }
    
    private function encryptSalary($value): string
    {
        $crypto = app(CryptoManager::class);
        $encrypted = $crypto->encryptWithKey(
            (string) $value, 
            "tenant-{$this->tenant_id}-payroll"
        );
        return $encrypted->toJson();
    }
    
    private function decryptSalary(?string $value): ?float
    {
        if (!$value) return null;
        
        $crypto = app(CryptoManager::class);
        $encrypted = EncryptedData::fromJson($value);
        return (float) $crypto->decryptWithKey(
            $encrypted,
            "tenant-{$this->tenant_id}-payroll"
        );
    }
}
```

### Pattern 2: Sign Exported Report

```php
use Nexus\Crypto\Services\CryptoManager;

class ReportExporter
{
    public function export(Report $report): SignedData
    {
        $crypto = app(CryptoManager::class);
        $keyPair = $this->getSigningKeyPair(); // Load from storage
        
        $reportData = $report->toJson();
        return $crypto->sign($reportData, $keyPair->privateKey);
    }
}
```

### Pattern 3: Verify Webhook from External API

```php
class WebhookController extends Controller
{
    public function handle(Request $request, CryptoManager $crypto)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature');
        $secret = config('services.external_api.webhook_secret');
        
        if (!$crypto->verifyHmac($payload, $signature, $secret)) {
            abort(401, 'Invalid signature');
        }
        
        // Process webhook
    }
}
```

## ⚡ Performance Tips

### 1. Cache Encryption Keys

Keys are cached automatically for 1 hour (configurable).

### 2. Use Appropriate Algorithms

- **Hashing:** SHA-256 (default) for checksums, BLAKE2b for speed
- **Encryption:** AES-256-GCM (default) for most use cases
- **Signing:** Ed25519 (default) - fastest, HMAC for webhooks

### 3. Batch Operations

```php
// Encrypt multiple records efficiently
$crypto->encryptWithKey($data1, 'tenant-123');
$crypto->encryptWithKey($data2, 'tenant-123'); // Key cached
$crypto->encryptWithKey($data3, 'tenant-123'); // Key cached
```

## 🐛 Troubleshooting

### Problem: "Invalid key length" error

**Solution:** Check that `APP_KEY` is set correctly in `.env`

```bash
php artisan key:generate
```

### Problem: Decryption fails after key rotation

**Solution:** Old keys are retained. Check you're using correct `key_id` and version.

```php
// Key storage keeps all versions
$key = $storage->retrieve('tenant-123'); // Gets latest version
```

### Problem: Webhook verification fails intermittently

**Solution:** Check for signature prefix handling:

```php
// Connector handles this automatically
$signature = 'sha256=abc123...'; // ✅ Works
$signature = 'abc123...';        // ✅ Works
```

## 🔐 Security Best Practices

1. ✅ **Never log encryption keys or plaintext**
2. ✅ **Use named keys per tenant/module**
3. ✅ **Enable audit logging** (`CRYPTO_AUDIT_ENABLED=true`)
4. ✅ **Rotate keys every 90 days** (automated)
5. ✅ **Use AES-256-GCM** for authenticated encryption
6. ✅ **Verify signatures** before processing data

## 📚 Next Steps

- Read full documentation: `packages/Crypto/README.md`
- Review implementation: `packages/Crypto/IMPLEMENTATION_SUMMARY.md`
- Check examples in: `packages/Connector/src/Services/WebhookVerifier.php`
- Plan PQC migration: Target Q3 2026 for Phase 2

## 🆘 Getting Help

- Documentation: `packages/Crypto/README.md`
- Examples: Search codebase for `CryptoManager`
- Issues: Contact security team
- Security: security@nexus-erp.example

---

**You're ready to use Nexus\Crypto! 🎉**
