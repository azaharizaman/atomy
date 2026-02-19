<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Privacy;

/**
 * Context DTO for data privacy data.
 *
 * Aggregates all privacy-related data for compliance workflows.
 */
final readonly class PrivacyContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $dataSubjectId Data subject identifier
     * @param array<int, array<string, mixed>> $consents Consent records
     * @param array<int, array<string, mixed>> $requests Data subject requests
     * @param bool $hasPendingRequests Whether there are pending requests
     * @param array<int, array<string, mixed>> $processingActivities Processing activities
     * @param array<string, bool> $consentStatus Consent status by purpose
     * @param \DateTimeImmutable $fetchedAt Fetch timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $dataSubjectId,
        public array $consents,
        public array $requests,
        public bool $hasPendingRequests,
        public array $processingActivities,
        public array $consentStatus,
        public \DateTimeImmutable $fetchedAt,
    ) {}

    /**
     * Check if consent is granted for a purpose.
     */
    public function hasConsentFor(string $purpose): bool
    {
        return $this->consentStatus[$purpose] ?? false;
    }

    /**
     * Check if there's an erasure request pending.
     */
    public function hasPendingErasureRequest(): bool
    {
        foreach ($this->requests as $request) {
            if ($request['type'] === 'erasure' && in_array($request['status'], ['pending', 'in_progress'], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get overdue requests.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOverdueRequests(): array
    {
        return array_filter($this->requests, fn($r) => $r['isOverdue'] ?? false);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'dataSubjectId' => $this->dataSubjectId,
            'consents' => $this->consents,
            'requests' => $this->requests,
            'hasPendingRequests' => $this->hasPendingRequests,
            'processingActivities' => $this->processingActivities,
            'consentStatus' => $this->consentStatus,
            'fetchedAt' => $this->fetchedAt->format('Y-m-d H:i:s'),
        ];
    }
}
