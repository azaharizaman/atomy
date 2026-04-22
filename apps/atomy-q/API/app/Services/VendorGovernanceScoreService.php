<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class VendorGovernanceScoreService
{
    /**
     * @param iterable<int, VendorEvidence|array<string, mixed>> $evidence
     * @param iterable<int, VendorFinding|array<string, mixed>> $findings
     *
     * @return array{
     *     scores: array{
     *         esg_score: int,
     *         compliance_health_score: int,
     *         risk_watch_score: int,
     *         evidence_freshness_score: int
     *     },
     *     warning_flags: list<string>
     * }
     */
    public function summarize(iterable $evidence, iterable $findings, ?CarbonInterface $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $evidenceRows = $this->normalizeEvidence($evidence);
        $findingRows = $this->normalizeFindings($findings);
        $warnings = [];

        $freshnessScore = $evidenceRows === [] ? 40 : 100;
        $esgScore = $this->hasDomainEvidence($evidenceRows, 'esg') ? 100 : 75;
        $complianceScore = $this->hasDomainEvidence($evidenceRows, 'compliance') ? 100 : 80;
        $riskScore = 100;

        if ($evidenceRows === []) {
            $warnings[] = 'governance_evidence_missing';
        }

        if (! $this->hasFreshDomainEvidence($evidenceRows, 'esg', $now, 365)) {
            $warnings[] = 'esg_review_stale';
            $esgScore -= 20;
        }

        if (! $this->hasFreshTypedEvidence($evidenceRows, 'sanctions_screening', $now, 180)) {
            $warnings[] = 'sanctions_check_due';
            $complianceScore -= 20;
        }

        foreach ($evidenceRows as $row) {
            if ($row['expires_at'] instanceof CarbonInterface && $row['expires_at']->isBefore($now)) {
                $freshnessScore -= 35;
                if ($row['domain'] === 'compliance') {
                    $complianceScore -= 35;
                    $warnings[] = 'compliance_document_expired';
                }
            }

            if ($row['observed_at'] === null || $row['observed_at']->diffInDays($now) > 365) {
                $freshnessScore -= 25;
                $warnings[] = 'stale_evidence';
            }

            if (! in_array($row['review_status'], ['reviewed', 'accepted', 'approved'], true)) {
                $freshnessScore -= 15;
                $warnings[] = 'unreviewed_evidence';
            }
        }

        foreach ($findingRows as $row) {
            if (! in_array($row['status'], ['open', 'in_progress'], true)) {
                continue;
            }

            $penalty = match ($row['severity']) {
                'critical' => 60,
                'high' => 45,
                'medium' => 20,
                'low' => 10,
                default => 15,
            };

            if ($row['domain'] === 'risk') {
                $riskScore -= $penalty;
            }

            if ($row['domain'] === 'compliance') {
                $complianceScore -= $penalty;
            }

            if ($row['domain'] === 'esg') {
                $esgScore -= $penalty;
            }

            if (in_array($row['severity'], ['critical', 'high'], true)) {
                $warnings[] = 'open_severe_' . $row['domain'] . '_finding';
            }

            if ($row['remediation_due_at'] instanceof CarbonInterface && $row['remediation_due_at']->isBefore($now)) {
                $warnings[] = 'remediation_overdue';
                $riskScore -= 10;
            }
        }

        return [
            'scores' => [
                'esg_score' => $this->bound($esgScore),
                'compliance_health_score' => $this->bound($complianceScore),
                'risk_watch_score' => $this->bound($riskScore),
                'evidence_freshness_score' => $this->bound($freshnessScore),
            ],
            'warning_flags' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param iterable<int, VendorEvidence|array<string, mixed>> $evidence
     * @return list<array{
     *     domain: string,
     *     type: string,
     *     observed_at: ?CarbonInterface,
     *     expires_at: ?CarbonInterface,
     *     review_status: string
     * }>
     */
    private function normalizeEvidence(iterable $evidence): array
    {
        $rows = [];
        foreach ($evidence as $row) {
            $rows[] = [
                'domain' => $this->normalizeText($this->value($row, 'domain')),
                'type' => $this->normalizeText($this->value($row, 'type')),
                'observed_at' => $this->dateOrNull($this->value($row, 'observed_at')),
                'expires_at' => $this->dateOrNull($this->value($row, 'expires_at')),
                'review_status' => $this->normalizeText($this->value($row, 'review_status')),
            ];
        }

        return $rows;
    }

    /**
     * @param iterable<int, VendorFinding|array<string, mixed>> $findings
     * @return list<array{
     *     domain: string,
     *     severity: string,
     *     status: string,
     *     remediation_due_at: ?CarbonInterface
     * }>
     */
    private function normalizeFindings(iterable $findings): array
    {
        $rows = [];
        foreach ($findings as $row) {
            $rows[] = [
                'domain' => $this->normalizeText($this->value($row, 'domain')),
                'severity' => $this->normalizeText($this->value($row, 'severity')),
                'status' => $this->normalizeText($this->value($row, 'status')),
                'remediation_due_at' => $this->dateOrNull($this->value($row, 'remediation_due_at')),
            ];
        }

        return $rows;
    }

    /**
     * @param list<array{domain: string, type: string, observed_at: ?CarbonInterface, expires_at: ?CarbonInterface, review_status: string}> $rows
     */
    private function hasDomainEvidence(array $rows, string $domain): bool
    {
        foreach ($rows as $row) {
            if ($row['domain'] === $domain) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{domain: string, type: string, observed_at: ?CarbonInterface, expires_at: ?CarbonInterface, review_status: string}> $rows
     */
    private function hasFreshDomainEvidence(array $rows, string $domain, CarbonInterface $now, int $maxAgeDays): bool
    {
        foreach ($rows as $row) {
            if ($row['domain'] === $domain && $row['observed_at'] instanceof CarbonInterface && $row['observed_at']->diffInDays($now) <= $maxAgeDays) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{domain: string, type: string, observed_at: ?CarbonInterface, expires_at: ?CarbonInterface, review_status: string}> $rows
     */
    private function hasFreshTypedEvidence(array $rows, string $type, CarbonInterface $now, int $maxAgeDays): bool
    {
        foreach ($rows as $row) {
            if ($row['type'] === $type && $row['observed_at'] instanceof CarbonInterface && $row['observed_at']->diffInDays($now) <= $maxAgeDays) {
                return true;
            }
        }

        return false;
    }

    private function value(VendorEvidence|VendorFinding|array $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? null;
        }

        return $row->getAttribute($key);
    }

    private function dateOrNull(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return CarbonImmutable::parse($text);
    }

    private function normalizeText(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function bound(int $score): int
    {
        return max(0, min(100, $score));
    }
}
