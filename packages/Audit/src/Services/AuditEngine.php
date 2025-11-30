<?php

declare(strict_types=1);

namespace Nexus\Audit\Services;

use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\Contracts\AuditSequenceManagerInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Contracts\AuditVerifierInterface;
use Nexus\Audit\Exceptions\HashChainException;
use Nexus\Audit\ValueObjects\AuditLevel;
use Nexus\Audit\ValueObjects\AuditSignature;
use Nexus\Audit\ValueObjects\RetentionPolicy;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\ValueObjects\HashAlgorithm;
use Symfony\Component\Uid\Ulid;

/**
 * Audit Engine Service
 * 
 * Core service for creating cryptographically-verified, immutable audit records.
 * Implements hash chain logic and dual-mode logging (sync/async).
 * 
 * Satisfies: SEC-AUD-0486, SEC-AUD-0490, REL-AUD-0301
 */
final readonly class AuditEngine implements AuditEngineInterface
{
    public function __construct(
        private AuditStorageInterface $storage,
        private AuditSequenceManagerInterface $sequenceManager,
        private AuditVerifierInterface $verifier,
        private HasherInterface $hasher,
        private ?AsymmetricSignerInterface $signer = null
    ) {}

    /**
     * {@inheritDoc}
     */
    public function logSync(
        string $tenantId,
        string $recordType,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $causerType = null,
        ?string $causerId = null,
        array $properties = [],
        AuditLevel $level = AuditLevel::Medium,
        ?int $retentionDays = null,
        ?string $signedBy = null
    ): string {
        // Get next sequence number (thread-safe)
        $sequenceNumber = $this->sequenceManager->getNextSequence($tenantId);

        // Get previous record hash for chain linking
        $previousHash = $this->getLastRecordHash($tenantId);

        // Generate unique record ID
        $recordId = $this->generateRecordId();

        // Calculate retention
        $retentionPolicy = $retentionDays 
            ? RetentionPolicy::days($retentionDays)
            : RetentionPolicy::default();

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $retentionPolicy->calculateExpirationDate($createdAt);

        // Prepare record data for hashing
        $recordData = [
            'id' => $recordId,
            'tenant_id' => $tenantId,
            'sequence_number' => $sequenceNumber,
            'record_type' => $recordType,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'properties' => $properties,
            'level' => $level->value,
            'previous_hash' => $previousHash,
            'created_at' => $createdAt->format('Y-m-d H:i:s.u'),
        ];

        // Calculate cryptographic hash for this record
        $recordHash = $this->verifier->calculateRecordHash($recordData);

        // Optional digital signature for non-repudiation
        $signature = null;
        if ($signedBy && $this->signer) {
            $signatureData = $this->signRecord($recordData, $signedBy);
            $signature = $signatureData->toString();
        }

        // Store immutable record
        $fullData = array_merge($recordData, [
            'record_hash' => $recordHash,
            'signature' => $signature,
            'signed_by' => $signedBy,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        $record = $this->storage->store($fullData);

        return $record->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function logAsync(
        string $tenantId,
        string $recordType,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $causerType = null,
        ?string $causerId = null,
        array $properties = [],
        AuditLevel $level = AuditLevel::Low,
        ?int $retentionDays = null
    ): string {
        // TODO: Queue job for async processing
        // For now, delegate to sync method
        // In Atomy layer, this will dispatch a queue job
        
        return $this->logSync(
            $tenantId,
            $recordType,
            $description,
            $subjectType,
            $subjectId,
            $causerType,
            $causerId,
            $properties,
            $level,
            $retentionDays
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLastSequenceNumber(string $tenantId): ?int
    {
        return $this->sequenceManager->getCurrentSequence($tenantId);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastRecordHash(string $tenantId): ?string
    {
        $lastRecord = $this->storage->getLastRecord($tenantId);
        return $lastRecord?->getRecordHash();
    }

    /**
     * Sign record data using Ed25519
     */
    private function signRecord(array $recordData, string $signedBy): AuditSignature
    {
        if (!$this->signer) {
            throw new \RuntimeException('Signature requested but no signer configured');
        }

        // Serialize data for signing
        $dataToSign = json_encode($recordData, JSON_THROW_ON_ERROR);

        // Generate signature (requires private key - injected via signer)
        $signedData = $this->signer->sign($dataToSign, $signedBy);

        return AuditSignature::ed25519(
            $signedData->getSignature(),
            $signedBy
        );
    }

    /**
     * Generate unique record ID (ULID)
     */
    private function generateRecordId(): string
    {
        return (string) new Ulid();
    }
}
