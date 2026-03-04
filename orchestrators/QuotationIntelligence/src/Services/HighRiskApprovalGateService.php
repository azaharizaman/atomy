<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\ApprovalGateServiceInterface;

/**
 * Requires human approval when risk or confidence thresholds are not met.
 */
final readonly class HighRiskApprovalGateService implements ApprovalGateServiceInterface
{
    private const MIN_TOP_SCORE_FOR_AUTO_APPROVAL = 70.0;

    /**
     * @inheritDoc
     */
    public function evaluate(array $vendors, array $scoring): array
    {
        $reasons = [];
        $hasHighRisk = false;

        foreach ($vendors as $vendor) {
            $vendorId = (string)($vendor['vendor_id'] ?? '');
            $risks = is_array($vendor['risks'] ?? null) ? $vendor['risks'] : [];
            foreach ($risks as $risk) {
                $level = strtolower((string)($risk['level'] ?? ''));
                if ($level === 'high') {
                    $hasHighRisk = true;
                    $reasons[] = sprintf('High risk detected for vendor "%s".', $vendorId);
                    break;
                }
            }
        }

        $topScore = $this->extractTopScore($scoring);
        if ($topScore < self::MIN_TOP_SCORE_FOR_AUTO_APPROVAL) {
            $reasons[] = sprintf(
                'Top vendor score %.2f is below auto-approval threshold %.2f.',
                $topScore,
                self::MIN_TOP_SCORE_FOR_AUTO_APPROVAL
            );
        }

        $required = $hasHighRisk || $topScore < self::MIN_TOP_SCORE_FOR_AUTO_APPROVAL;

        return [
            'required' => $required,
            'status' => $required ? 'pending_approval' : 'auto_approved',
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    /**
     * @param array<string, mixed> $scoring
     */
    private function extractTopScore(array $scoring): float
    {
        $ranking = is_array($scoring['ranking'] ?? null) ? $scoring['ranking'] : [];
        if ($ranking === []) {
            return 0.0;
        }

        $first = $ranking[0];
        if (!is_array($first)) {
            return 0.0;
        }

        $score = $first['total_score'] ?? 0.0;
        return is_numeric($score) ? (float)$score : 0.0;
    }
}

