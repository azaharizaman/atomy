# Nexus\Audit

**Cryptographically-verified, immutable audit engine for compliance and forensic analysis**

## Overview

The `Nexus\Audit` package provides an enterprise-grade, immutable audit trail with cryptographic hash chains and optional digital signatures. This package is the **compliance engine** designed for high-integrity, legally-defensible audit logging where tampering detection and state verification are critical.

## Key Features

✅ **Cryptographic Hash Chains** - Each audit record links to the previous via SHA-256 hashes  
✅ **Optional Digital Signatures** - Ed25519 signatures for non-repudiation  
✅ **Per-Tenant Isolation** - Separate hash chains and sequences per tenant  
✅ **Dual-Mode Logging** - Synchronous for critical events, async for high-volume  
✅ **Tamper Detection** - Automatic verification on retrieval  
✅ **Sequence Integrity** - Gap detection and monotonic ordering  
✅ **Framework-Agnostic** - Pure PHP 8.3+ with zero Laravel dependencies  
✅ **Compliance-Ready** - Meets SEC-AUD-0486, SEC-AUD-0490 requirements  

## Core Differences from AuditLogger

| Feature | Nexus\Audit (Engine) | Nexus\AuditLogger (Utility) |
|---------|---------------------|----------------------------|
| **Purpose** | Immutable, verifiable audit trail | User-friendly search/export/timeline |
| **Immutability** | Cryptographic hash chain enforcement | Append-only by convention |
| **Data Storage** | Raw, unmasked data for forensics | Masked data for display |
| **Write Mode** | Sync (critical) + Async (bulk) | Primarily async |
| **Verification** | Hash chain + signature verification | None |
| **Use Case** | Compliance, legal, security events | Activity feeds, debugging, reporting |

## Architecture

### Contracts

- **`AuditEngineInterface`** - Core logging engine with sync/async methods
- **`AuditRecordInterface`** - Immutable audit record with hash chain fields
- **`AuditStorageInterface`** - Append-only persistence layer
- **`AuditVerifierInterface`** - Hash chain and signature verification
- **`AuditSequenceManagerInterface`** - Per-tenant sequence management

### Value Objects

- **`AuditHash`** - Immutable hash result (value + algorithm)
- **`AuditSignature`** - Digital signature container
- **`SequenceNumber`** - Tenant-scoped sequence tracking
- **`AuditLevel`** - Severity levels (Low=1, Medium=2, High=3, Critical=4)
- **`RetentionPolicy`** - Compliance-driven retention periods

### Services

- **`AuditEngine`** - Main orchestrator with hash chain logic
- **`HashChainVerifier`** - Integrity verification service
- **`AuditSequenceManager`** - Sequence number management
- **`RetentionPolicyService`** - Automated purging

## Installation

```bash
composer require nexus/audit:"*@dev"
```

## Usage

### Synchronous Logging (Critical Events)

```php
use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\ValueObjects\AuditLevel;

$auditEngine = app(AuditEngineInterface::class);

// Critical security event - blocks until written with hash chain
$recordId = $auditEngine->logSync(
    tenantId: '01TENANT...',
    recordType: 'user_role_assigned',
    description: 'User John Doe assigned role: Administrator',
    subjectType: 'User',
    subjectId: '01USER...',
    causerType: 'User',
    causerId: '01ADMIN...',
    properties: ['role_id' => '01ROLE...', 'role_name' => 'Administrator'],
    level: AuditLevel::Critical
);
```

### Asynchronous Logging (Bulk Operations)

```php
// Non-critical access log - queued for async processing
$auditEngine->logAsync(
    tenantId: '01TENANT...',
    recordType: 'document_viewed',
    description: 'User viewed document',
    subjectType: 'Document',
    subjectId: '01DOC...',
    properties: ['ip_address' => '192.168.1.1'],
    level: AuditLevel::Low
);
```

### Hash Chain Verification

```php
use Nexus\Audit\Contracts\AuditVerifierInterface;

$verifier = app(AuditVerifierInterface::class);

// Verify entire chain for a tenant
$isValid = $verifier->verifyChainIntegrity('01TENANT...');

// Detect sequence gaps (potential deletion)
$gaps = $verifier->detectSequenceGaps('01TENANT...');

// Verify specific record
$record = $auditStorage->findById($recordId);
$verifier->verifyRecord($record); // Throws AuditTamperedException if invalid
```

## Security Requirements Satisfied

- **SEC-AUD-0486** - Immutable audit logs (cryptographically enforced)
- **SEC-AUD-0490** - Cryptographic verification (hash chains + signatures)
- **SEC-AUD-0487** - Strict tenant isolation (per-tenant hash chains)
- **REL-AUD-0301** - Log sequence integrity (monotonic ordering)

## Integration with Other Packages

- **Nexus\Crypto** - SHA-256 hashing and Ed25519 signatures
- **Nexus\AuditLogger** - Presentation layer built on Audit engine
- **Nexus\Identity** - Critical identity events logged via Audit
- **Nexus\Finance** - Financial transactions logged synchronously

## Documentation

### Quick Links

- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide for new users
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Integration Guide](docs/integration-guide.md)** - Framework integration examples (Laravel, Symfony)
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple invoice audit workflow
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Digital signatures, async logging, retention policies

### Package Documentation

- **[Requirements](REQUIREMENTS.md)** - Comprehensive requirements traceability (98 requirements)
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Implementation progress and metrics
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and strategy (77 tests planned)
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation and ROI analysis ($200K valuation)

### Additional Resources

- **Architecture:** Cryptographic hash chains with SHA-256, Ed25519 signatures, per-tenant isolation
- **Compliance:** SOX, GDPR, HIPAA audit requirements
- **Security:** Tamper detection, forensic investigation, non-repudiation

## License

MIT License - See LICENSE file for details
