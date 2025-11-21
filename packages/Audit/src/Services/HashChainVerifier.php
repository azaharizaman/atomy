<?php

declare(strict_types=1);

namespace Nexus\Audit\Services;

use Nexus\Audit\Contracts\AuditRecordInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Contracts\AuditVerifierInterface;
use Nexus\Audit\Exceptions\AuditSequenceException;
use Nexus\Audit\Exceptions\AuditTamperedException;
use Nexus\Audit\Exceptions\SignatureVerificationException;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\ValueObjects\HashAlgorithm;

/**
 * Hash Chain Verifier Service
 * 
 * Verifies cryptographic integrity of audit records and hash chains.
 * Detects tampering, sequence gaps, and validates digital signatures.
 * 
 * Satisfies: SEC-AUD-0490
 */
final readonly class HashChainVerifier implements AuditVerifierInterface
{
    public function __construct(
        private AuditStorageInterface $storage,
        private HasherInterface $hasher,
        private ?AsymmetricSignerInterface $signer = null
    ) {}

    /**
     * {@inheritDoc}
     */
    public function verifyChainIntegrity(string $tenantId): bool
    {
        $records = $this->storage->findByTenantSequence($tenantId, 1, 10000);

        if (empty($records)) {
            return true; // No records = valid (empty chain)
        }

        $previousHash = null;
        $expectedSequence = 1;

        foreach ($records as $record) {
            // Verify sequence continuity
            if ($record->getSequenceNumber() !== $expectedSequence) {
                throw AuditSequenceException::outOfOrder(
                    $tenantId,
                    $expectedSequence,
                    $record->getSequenceNumber()
                );
            }

            // Verify hash chain linking
            if ($previousHash !== null && $record->getPreviousHash() !== $previousHash) {
                throw AuditTamperedException::chainBroken(
                    $record->getId(),
                    $record->getSequenceNumber()
                );
            }

            // Verify record hash
            $this->verifyRecord($record);

            $previousHash = $record->getRecordHash();
            $expectedSequence++;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function verifyRecord(AuditRecordInterface $record): bool
    {
        // Reconstruct record data for hash calculation
        $recordData = [
            'id' => $record->getId(),
            'tenant_id' => $record->getTenantId(),
            'sequence_number' => $record->getSequenceNumber(),
            'record_type' => $record->getRecordType(),
            'description' => $record->getDescription(),
            'subject_type' => $record->getSubjectType(),
            'subject_id' => $record->getSubjectId(),
            'causer_type' => $record->getCauserType(),
            'causer_id' => $record->getCauserId(),
            'properties' => $record->getProperties(),
            'level' => $record->getLevel(),
            'previous_hash' => $record->getPreviousHash(),
            'created_at' => $record->getCreatedAt()->format('Y-m-d H:i:s.u'),
        ];

        // Recalculate hash
        $calculatedHash = $this->calculateRecordHash($recordData);

        // Compare with stored hash
        if ($calculatedHash !== $record->getRecordHash()) {
            throw AuditTamperedException::hashMismatch(
                $record->getId(),
                $record->getRecordHash(),
                $calculatedHash
            );
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function verifySignature(AuditRecordInterface $record): bool
    {
        if (!$record->getSignature()) {
            return true; // No signature = nothing to verify
        }

        if (!$this->signer) {
            throw new SignatureVerificationException(
                'Signature verification requested but no signer configured'
            );
        }

        // Reconstruct signed data
        $recordData = [
            'id' => $record->getId(),
            'tenant_id' => $record->getTenantId(),
            'sequence_number' => $record->getSequenceNumber(),
            'record_type' => $record->getRecordType(),
            'description' => $record->getDescription(),
            'subject_type' => $record->getSubjectType(),
            'subject_id' => $record->getSubjectId(),
            'causer_type' => $record->getCauserType(),
            'causer_id' => $record->getCauserId(),
            'properties' => $record->getProperties(),
            'level' => $record->getLevel(),
            'previous_hash' => $record->getPreviousHash(),
            'created_at' => $record->getCreatedAt()->format('Y-m-d H:i:s.u'),
        ];

        $dataToVerify = json_encode($recordData, JSON_THROW_ON_ERROR);

        // Verify signature
        $isValid = $this->signer->verify(
            $dataToVerify,
            $record->getSignature(),
            $record->getSignedBy() ?? ''
        );

        if (!$isValid) {
            throw SignatureVerificationException::invalidSignature($record->getId());
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function detectSequenceGaps(string $tenantId): array
    {
        $records = $this->storage->findByTenantSequence($tenantId, 1, 10000);

        if (empty($records)) {
            return [];
        }

        $gaps = [];
        $expectedSequence = 1;

        foreach ($records as $record) {
            $actualSequence = $record->getSequenceNumber();

            // Check for gap
            if ($actualSequence > $expectedSequence) {
                // Add missing sequences to gaps array
                for ($i = $expectedSequence; $i < $actualSequence; $i++) {
                    $gaps[] = $i;
                }
            }

            $expectedSequence = $actualSequence + 1;
        }

        return $gaps;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRecordHash(array $data): string
    {
        // Create deterministic string representation
        // Order matters for consistent hashing
        $hashInput = implode('|', [
            $data['id'] ?? '',
            $data['tenant_id'] ?? '',
            (string) ($data['sequence_number'] ?? 0),
            $data['record_type'] ?? '',
            $data['description'] ?? '',
            $data['subject_type'] ?? '',
            $data['subject_id'] ?? '',
            $data['causer_type'] ?? '',
            $data['causer_id'] ?? '',
            json_encode($data['properties'] ?? [], JSON_THROW_ON_ERROR),
            (string) ($data['level'] ?? 0),
            $data['previous_hash'] ?? '',
            $data['created_at'] ?? '',
        ]);

        // Calculate SHA-256 hash
        $hashResult = $this->hasher->hash($hashInput, HashAlgorithm::SHA256);

        return $hashResult->getValue();
    }
}
