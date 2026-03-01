<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use InvalidArgumentException;
use Nexus\Loyalty\Contracts\AdjustmentServiceInterface;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Psr\Log\LoggerInterface;

/**
 * Service for manual point adjustments with mandatory auditing.
 * Requirement: FUN-LOY-401
 */
final readonly class AdjustmentService implements AdjustmentServiceInterface
{
    private const string HASH_ALGO = 'sha256';

    /**
     * List of metadata keys that are safe to log in audit trails.
     * These keys are chosen because they represent operational or debug info
     * (e.g., channel, source) and do not contain PII, tokens, or sensitive identifiers.
     * The sanitizeMetadata() method uses this allowlist to filter out any potentially
     * sensitive metadata before logging.
     */
    private const array ALLOWED_METADATA_KEYS = ['channel', 'source', 'campaign_id'];

    /**
     * @param LoggerInterface $auditLogger PSR-3 Logger for audit trails.
     * @param string $secretKey Secret key for identifier pseudonymization.
     * @throws InvalidArgumentException If secretKey is empty or whitespace.
     */
    public function __construct(
        private LoggerInterface $auditLogger,
        private string $secretKey
    ) {
        if (trim($this->secretKey) === '') {
            throw new InvalidArgumentException("AdjustmentService requires a non-empty secretKey for pseudonymization.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function adjustPoints(
        LoyaltyProfile $profile,
        int $points,
        string $reasonCode,
        string $adminId,
        array $metadata = []
    ): int {
        // Compute clamped new balance (never negative)
        $newBalance = max(0, $profile->balance->totalAvailable + $points);

        // Pseudonymize identifiers and sanitize metadata for privacy (SEC-LOY-001)
        $this->auditLogger->info("Manual point adjustment performed.", [
            'member_id' => $this->hashIdentifier($profile->memberId),
            'tenant_id' => $this->hashIdentifier($profile->tenantId),
            'points_delta' => $points,
            'reason_code' => $reasonCode,
            'admin_id' => $this->hashIdentifier($adminId),
            'previous_balance' => $profile->balance->totalAvailable,
            'new_balance' => $newBalance,
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);

        return $newBalance;
    }

    /**
     * Deterministic pseudonymization of sensitive identifiers.
     */
    private function hashIdentifier(string $id): string
    {
        return hash_hmac(self::HASH_ALGO, $id, $this->secretKey);
    }

    /**
     * Sanitize metadata to include only allowed keys and mask values if necessary.
     */
    private function sanitizeMetadata(array $metadata): array
    {
        return array_intersect_key($metadata, array_flip(self::ALLOWED_METADATA_KEYS));
    }
}
