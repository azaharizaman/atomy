<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessResultInterface;
use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessValidatorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\ValueObjects\ComparisonReadinessResult;

/**
 * Enforces pre-comparison business rules for both preview and final runs.
 */
final readonly class ComparisonReadinessValidator implements ComparisonReadinessValidatorInterface
{
    private const MIN_VENDORS_FOR_FINAL_RUN = 2;
    private const MIN_AI_CONFIDENCE_THRESHOLD = 0.5;

    public function __construct(
        private OrchestratorProcurementManagerInterface $procurementManager,
    ) {
    }

    public function validate(
        string $tenantId,
        string $rfqId,
        array $vendorLineSets,
        bool $isPreview = false
    ): ComparisonReadinessResultInterface {
        $blockers = [];
        $warnings = [];

        $this->checkRfqClosingDate($rfqId, $isPreview, $blockers, $warnings);
        $this->checkMinimumVendors($vendorLineSets, $isPreview, $blockers, $warnings);
        $this->checkNormalizationCompleteness($vendorLineSets, $isPreview, $blockers, $warnings);
        $this->checkAiConfidence($vendorLineSets, $isPreview, $blockers, $warnings);

        if ($blockers !== []) {
            return ComparisonReadinessResult::blocked($blockers, $warnings);
        }

        /**
         * Dual-flag pattern: ready=true with previewOnly=true means no blocking errors,
         * but warnings suggest restricting execution to preview-only mode.
         * Callers like BatchQuoteComparisonCoordinator check isReady() to proceed.
         */
        if ($warnings !== [] && !$isPreview) {
            return ComparisonReadinessResult::previewAllowed($warnings);
        }

        return ComparisonReadinessResult::pass();
    }

    /**
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    private function checkRfqClosingDate(
        string $rfqId,
        bool $isPreview,
        array &$blockers,
        array &$warnings
    ): void {
        $requisition = $this->procurementManager->getRequisition($rfqId);
        if ($requisition === null) {
            $blockers[] = [
                'code' => 'RFQ_NOT_FOUND',
                'message' => sprintf('Requisition "%s" not found.', $rfqId),
            ];
            return;
        }

        $lines = $requisition->getLines();
        if ($lines === []) {
            $blockers[] = [
                'code' => 'RFQ_NO_LINES',
                'message' => sprintf('Requisition "%s" has no line items.', $rfqId),
            ];
        }

        if ($isPreview) {
            return;
        }

        $closingDate = $requisition->getClosingDate();
        if ($closingDate === null) {
            $warnings[] = [
                'code' => 'RFQ_NO_CLOSING_DATE',
                'message' => 'Requisition has no closing date; final run permitted but recommend setting one.',
            ];
            return;
        }

        $now = new \DateTimeImmutable();
        if ($now < $closingDate) {
            $blockers[] = [
                'code' => 'RFQ_NOT_CLOSED',
                'message' => sprintf(
                    'RFQ closing date "%s" has not been reached yet.',
                    $closingDate->format('Y-m-d H:i:s'),
                ),
            ];
        }
    }

    /**
     * @param array<int, array{vendor_id: string, lines: array}> $vendorLineSets
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    private function checkMinimumVendors(
        array $vendorLineSets,
        bool $isPreview,
        array &$blockers,
        array &$warnings
    ): void {
        $vendorCount = count($vendorLineSets);

        if ($vendorCount === 0) {
            $blockers[] = [
                'code' => 'NO_VENDORS',
                'message' => 'At least one vendor is required for a comparison run.',
            ];
            return;
        }

        if (!$isPreview && $vendorCount < self::MIN_VENDORS_FOR_FINAL_RUN) {
            $blockers[] = [
                'code' => 'INSUFFICIENT_VENDORS',
                'message' => sprintf(
                    'Final runs require at least %d vendors; only %d provided.',
                    self::MIN_VENDORS_FOR_FINAL_RUN,
                    $vendorCount,
                ),
            ];
        }
    }

    /**
     * @param array<int, array{vendor_id: string, lines: array}> $vendorLineSets
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    private function checkNormalizationCompleteness(
        array $vendorLineSets,
        bool $isPreview,
        array &$blockers,
        array &$warnings
    ): void {
        foreach ($vendorLineSets as $vendorSet) {
            $vendorId = (string) ($vendorSet['vendor_id'] ?? '');
            $lines = $vendorSet['lines'] ?? [];

            $normalizedCount = 0;
            foreach ($lines as $line) {
                if ($line instanceof NormalizedQuoteLine) {
                    $normalizedCount++;
                }
            }

            if ($normalizedCount === 0 && $lines !== []) {
                $entry = [
                    'code' => 'NORMALIZATION_INCOMPLETE',
                    'message' => sprintf(
                        'Vendor "%s" has %d raw lines but none are normalized.',
                        $vendorId,
                        count($lines),
                    ),
                ];

                if ($isPreview) {
                    $warnings[] = $entry;
                } else {
                    $blockers[] = $entry;
                }
            }
        }
    }

    /**
     * @param array<int, array{vendor_id: string, lines: array}> $vendorLineSets
     * @param array<int, array{code: string, message: string}> $blockers
     * @param array<int, array{code: string, message: string}> $warnings
     */
    private function checkAiConfidence(
        array $vendorLineSets,
        bool $isPreview,
        array &$blockers,
        array &$warnings
    ): void {
        foreach ($vendorLineSets as $vendorSet) {
            $vendorId = (string) ($vendorSet['vendor_id'] ?? '');
            $lines = $vendorSet['lines'] ?? [];

            foreach ($lines as $line) {
                if (!$line instanceof NormalizedQuoteLine) {
                    continue;
                }

                if ($line->aiConfidence < self::MIN_AI_CONFIDENCE_THRESHOLD) {
                    $entry = [
                        'code' => 'LOW_AI_CONFIDENCE',
                        'message' => sprintf(
                            'Vendor "%s", line "%s": AI confidence %.2f is below %.2f threshold.',
                            $vendorId,
                            $line->rfqLineId !== '' ? $line->rfqLineId : $line->taxonomyCode,
                            $line->aiConfidence,
                            self::MIN_AI_CONFIDENCE_THRESHOLD,
                        ),
                    ];

                    if ($isPreview) {
                        $warnings[] = $entry;
                    } else {
                        $blockers[] = $entry;
                    }
                }
            }
        }
    }
}
