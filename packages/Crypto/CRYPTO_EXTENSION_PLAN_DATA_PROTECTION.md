# Nexus\Crypto Extension Plan: Data Anonymization & Masking

**Version:** 1.0  
**Date:** December 17, 2025  
**Status:** 📋 PLANNING  
**Context:** Extension for Compliance Domain - Data Privacy Support  
**Related:** [`packages/Compliance/COMPLIANCE_ATOMIC_DECOMPOSITION_STRATEGY.md`](../Compliance/COMPLIANCE_ATOMIC_DECOMPOSITION_STRATEGY.md)

---

## 🎯 Executive Summary

Extend the existing `Nexus\Crypto` package with **data anonymization, pseudonymization, and masking** capabilities to support the `Nexus\DataPrivacy` package and broader compliance requirements (GDPR, CCPA, LGPD, PIPEDA).

**Scope:**
- Data anonymization (irreversible)
- Pseudonymization (reversible with key)
- Data masking utilities (display protection)

**Estimated Effort:** ~400 LOC addition  
**Atomicity Impact:** ✅ SAFE - stays within single "Cryptography & Data Protection" domain

---

## 📋 Atomicity Compliance Verification

### Pre-Extension Check

| Criterion | Current State | Post-Extension | Threshold | Status |
|-----------|---------------|----------------|-----------|--------|
| **Domain Responsibility** | Cryptographic operations | Cryptographic operations + data protection | 1 domain | ✅ SAME DOMAIN |
| **Public Service Classes** | 5 classes | 7 classes | <15 | ✅ SAFE (47%) |
| **Interface Methods** | ~18 methods | ~26 methods | <40 | ✅ SAFE (65%) |
| **Lines of Code** | ~1,200 LOC | ~1,600 LOC | <5,000 | ✅ SAFE (32%) |
| **Constructor Dependencies** | 5 max | 5 max | <7 | ✅ SAFE (71%) |

### Domain Justification

**Why this belongs in `Nexus\Crypto` (not a separate package):**

1. **Cryptographic Foundation:** Anonymization and pseudonymization are fundamentally cryptographic operations:
   - Pseudonymization uses encryption/hashing
   - Anonymization uses irreversible hashing
   - Masking uses format-preserving transformation

2. **Shared Dependencies:** Uses existing package infrastructure:
   - `HasherInterface` for irreversible anonymization
   - `SymmetricEncryptorInterface` for reversible pseudonymization
   - `KeyStorageInterface` for pseudonymization key management

3. **Single Responsibility:** "Protect data cryptographically" encompasses:
   - Encrypt data (existing)
   - Hash data (existing)
   - Anonymize data (new - irreversible protection)
   - Mask data (new - display protection)

4. **Industry Precedent:** Major crypto libraries (OpenSSL, libsodium, BouncyCastle) include data protection utilities alongside core cryptographic operations.

---

## 🏗️ Extension Architecture

### New Components Overview

```
packages/Crypto/
├── src/
│   ├── Contracts/
│   │   ├── AnonymizerInterface.php          # NEW - Anonymization operations
│   │   └── DataMaskerInterface.php          # NEW - Masking operations
│   │
│   ├── Enums/
│   │   ├── AnonymizationMethod.php          # NEW - k-anonymity, differential privacy, hash-based
│   │   └── MaskingPattern.php               # NEW - Email, phone, credit card, SSN patterns
│   │
│   ├── Services/
│   │   ├── Anonymizer.php                   # NEW - Anonymization service
│   │   └── DataMasker.php                   # NEW - Masking service
│   │
│   ├── ValueObjects/
│   │   ├── AnonymizedData.php               # NEW - Anonymization result
│   │   └── PseudonymizedData.php            # NEW - Pseudonymization result (reversible)
│   │
│   └── Exceptions/
│       └── AnonymizationException.php       # NEW - Anonymization failures
│
└── tests/
    └── Unit/
        ├── Services/
        │   ├── AnonymizerTest.php           # NEW
        │   └── DataMaskerTest.php           # NEW
        └── ValueObjects/
            ├── AnonymizedDataTest.php       # NEW
            └── PseudonymizedDataTest.php    # NEW
```

---

## 📐 Detailed Design

### 1. Contracts (Interfaces)

#### `AnonymizerInterface.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\PseudonymizedData;

/**
 * Anonymizer Interface
 *
 * Provides data anonymization and pseudonymization for privacy compliance.
 * 
 * Key Concepts:
 * - **Anonymization**: Irreversible transformation (cannot recover original data)
 * - **Pseudonymization**: Reversible with key (can recover original with proper authorization)
 *
 * @see https://gdpr-info.eu/recitals/no-26/ GDPR definition of anonymization
 */
interface AnonymizerInterface
{
    /**
     * Anonymize data (irreversible)
     *
     * Creates a one-way transformation that cannot be reversed.
     * Suitable for analytics, testing data, and permanent de-identification.
     *
     * @param string $data Original data to anonymize
     * @param AnonymizationMethod $method Anonymization method to use
     * @param array<string, mixed> $options Method-specific options
     * @return AnonymizedData Anonymized result with metadata
     */
    public function anonymize(
        string $data,
        AnonymizationMethod $method = AnonymizationMethod::HASH_BASED,
        array $options = []
    ): AnonymizedData;

    /**
     * Pseudonymize data (reversible with key)
     *
     * Creates a reversible transformation using encryption.
     * Original data can be recovered with the pseudonymization key.
     *
     * @param string $data Original data to pseudonymize
     * @param string $keyId Key identifier for pseudonymization/de-pseudonymization
     * @return PseudonymizedData Pseudonymized result with key reference
     */
    public function pseudonymize(string $data, string $keyId): PseudonymizedData;

    /**
     * De-pseudonymize data (reverse pseudonymization)
     *
     * Recovers original data using the pseudonymization key.
     * Requires proper authorization to access the key.
     *
     * @param PseudonymizedData $pseudonymized Pseudonymized data
     * @return string Original data
     * @throws \Nexus\Crypto\Exceptions\DecryptionException If key not found or decryption fails
     */
    public function dePseudonymize(PseudonymizedData $pseudonymized): string;

    /**
     * Generate consistent pseudonym for cross-system correlation
     *
     * Creates a deterministic pseudonym using HMAC, allowing correlation
     * across systems without exposing original data.
     *
     * @param string $data Original data
     * @param string $context Context identifier (e.g., 'analytics', 'research')
     * @param string $keyId Key for HMAC generation
     * @return string Deterministic pseudonym (hex-encoded)
     */
    public function generatePseudonym(string $data, string $context, string $keyId): string;
}
```

#### `DataMaskerInterface.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\MaskingPattern;

/**
 * Data Masker Interface
 *
 * Provides data masking for secure display and logging.
 * Masks sensitive data while preserving recognizable format.
 *
 * Use Cases:
 * - Display masked credit cards in UI (****-****-****-1234)
 * - Log masked emails (j***@example.com)
 * - Show partial phone numbers (+1 (***) ***-7890)
 * - Mask SSN/National IDs (***-**-6789)
 */
interface DataMaskerInterface
{
    /**
     * Mask data using predefined pattern
     *
     * @param string $data Original data to mask
     * @param MaskingPattern $pattern Masking pattern to apply
     * @return string Masked data
     */
    public function mask(string $data, MaskingPattern $pattern): string;

    /**
     * Mask data using custom pattern
     *
     * Pattern syntax:
     * - `*` = Replace with mask character
     * - `#` = Keep original character
     * - Any other character = Literal
     *
     * Example: "####-****-****-####" for credit card
     *
     * @param string $data Original data to mask
     * @param string $pattern Custom masking pattern
     * @param string $maskChar Character to use for masking (default: '*')
     * @return string Masked data
     */
    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string;

    /**
     * Mask email address
     *
     * Preserves first character of local part and full domain.
     * Example: "john.doe@example.com" → "j*******@example.com"
     *
     * @param string $email Email address to mask
     * @return string Masked email
     */
    public function maskEmail(string $email): string;

    /**
     * Mask phone number
     *
     * Preserves country code and last 4 digits.
     * Example: "+1 (555) 123-4567" → "+1 (***) ***-4567"
     *
     * @param string $phone Phone number to mask
     * @return string Masked phone
     */
    public function maskPhone(string $phone): string;

    /**
     * Mask credit card number
     *
     * Preserves first 4 and last 4 digits (standard display format).
     * Example: "4111111111111111" → "4111-****-****-1111"
     *
     * @param string $cardNumber Credit card number
     * @return string Masked card number
     */
    public function maskCreditCard(string $cardNumber): string;

    /**
     * Mask national ID / SSN
     *
     * Preserves last 4 digits.
     * Example: "123-45-6789" → "***-**-6789"
     *
     * @param string $nationalId National ID or SSN
     * @return string Masked ID
     */
    public function maskNationalId(string $nationalId): string;

    /**
     * Mask IBAN
     *
     * Preserves country code and last 4 digits.
     * Example: "DE89370400440532013000" → "DE**************3000"
     *
     * @param string $iban IBAN number
     * @return string Masked IBAN
     */
    public function maskIban(string $iban): string;

    /**
     * Redact data completely
     *
     * Replaces entire content with redaction marker.
     * Example: "John Doe" → "[REDACTED]"
     *
     * @param string $data Data to redact
     * @param string $redactionMarker Marker to use (default: '[REDACTED]')
     * @return string Redaction marker
     */
    public function redact(string $data, string $redactionMarker = '[REDACTED]'): string;
}
```

---

### 2. Enums

#### `AnonymizationMethod.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Anonymization Method
 *
 * Defines available anonymization techniques for irreversible data protection.
 */
enum AnonymizationMethod: string
{
    /**
     * Hash-based anonymization using SHA-256
     * 
     * Fast, deterministic (same input = same output).
     * Good for: Identifiers, lookup keys.
     * Risk: Vulnerable to dictionary attacks on low-entropy data.
     */
    case HASH_BASED = 'hash_based';

    /**
     * Salted hash anonymization
     * 
     * Non-deterministic (same input = different output each time).
     * Good for: One-time anonymization, preventing correlation.
     * Risk: Cannot correlate same values across records.
     */
    case SALTED_HASH = 'salted_hash';

    /**
     * HMAC-based anonymization (keyed hash)
     * 
     * Deterministic with secret key.
     * Good for: Cross-system correlation, consistent pseudonyms.
     * Requires: Key management for the HMAC secret.
     */
    case HMAC_BASED = 'hmac_based';

    /**
     * K-anonymity generalization
     * 
     * Generalizes value to broader category.
     * Good for: Ages (→ ranges), locations (→ regions).
     * Requires: Generalization hierarchy in options.
     */
    case K_ANONYMITY = 'k_anonymity';

    /**
     * Data suppression (complete removal)
     * 
     * Replaces value with null/empty.
     * Good for: Fields that must be completely removed.
     * Simplest but least utility.
     */
    case SUPPRESSION = 'suppression';

    /**
     * Check if method is deterministic (same input = same output)
     */
    public function isDeterministic(): bool
    {
        return match ($this) {
            self::HASH_BASED, self::HMAC_BASED => true,
            self::SALTED_HASH, self::K_ANONYMITY, self::SUPPRESSION => false,
        };
    }

    /**
     * Check if method requires a key
     */
    public function requiresKey(): bool
    {
        return $this === self::HMAC_BASED;
    }

    /**
     * Get description for documentation/logging
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HASH_BASED => 'SHA-256 hash anonymization',
            self::SALTED_HASH => 'Salted SHA-256 hash anonymization',
            self::HMAC_BASED => 'HMAC-SHA256 keyed anonymization',
            self::K_ANONYMITY => 'K-anonymity generalization',
            self::SUPPRESSION => 'Complete data suppression',
        };
    }
}
```

#### `MaskingPattern.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Masking Pattern
 *
 * Predefined masking patterns for common sensitive data types.
 */
enum MaskingPattern: string
{
    /**
     * Email masking: j*******@example.com
     */
    case EMAIL = 'email';

    /**
     * Phone masking: +1 (***) ***-4567
     */
    case PHONE = 'phone';

    /**
     * Credit card masking: 4111-****-****-1111
     */
    case CREDIT_CARD = 'credit_card';

    /**
     * SSN/National ID masking: ***-**-6789
     */
    case NATIONAL_ID = 'national_id';

    /**
     * IBAN masking: DE**************3000
     */
    case IBAN = 'iban';

    /**
     * Name masking: J*** D**
     */
    case NAME = 'name';

    /**
     * Address masking: 123 **** Street, ****
     */
    case ADDRESS = 'address';

    /**
     * Date of birth masking: ****-**-15
     */
    case DATE_OF_BIRTH = 'date_of_birth';

    /**
     * Full redaction: [REDACTED]
     */
    case FULL_REDACTION = 'full_redaction';

    /**
     * Get display-friendly label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'Email Address',
            self::PHONE => 'Phone Number',
            self::CREDIT_CARD => 'Credit Card',
            self::NATIONAL_ID => 'National ID / SSN',
            self::IBAN => 'IBAN',
            self::NAME => 'Personal Name',
            self::ADDRESS => 'Address',
            self::DATE_OF_BIRTH => 'Date of Birth',
            self::FULL_REDACTION => 'Full Redaction',
        };
    }

    /**
     * Get example output for documentation
     */
    public function getExample(): string
    {
        return match ($this) {
            self::EMAIL => 'j*******@example.com',
            self::PHONE => '+1 (***) ***-4567',
            self::CREDIT_CARD => '4111-****-****-1111',
            self::NATIONAL_ID => '***-**-6789',
            self::IBAN => 'DE**************3000',
            self::NAME => 'J*** D**',
            self::ADDRESS => '123 **** St, ****',
            self::DATE_OF_BIRTH => '****-**-15',
            self::FULL_REDACTION => '[REDACTED]',
        };
    }
}
```

---

### 3. Value Objects

#### `AnonymizedData.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use Nexus\Crypto\Enums\AnonymizationMethod;

/**
 * Anonymized Data Value Object
 *
 * Represents the result of an irreversible anonymization operation.
 * Original data cannot be recovered from this object.
 */
final class AnonymizedData
{
    public function __construct(
        /**
         * Anonymized value (hex-encoded for hash-based methods)
         */
        public readonly string $value,

        /**
         * Method used for anonymization
         */
        public readonly AnonymizationMethod $method,

        /**
         * Original data length (for validation purposes)
         */
        public readonly int $originalLength,

        /**
         * Timestamp of anonymization
         */
        public readonly \DateTimeImmutable $anonymizedAt,

        /**
         * Additional metadata
         * 
         * @var array<string, mixed>
         */
        public readonly array $metadata = [],
    ) {}

    /**
     * Check if anonymization used deterministic method
     */
    public function isDeterministic(): bool
    {
        return $this->method->isDeterministic();
    }

    /**
     * Serialize for storage/transmission
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'method' => $this->method->value,
            'original_length' => $this->originalLength,
            'anonymized_at' => $this->anonymizedAt->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Reconstruct from serialized data
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'],
            method: AnonymizationMethod::from($data['method']),
            originalLength: $data['original_length'],
            anonymizedAt: new \DateTimeImmutable($data['anonymized_at']),
            metadata: $data['metadata'] ?? [],
        );
    }
}
```

#### `PseudonymizedData.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

/**
 * Pseudonymized Data Value Object
 *
 * Represents the result of a reversible pseudonymization operation.
 * Original data can be recovered with the proper key.
 */
final class PseudonymizedData
{
    public function __construct(
        /**
         * Pseudonymized value (encrypted, base64-encoded)
         */
        public readonly string $value,

        /**
         * Key identifier used for pseudonymization
         * 
         * Required for de-pseudonymization.
         */
        public readonly string $keyId,

        /**
         * Timestamp of pseudonymization
         */
        public readonly \DateTimeImmutable $pseudonymizedAt,

        /**
         * Version of the key used (for key rotation support)
         */
        public readonly int $keyVersion = 1,

        /**
         * Additional metadata
         * 
         * @var array<string, mixed>
         */
        public readonly array $metadata = [],
    ) {}

    /**
     * Serialize for storage/transmission
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'key_id' => $this->keyId,
            'key_version' => $this->keyVersion,
            'pseudonymized_at' => $this->pseudonymizedAt->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Reconstruct from serialized data
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'],
            keyId: $data['key_id'],
            pseudonymizedAt: new \DateTimeImmutable($data['pseudonymized_at']),
            keyVersion: $data['key_version'] ?? 1,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Check if pseudonymization uses latest key version
     */
    public function isCurrentKeyVersion(int $currentVersion): bool
    {
        return $this->keyVersion === $currentVersion;
    }
}
```

---

### 4. Services Implementation

#### `Anonymizer.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\AnonymizerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Exceptions\AnonymizationException;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Anonymizer Service
 *
 * Provides data anonymization and pseudonymization operations.
 */
final readonly class Anonymizer implements AnonymizerInterface
{
    public function __construct(
        private HasherInterface $hasher,
        private SymmetricEncryptorInterface $encryptor,
        private KeyStorageInterface $keyStorage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function anonymize(
        string $data,
        AnonymizationMethod $method = AnonymizationMethod::HASH_BASED,
        array $options = []
    ): AnonymizedData {
        $this->logger->debug('Anonymizing data', [
            'method' => $method->value,
            'dataLength' => strlen($data),
        ]);

        $value = match ($method) {
            AnonymizationMethod::HASH_BASED => $this->hashBasedAnonymize($data),
            AnonymizationMethod::SALTED_HASH => $this->saltedHashAnonymize($data),
            AnonymizationMethod::HMAC_BASED => $this->hmacBasedAnonymize($data, $options),
            AnonymizationMethod::K_ANONYMITY => $this->kAnonymityAnonymize($data, $options),
            AnonymizationMethod::SUPPRESSION => '',
        };

        return new AnonymizedData(
            value: $value,
            method: $method,
            originalLength: strlen($data),
            anonymizedAt: new \DateTimeImmutable(),
            metadata: $options['metadata'] ?? [],
        );
    }

    public function pseudonymize(string $data, string $keyId): PseudonymizedData
    {
        $this->logger->debug('Pseudonymizing data', ['keyId' => $keyId]);

        $key = $this->keyStorage->retrieve($keyId);
        $encrypted = $this->encryptor->encrypt($data, $key->algorithm, $key);

        return new PseudonymizedData(
            value: base64_encode($encrypted->ciphertext . '::' . $encrypted->iv . '::' . $encrypted->tag),
            keyId: $keyId,
            pseudonymizedAt: new \DateTimeImmutable(),
            keyVersion: $key->version ?? 1,
        );
    }

    public function dePseudonymize(PseudonymizedData $pseudonymized): string
    {
        $this->logger->debug('De-pseudonymizing data', ['keyId' => $pseudonymized->keyId]);

        $decoded = base64_decode($pseudonymized->value, true);
        if ($decoded === false) {
            throw new AnonymizationException('Invalid pseudonymized data format');
        }

        $parts = explode('::', $decoded);
        if (count($parts) !== 3) {
            throw new AnonymizationException('Malformed pseudonymized data');
        }

        [$ciphertext, $iv, $tag] = $parts;

        $key = $this->keyStorage->retrieve($pseudonymized->keyId);
        
        $encrypted = new \Nexus\Crypto\ValueObjects\EncryptedData(
            ciphertext: $ciphertext,
            iv: $iv,
            tag: $tag,
            algorithm: $key->algorithm,
        );

        return $this->encryptor->decrypt($encrypted, $key);
    }

    public function generatePseudonym(string $data, string $context, string $keyId): string
    {
        $key = $this->keyStorage->retrieve($keyId);
        $combined = $context . '::' . $data;
        
        return hash_hmac('sha256', $combined, $key->keyMaterial);
    }

    // =====================================================
    // PRIVATE METHODS
    // =====================================================

    private function hashBasedAnonymize(string $data): string
    {
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        return $result->hash;
    }

    private function saltedHashAnonymize(string $data): string
    {
        $salt = random_bytes(16);
        $result = $this->hasher->hash($salt . $data, HashAlgorithm::SHA256);
        return bin2hex($salt) . ':' . $result->hash;
    }

    /**
     * @param array<string, mixed> $options Must contain 'keyId'
     */
    private function hmacBasedAnonymize(string $data, array $options): string
    {
        if (!isset($options['keyId'])) {
            throw new AnonymizationException('HMAC-based anonymization requires keyId option');
        }

        $key = $this->keyStorage->retrieve($options['keyId']);
        return hash_hmac('sha256', $data, $key->keyMaterial);
    }

    /**
     * @param array<string, mixed> $options Must contain 'hierarchy' for generalization
     */
    private function kAnonymityAnonymize(string $data, array $options): string
    {
        $hierarchy = $options['hierarchy'] ?? null;
        
        if ($hierarchy === null) {
            throw new AnonymizationException('K-anonymity requires hierarchy option');
        }

        // Find matching generalization in hierarchy
        foreach ($hierarchy as $pattern => $generalization) {
            if (preg_match($pattern, $data)) {
                return $generalization;
            }
        }

        // Default: suppress if no match
        return $options['default'] ?? '[GENERALIZED]';
    }
}
```

#### `DataMasker.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\DataMaskerInterface;
use Nexus\Crypto\Enums\MaskingPattern;

/**
 * Data Masker Service
 *
 * Provides data masking for secure display and logging.
 */
final readonly class DataMasker implements DataMaskerInterface
{
    public function mask(string $data, MaskingPattern $pattern): string
    {
        return match ($pattern) {
            MaskingPattern::EMAIL => $this->maskEmail($data),
            MaskingPattern::PHONE => $this->maskPhone($data),
            MaskingPattern::CREDIT_CARD => $this->maskCreditCard($data),
            MaskingPattern::NATIONAL_ID => $this->maskNationalId($data),
            MaskingPattern::IBAN => $this->maskIban($data),
            MaskingPattern::NAME => $this->maskName($data),
            MaskingPattern::ADDRESS => $this->maskAddress($data),
            MaskingPattern::DATE_OF_BIRTH => $this->maskDateOfBirth($data),
            MaskingPattern::FULL_REDACTION => $this->redact($data),
        };
    }

    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string
    {
        $result = '';
        $dataIndex = 0;
        $dataLength = strlen($data);

        for ($i = 0, $patternLength = strlen($pattern); $i < $patternLength; $i++) {
            $patternChar = $pattern[$i];
            
            if ($dataIndex >= $dataLength) {
                break;
            }

            $result .= match ($patternChar) {
                '#' => $data[$dataIndex++],
                '*' => $maskChar . (++$dataIndex ? '' : ''),
                default => $patternChar,
            };
        }

        return $result;
    }

    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        
        if (count($parts) !== 2) {
            return $this->redact($email);
        }

        [$local, $domain] = $parts;
        
        if (strlen($local) <= 1) {
            return $local . '@' . $domain;
        }

        $masked = $local[0] . str_repeat('*', strlen($local) - 1);
        return $masked . '@' . $domain;
    }

    public function maskPhone(string $phone): string
    {
        // Remove non-digit characters for processing
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($digits) < 4) {
            return $this->redact($phone);
        }

        // Keep last 4 digits
        $lastFour = substr($digits, -4);
        $prefix = substr($digits, 0, -4);
        $maskedPrefix = str_repeat('*', strlen($prefix));

        // Reconstruct with original formatting hints
        if (str_starts_with($phone, '+')) {
            // International format
            $countryCode = substr($digits, 0, min(3, strlen($digits) - 4));
            $remaining = str_repeat('*', strlen($digits) - strlen($countryCode) - 4);
            return '+' . $countryCode . ' ' . $remaining . $lastFour;
        }

        return $maskedPrefix . $lastFour;
    }

    public function maskCreditCard(string $cardNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $cardNumber);
        
        if (strlen($digits) < 12) {
            return $this->redact($cardNumber);
        }

        $first = substr($digits, 0, 4);
        $last = substr($digits, -4);
        $middleLength = strlen($digits) - 8;
        $masked = str_repeat('*', $middleLength);

        // Format as 4-4-4-4
        return $first . '-' . substr($masked, 0, 4) . '-' . substr($masked, 4, 4) . '-' . $last;
    }

    public function maskNationalId(string $nationalId): string
    {
        $digits = preg_replace('/[^0-9]/', '', $nationalId);
        
        if (strlen($digits) < 4) {
            return $this->redact($nationalId);
        }

        $last = substr($digits, -4);
        $prefix = substr($digits, 0, -4);
        $masked = str_repeat('*', strlen($prefix));

        // Common SSN format: ***-**-XXXX
        if (strlen($digits) === 9) {
            return '***-**-' . $last;
        }

        return $masked . '-' . $last;
    }

    public function maskIban(string $iban): string
    {
        $clean = str_replace(' ', '', strtoupper($iban));
        
        if (strlen($clean) < 8) {
            return $this->redact($iban);
        }

        $country = substr($clean, 0, 2);
        $last = substr($clean, -4);
        $middleLength = strlen($clean) - 6;
        $masked = str_repeat('*', $middleLength);

        return $country . $masked . $last;
    }

    public function redact(string $data, string $redactionMarker = '[REDACTED]'): string
    {
        return $redactionMarker;
    }

    // =====================================================
    // PRIVATE HELPER METHODS
    // =====================================================

    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', $name);
        
        if ($parts === false || count($parts) === 0) {
            return $this->redact($name);
        }

        $masked = [];
        foreach ($parts as $part) {
            if (strlen($part) <= 1) {
                $masked[] = $part;
            } else {
                $masked[] = $part[0] . str_repeat('*', strlen($part) - 1);
            }
        }

        return implode(' ', $masked);
    }

    private function maskAddress(string $address): string
    {
        // Keep first numeric part (street number), mask rest
        if (preg_match('/^(\d+)\s+(.*)$/', $address, $matches)) {
            $number = $matches[1];
            $rest = $matches[2];
            $maskedRest = preg_replace('/[a-zA-Z]/', '*', $rest);
            return $number . ' ' . $maskedRest;
        }

        // No number found, mask alphanumeric
        return preg_replace('/[a-zA-Z0-9]/', '*', $address) ?? $this->redact($address);
    }

    private function maskDateOfBirth(string $date): string
    {
        // Try to parse as date
        $parsed = date_parse($date);
        
        if ($parsed['error_count'] > 0 || !$parsed['day']) {
            return $this->redact($date);
        }

        // Keep only day
        return '****-**-' . str_pad((string) $parsed['day'], 2, '0', STR_PAD_LEFT);
    }
}
```

---

### 5. Exception

#### `AnonymizationException.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Anonymization Exception
 *
 * Thrown when anonymization or pseudonymization operations fail.
 */
class AnonymizationException extends CryptoException
{
    public static function invalidMethod(string $method): self
    {
        return new self("Invalid anonymization method: {$method}");
    }

    public static function missingOption(string $option, string $method): self
    {
        return new self("{$method} anonymization requires '{$option}' option");
    }

    public static function pseudonymizationFailed(string $reason): self
    {
        return new self("Pseudonymization failed: {$reason}");
    }

    public static function dePseudonymizationFailed(string $reason): self
    {
        return new self("De-pseudonymization failed: {$reason}");
    }
}
```

---

## 📋 Requirements to Add to REQUIREMENTS.md

| Code | Type | Requirement Statement | Files | Status |
|------|------|----------------------|-------|--------|
| BUS-CRY-1009 | Business | Support irreversible data anonymization with multiple methods | `src/Services/Anonymizer.php` | 🔵 New |
| BUS-CRY-1010 | Business | Support reversible pseudonymization with key management | `src/Services/Anonymizer.php` | 🔵 New |
| BUS-CRY-1011 | Business | Support data masking for common PII patterns | `src/Services/DataMasker.php` | 🔵 New |
| BUS-CRY-1012 | Business | Support custom masking patterns | `src/Services/DataMasker.php` | 🔵 New |
| FUN-CRY-2012 | Functional | Provide `anonymize()` method with method selection | `src/Contracts/AnonymizerInterface.php` | 🔵 New |
| FUN-CRY-2013 | Functional | Provide `pseudonymize()` / `dePseudonymize()` methods | `src/Contracts/AnonymizerInterface.php` | 🔵 New |
| FUN-CRY-2014 | Functional | Provide `mask()` method with pattern selection | `src/Contracts/DataMaskerInterface.php` | 🔵 New |
| FUN-CRY-2015 | Functional | Provide specialized masking methods (email, phone, card) | `src/Contracts/DataMaskerInterface.php` | 🔵 New |
| SEC-CRY-3006 | Security | Anonymization output must not leak original data length (except metadata) | `src/Services/Anonymizer.php` | 🔵 New |
| SEC-CRY-3007 | Security | Pseudonymization must use authenticated encryption | `src/Services/Anonymizer.php` | 🔵 New |
| INT-CRY-5003 | Integration | Integrate with `Nexus\DataPrivacy` for GDPR anonymization | - | 🔵 New |

---

## 🔗 Integration with CryptoManager

Extend the existing `CryptoManager` facade to include anonymization and masking operations:

```php
// Add to CryptoManager class:

public function __construct(
    private HasherInterface $hasher,
    private SymmetricEncryptorInterface $encryptor,
    private AsymmetricSignerInterface $signer,
    private KeyGeneratorInterface $keyGenerator,
    private KeyStorageInterface $keyStorage,
    private AnonymizerInterface $anonymizer,      // NEW
    private DataMaskerInterface $masker,          // NEW
    private LoggerInterface $logger = new NullLogger(),
) {}

// =====================================================
// DATA PROTECTION OPERATIONS (NEW SECTION)
// =====================================================

/**
 * Anonymize data (irreversible)
 */
public function anonymize(
    string $data,
    AnonymizationMethod $method = AnonymizationMethod::HASH_BASED,
    array $options = []
): AnonymizedData {
    return $this->anonymizer->anonymize($data, $method, $options);
}

/**
 * Pseudonymize data (reversible with key)
 */
public function pseudonymize(string $data, string $keyId): PseudonymizedData
{
    return $this->anonymizer->pseudonymize($data, $keyId);
}

/**
 * De-pseudonymize data
 */
public function dePseudonymize(PseudonymizedData $data): string
{
    return $this->anonymizer->dePseudonymize($data);
}

/**
 * Mask data for display
 */
public function mask(string $data, MaskingPattern $pattern): string
{
    return $this->masker->mask($data, $pattern);
}

/**
 * Mask email
 */
public function maskEmail(string $email): string
{
    return $this->masker->maskEmail($email);
}

/**
 * Mask credit card
 */
public function maskCreditCard(string $cardNumber): string
{
    return $this->masker->maskCreditCard($cardNumber);
}
```

---

## 📊 Implementation Checklist

### Phase 1: Core Interfaces & Enums (Day 1)
- [ ] Create `AnonymizerInterface.php`
- [ ] Create `DataMaskerInterface.php`
- [ ] Create `AnonymizationMethod.php` enum
- [ ] Create `MaskingPattern.php` enum

### Phase 2: Value Objects (Day 1)
- [ ] Create `AnonymizedData.php`
- [ ] Create `PseudonymizedData.php`
- [ ] Create `AnonymizationException.php`

### Phase 3: Services (Day 2-3)
- [ ] Implement `Anonymizer.php`
- [ ] Implement `DataMasker.php`
- [ ] Extend `CryptoManager.php` with new methods

### Phase 4: Tests (Day 3-4)
- [ ] Write `AnonymizerTest.php`
- [ ] Write `DataMaskerTest.php`
- [ ] Write `AnonymizedDataTest.php`
- [ ] Write `PseudonymizedDataTest.php`

### Phase 5: Documentation (Day 4)
- [ ] Update `README.md` with new features
- [ ] Update `REQUIREMENTS.md` with new requirements
- [ ] Update `IMPLEMENTATION_SUMMARY.md`

---

## 🎯 Success Criteria

After implementation:

1. ✅ `Nexus\DataPrivacy` can use `AnonymizerInterface` for GDPR erasure anonymization
2. ✅ `Nexus\AuditLogger` can mask sensitive data in audit logs using `DataMaskerInterface`
3. ✅ BankAccount package can use masking for account number display
4. ✅ All masking patterns work correctly for international formats
5. ✅ Pseudonymization is fully reversible with proper key
6. ✅ Package remains atomic and framework-agnostic

---

## 📚 References

- [GDPR Article 17 - Right to Erasure](https://gdpr-info.eu/art-17-gdpr/)
- [GDPR Recital 26 - Anonymization](https://gdpr-info.eu/recitals/no-26/)
- [NIST SP 800-188 - De-Identifying Government Data](https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-188.pdf)
- [ISO/IEC 20889:2018 - Privacy enhancing data de-identification](https://www.iso.org/standard/69373.html)

---

**Document Status:** 📋 PLANNING  
**Estimated Effort:** 4 days (~400 LOC + tests + docs)  
**Next Steps:** Review with architecture team, then implement
