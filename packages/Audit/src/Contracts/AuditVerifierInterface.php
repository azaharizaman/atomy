<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

/**
 * Audit Verifier Interface
 * 
 * Service for verifying hash chain integrity, detecting tampering,
 * and validating digital signatures.
 * 
 * Satisfies: SEC-AUD-0490 (Cryptographic verification)
 */
interface AuditVerifierInterface
{
    /**
     * Verify entire hash chain for a tenant
     * 
     * Checks:
     * - Each record's hash is valid (recalculation matches stored hash)
     * - Each record's previous_hash matches actual previous record's hash
     * - No gaps in sequence numbers
     * 
     * @param string $tenantId
     * @return bool True if chain is valid
     * 
     * @throws \Nexus\Audit\Exceptions\AuditTamperedException If tampering detected
     * @throws \Nexus\Audit\Exceptions\AuditSequenceException If sequence gaps found
     */
    public function verifyChainIntegrity(string $tenantId): bool;

    /**
     * Verify specific audit record
     * 
     * Recalculates hash from record data and compares with stored hash.
     * 
     * @param AuditRecordInterface $record
     * @return bool True if valid
     * 
     * @throws \Nexus\Audit\Exceptions\AuditTamperedException If hash mismatch
     */
    public function verifyRecord(AuditRecordInterface $record): bool;

    /**
     * Verify digital signature (if present)
     * 
     * @param AuditRecordInterface $record
     * @return bool True if signature is valid
     * 
     * @throws \Nexus\Audit\Exceptions\SignatureVerificationException
     */
    public function verifySignature(AuditRecordInterface $record): bool;

    /**
     * Detect gaps in sequence numbers for a tenant
     * 
     * Returns array of missing sequence numbers, indicating
     * potential deletion attacks.
     * 
     * @param string $tenantId
     * @return array<int> Array of missing sequence numbers
     */
    public function detectSequenceGaps(string $tenantId): array;

    /**
     * Calculate hash for record data
     * 
     * Used for verification - should produce same result as original calculation.
     * 
     * @param array $data Record data
     * @return string SHA-256 hash
     */
    public function calculateRecordHash(array $data): string;

    /**
     * Get integrity report
     *
     * Returns a comprehensive integrity report for a tenant's audit records,
     * including chain verification results, tampering detection, and statistics.
     *
     * @param string $tenantId Tenant identifier
     * @return array<string, mixed> Integrity report including:
     *         - tenant_id: string
     *         - verification_status: string ('valid', 'tampered', 'error')
     *         - total_records: int
     *         - verified_records: int
     *         - failed_records: int
     *         - sequence_gaps: array<int>
     *         - tampered_records: array<string>
     *         - last_verified_at: string
     *         - chain_integrity: bool
     */
    public function getIntegrityReport(string $tenantId): array;
}
