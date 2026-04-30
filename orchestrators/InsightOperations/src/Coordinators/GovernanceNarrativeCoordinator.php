<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\GovernanceFactsPortInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\InsightResultDto;
use Nexus\InsightOperations\Services\FactHasher;
use Throwable;

final readonly class GovernanceNarrativeCoordinator
{
    private const FEATURE_KEY = 'governance_ai_narrative';
    private const CAPABILITY_GROUP = 'governance_intelligence';
    private const SUBJECT_TYPE = 'vendor_governance';
    private const ARTIFACT_FIELD = 'ai_narrative';

    /**
     * @var list<string>
     */
    private const PROVIDER_SAFE_RECORD_KEYS = [
        'domain',
        'type',
        'source',
        'status',
        'review_status',
        'severity',
        'issue_type',
        'category',
        'observed_at',
        'issued_at',
        'expires_at',
        'opened_at',
        'closed_at',
        'screened_at',
        'reviewed_at',
        'completed_at',
        'due_at',
        'remediation_due_at',
        'date',
        'dates',
        'score',
        'scores',
        'warning_flags',
        'has_notes',
        'has_resolution_summary',
    ];

    /**
     * @var list<string>
     */
    private const ACTOR_FIELD_KEYS = [
        'actor_id',
        'actor_name',
        'actor_email',
        'actor_phone',
        'reviewer_id',
        'reviewer_name',
        'reviewer_email',
        'reviewer_phone',
        'reviewed_by',
        'opened_by',
        'remediation_owner',
        'created_by',
        'updated_by',
    ];

    public function __construct(
        private GovernanceFactsPortInterface $factsPort,
        private AiArtifactCachePortInterface $cachePort,
        private AiAvailabilityPortInterface $availabilityPort,
        private InsightNarrativePortInterface $narrativePort,
        private FactHasher $factHasher = new FactHasher(),
        private int $artifactTtlSeconds = 3600,
    ) {}

    public function show(string $tenantId, string $vendorId): InsightResultDto
    {
        $facts = $this->factsPort->factsForVendor($tenantId, $vendorId)->toArray();
        $sourceFacts = $this->providerFacts($facts);
        $sourceFactsHash = $this->factHasher->hash($sourceFacts);
        $artifact = $this->cachePort->get($this->cacheKey($tenantId, $vendorId, $sourceFactsHash))
            ?? $this->unavailable($sourceFacts, $sourceFactsHash, ['no_cached_ai_artifact']);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    public function generate(string $tenantId, string $vendorId, string $actorId): InsightResultDto
    {
        $facts = $this->factsPort->factsForVendor($tenantId, $vendorId)->toArray();
        $sourceFacts = $this->providerFacts($facts);
        $sourceFactsHash = $this->factHasher->hash($sourceFacts);

        if (! $this->availabilityPort->isFeatureAvailable(self::FEATURE_KEY)) {
            return new InsightResultDto(
                $facts,
                $this->unavailable($sourceFacts, $sourceFactsHash, $this->availabilityReasonCodes()),
                self::ARTIFACT_FIELD,
            );
        }

        try {
            $artifact = $this->narrativePort
                ->generate(self::FEATURE_KEY, $tenantId, self::SUBJECT_TYPE, $actorId, $sourceFacts)
                ->withSourceFacts($sourceFacts, $sourceFactsHash, $actorId);
        } catch (Throwable) {
            return new InsightResultDto(
                $facts,
                $this->unavailable($sourceFacts, $sourceFactsHash, ['provider_unavailable']),
                self::ARTIFACT_FIELD,
            );
        }

        $this->cachePort->put($this->cacheKey($tenantId, $vendorId, $sourceFactsHash), $artifact, $this->artifactTtlSeconds);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    /**
     * @param array<string, mixed> $facts
     * @return array<string, mixed>
     */
    private function providerFacts(array $facts): array
    {
        return [
            'vendor_id_hash' => hash('sha256', (string) ($facts['vendor_id'] ?? '')),
            'evidence' => $this->sanitizeRecordList($facts['evidence'] ?? []),
            'findings' => $this->sanitizeRecordList($facts['findings'] ?? []),
            'summary_scores' => $facts['summary_scores'] ?? $facts['scores'] ?? [],
            'warning_flags' => $facts['warning_flags'] ?? [],
            'sanctions_screenings' => $this->sanitizeRecordList($facts['sanctions_screenings'] ?? []),
            'due_diligence_status' => $facts['due_diligence_status'] ?? null,
            'evidence_freshness' => $facts['evidence_freshness'] ?? [],
        ];
    }

    /**
     * @param mixed $records
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeRecordList(mixed $records): array
    {
        if (! is_array($records)) {
            return [];
        }

        $sanitized = [];
        foreach ($records as $record) {
            if (is_array($record)) {
                $sanitized[] = $this->sanitizeRecord($record);
            }
        }

        return $sanitized;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function sanitizeRecord(array $record): array
    {
        $safe = [];
        foreach (self::PROVIDER_SAFE_RECORD_KEYS as $key) {
            if (array_key_exists($key, $record)) {
                $safe[$key] = $record[$key];
            }
        }

        $actorFields = [];
        foreach (self::ACTOR_FIELD_KEYS as $key) {
            if (array_key_exists($key, $record) && $record[$key] !== null && $record[$key] !== '') {
                $actorFields[$key] = (string) $record[$key];
            }
        }

        foreach ($actorFields as $key => $value) {
            $safe[$key . '_hash'] = hash('sha256', strtolower(trim($value)));
        }

        ksort($safe);

        return $safe;
    }

    private function cacheKey(string $tenantId, string $vendorId, string $sourceFactsHash): string
    {
        return self::FEATURE_KEY . ':' . hash('sha256', json_encode([$tenantId, $vendorId, $sourceFactsHash], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $sourceFacts
     * @param list<string> $reasonCodes
     */
    private function unavailable(array $sourceFacts, string $sourceFactsHash, array $reasonCodes): AiArtifactDto
    {
        return AiArtifactDto::unavailable(
            self::FEATURE_KEY,
            self::CAPABILITY_GROUP,
            $sourceFacts,
            $sourceFactsHash,
            $reasonCodes,
        );
    }

    /**
     * @return list<string>
     */
    private function availabilityReasonCodes(): array
    {
        $reasonCodes = $this->availabilityPort->reasonCodes(self::FEATURE_KEY);

        return $reasonCodes === [] ? ['ai_unavailable'] : $reasonCodes;
    }
}
