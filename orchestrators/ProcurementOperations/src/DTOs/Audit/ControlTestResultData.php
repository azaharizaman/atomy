<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

use Nexus\ProcurementOperations\Enums\ControlArea;

/**
 * DTO representing a control test result for SOX 404 compliance.
 */
final readonly class ControlTestResultData
{
    /**
     * @param string $testId Test identifier
     * @param string $tenantId Tenant context
     * @param ControlArea $controlArea Control area being tested
     * @param string $testProcedure Description of test procedure
     * @param int $sampleSize Number of items tested
     * @param int $exceptionsFound Number of exceptions identified
     * @param bool $isPassing Whether control test passed
     * @param string $testedBy Auditor who performed test
     * @param \DateTimeImmutable $testedAt When test was performed
     * @param \DateTimeImmutable $periodStart Period start date covered
     * @param \DateTimeImmutable $periodEnd Period end date covered
     * @param string|null $conclusion Test conclusion narrative
     * @param array $exceptions Details of exceptions found
     * @param array $workpaperReferences Audit workpaper references
     * @param array $metadata Additional test metadata
     */
    public function __construct(
        public string $testId,
        public string $tenantId,
        public ControlArea $controlArea,
        public string $testProcedure,
        public int $sampleSize,
        public int $exceptionsFound,
        public bool $isPassing,
        public string $testedBy,
        public \DateTimeImmutable $testedAt,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public ?string $conclusion = null,
        public array $exceptions = [],
        public array $workpaperReferences = [],
        public array $metadata = [],
    ) {}

    /**
     * Calculate exception rate as percentage.
     */
    public function getExceptionRate(): float
    {
        if ($this->sampleSize === 0) {
            return 0.0;
        }

        return round(($this->exceptionsFound / $this->sampleSize) * 100, 2);
    }

    /**
     * Check if exception rate exceeds threshold.
     */
    public function exceedsTolerableRate(float $tolerableRate = 5.0): bool
    {
        return $this->getExceptionRate() > $tolerableRate;
    }

    /**
     * Get test effectiveness rating.
     */
    public function getEffectivenessRating(): string
    {
        $exceptionRate = $this->getExceptionRate();

        if ($exceptionRate === 0.0) {
            return 'EFFECTIVE';
        }

        if ($exceptionRate <= 2.0) {
            return 'EFFECTIVE_WITH_MINOR_EXCEPTIONS';
        }

        if ($exceptionRate <= 5.0) {
            return 'EFFECTIVE_WITH_EXCEPTIONS';
        }

        if ($exceptionRate <= 10.0) {
            return 'DEFICIENT';
        }

        return 'NOT_EFFECTIVE';
    }

    /**
     * Get COSO component for this control.
     */
    public function getCOSOComponent(): string
    {
        return $this->controlArea->getCOSOComponent();
    }

    /**
     * Get control objective.
     */
    public function getControlObjective(): string
    {
        return $this->controlArea->getObjective();
    }

    /**
     * Check if this is a key control.
     */
    public function isKeyControl(): bool
    {
        return $this->controlArea->isKeyControl();
    }

    /**
     * Get number of items passed.
     */
    public function getItemsPassed(): int
    {
        return $this->sampleSize - $this->exceptionsFound;
    }

    /**
     * Get pass rate as percentage.
     */
    public function getPassRate(): float
    {
        if ($this->sampleSize === 0) {
            return 0.0;
        }

        return round(($this->getItemsPassed() / $this->sampleSize) * 100, 2);
    }

    /**
     * Get period covered description.
     */
    public function getPeriodCovered(): string
    {
        return sprintf(
            '%s to %s',
            $this->periodStart->format('Y-m-d'),
            $this->periodEnd->format('Y-m-d'),
        );
    }

    /**
     * Check if test meets minimum sample size.
     */
    public function meetsMinimumSampleSize(): bool
    {
        return $this->sampleSize >= $this->controlArea->getTestSampleSize();
    }

    /**
     * Get sample size gap if below minimum.
     */
    public function getSampleSizeGap(): int
    {
        $minimum = $this->controlArea->getTestSampleSize();
        return max(0, $minimum - $this->sampleSize);
    }

    /**
     * Convert to audit workpaper format.
     */
    public function toWorkpaperFormat(): array
    {
        return [
            'test_reference' => $this->testId,
            'control_area' => $this->controlArea->value,
            'control_objective' => $this->getControlObjective(),
            'coso_component' => $this->getCOSOComponent(),
            'is_key_control' => $this->isKeyControl(),
            'test_procedure' => $this->testProcedure,
            'period_covered' => $this->getPeriodCovered(),
            'sample_size' => $this->sampleSize,
            'minimum_sample' => $this->controlArea->getTestSampleSize(),
            'exceptions_found' => $this->exceptionsFound,
            'exception_rate' => $this->getExceptionRate() . '%',
            'effectiveness_rating' => $this->getEffectivenessRating(),
            'test_result' => $this->isPassing ? 'PASS' : 'FAIL',
            'tested_by' => $this->testedBy,
            'tested_at' => $this->testedAt->format('Y-m-d H:i:s'),
            'conclusion' => $this->conclusion,
            'workpaper_references' => $this->workpaperReferences,
        ];
    }
}
