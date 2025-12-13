<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\Enums\ControlArea;

/**
 * Rule for validating control testing requirements.
 */
final readonly class ControlTestingRule
{
    private const int DEFAULT_MIN_SAMPLE_SIZE = 25;
    private const int KEY_CONTROL_MIN_SAMPLE = 45;
    private const float MAX_EXCEPTION_RATE = 5.0;
    private const int RETEST_SAMPLE_MULTIPLIER = 2;

    /**
     * Validate sample size requirements for control testing.
     *
     * @param ControlArea $controlArea Control area being tested
     * @param int $proposedSampleSize Proposed sample size
     * @param int $populationSize Total population size
     * @return ControlTestingRuleResult Validation result
     */
    public function validateSampleSize(
        ControlArea $controlArea,
        int $proposedSampleSize,
        int $populationSize,
    ): ControlTestingRuleResult {
        $minRequiredSample = $controlArea->isKeyControl()
            ? self::KEY_CONTROL_MIN_SAMPLE
            : self::DEFAULT_MIN_SAMPLE_SIZE;

        // PCAOB standard: For populations < 250, sample at least 10%
        if ($populationSize < 250) {
            $minRequiredSample = max($minRequiredSample, (int) ceil($populationSize * 0.10));
        }

        // Cannot sample more than population
        $effectiveMin = min($minRequiredSample, $populationSize);

        if ($proposedSampleSize < $effectiveMin) {
            return ControlTestingRuleResult::fail(
                message: "Sample size {$proposedSampleSize} is below minimum required {$effectiveMin}",
                reason: 'INSUFFICIENT_SAMPLE_SIZE',
                controlArea: $controlArea->value,
                requiredSampleSize: $effectiveMin,
                proposedSampleSize: $proposedSampleSize,
                populationSize: $populationSize,
            );
        }

        // Warn if sample is low relative to population for key controls
        if ($controlArea->isKeyControl() && $populationSize > 100) {
            $recommendedSample = (int) ceil($populationSize * 0.15);
            if ($proposedSampleSize < $recommendedSample) {
                return ControlTestingRuleResult::warn(
                    message: "Sample size meets minimum but is below recommended {$recommendedSample} for key control",
                    controlArea: $controlArea->value,
                    recommendedSampleSize: $recommendedSample,
                    proposedSampleSize: $proposedSampleSize,
                );
            }
        }

        return ControlTestingRuleResult::pass(
            message: 'Sample size meets requirements',
            controlArea: $controlArea->value,
            proposedSampleSize: $proposedSampleSize,
            minRequiredSample: $effectiveMin,
        );
    }

    /**
     * Validate exception handling for failed tests.
     *
     * @param int $exceptionsFound Number of exceptions found
     * @param int $sampleSize Total sample size
     * @param ControlArea $controlArea Control area being tested
     * @param bool $isKeyControl Whether this is a key control
     * @return ControlTestingRuleResult Validation result with recommended actions
     */
    public function validateExceptionHandling(
        int $exceptionsFound,
        int $sampleSize,
        ControlArea $controlArea,
        bool $isKeyControl = false,
    ): ControlTestingRuleResult {
        if ($sampleSize === 0) {
            return ControlTestingRuleResult::fail(
                message: 'Cannot validate exceptions with zero sample size',
                reason: 'INVALID_SAMPLE_SIZE',
                controlArea: $controlArea->value,
            );
        }

        $exceptionRate = ($exceptionsFound / $sampleSize) * 100;

        if ($exceptionsFound === 0) {
            return ControlTestingRuleResult::pass(
                message: 'No exceptions found - control operating effectively',
                controlArea: $controlArea->value,
                exceptionsFound: 0,
                exceptionRate: 0.0,
            );
        }

        // Single exception in key control requires investigation
        if ($isKeyControl && $exceptionsFound === 1) {
            return ControlTestingRuleResult::warn(
                message: 'Single exception in key control requires root cause analysis',
                controlArea: $controlArea->value,
                exceptionsFound: $exceptionsFound,
                exceptionRate: $exceptionRate,
                recommendedAction: 'INVESTIGATE_ROOT_CAUSE',
            );
        }

        // Exception rate exceeds tolerance
        if ($exceptionRate > self::MAX_EXCEPTION_RATE) {
            $expandedSampleSize = $sampleSize * self::RETEST_SAMPLE_MULTIPLIER;

            return ControlTestingRuleResult::fail(
                message: "Exception rate {$exceptionRate}% exceeds maximum tolerance of " . self::MAX_EXCEPTION_RATE . '%',
                reason: 'EXCEPTION_RATE_EXCEEDED',
                controlArea: $controlArea->value,
                exceptionsFound: $exceptionsFound,
                exceptionRate: $exceptionRate,
                toleranceRate: self::MAX_EXCEPTION_RATE,
                recommendedAction: 'EXPAND_TESTING',
                expandedSampleSize: $expandedSampleSize,
            );
        }

        // Exceptions within tolerance but require documentation
        return ControlTestingRuleResult::warn(
            message: 'Exceptions found within tolerance - require documentation',
            controlArea: $controlArea->value,
            exceptionsFound: $exceptionsFound,
            exceptionRate: $exceptionRate,
            recommendedAction: 'DOCUMENT_AND_MONITOR',
        );
    }

    /**
     * Validate test timing requirements.
     *
     * @param ControlArea $controlArea Control area
     * @param \DateTimeImmutable $lastTestDate Last test date
     * @param \DateTimeImmutable $periodEndDate Period end date
     * @return ControlTestingRuleResult Validation result
     */
    public function validateTestTiming(
        ControlArea $controlArea,
        \DateTimeImmutable $lastTestDate,
        \DateTimeImmutable $periodEndDate,
    ): ControlTestingRuleResult {
        // Calculate days between test and period end
        $interval = $lastTestDate->diff($periodEndDate);
        
        $validationResult = $this->validateIntervalDays(
            $interval,
            $controlArea,
            'days between test and period end'
        );
        if ($validationResult !== null) {
            return $validationResult;
        }
        
        $daysBetween = $interval->days;

        // Key controls must be tested within 90 days of period end
        $maxDaysBefore = $controlArea->isKeyControl() ? 90 : 120;

        if ($daysBetween > $maxDaysBefore) {
            return ControlTestingRuleResult::fail(
                message: "Test performed {$daysBetween} days before period end exceeds maximum of {$maxDaysBefore} days",
                reason: 'TEST_TOO_EARLY',
                controlArea: $controlArea->value,
                daysBetweenTestAndPeriodEnd: $daysBetween,
                maxAllowedDays: $maxDaysBefore,
                recommendedAction: 'ROLLFORWARD_REQUIRED',
            );
        }

        // Test after period end requires rollback procedures
        if ($lastTestDate > $periodEndDate) {
            $interval = $periodEndDate->diff($lastTestDate);
            
            $validationResult = $this->validateIntervalDays(
                $interval,
                $controlArea,
                'days after period end'
            );
            if ($validationResult !== null) {
                return $validationResult;
            }
            
            $daysAfter = $interval->days;

            if ($daysAfter > 60) {
                return ControlTestingRuleResult::fail(
                    message: "Test performed {$daysAfter} days after period end - too late for reliance",
                    reason: 'TEST_TOO_LATE',
                    controlArea: $controlArea->value,
                    daysAfterPeriodEnd: $daysAfter,
                );
            }

            return ControlTestingRuleResult::warn(
                message: 'Test performed after period end - rollback procedures required',
                controlArea: $controlArea->value,
                daysAfterPeriodEnd: $daysAfter,
                recommendedAction: 'PERFORM_ROLLBACK',
            );
        }

        return ControlTestingRuleResult::pass(
            message: 'Test timing meets requirements',
            controlArea: $controlArea->value,
            daysBetweenTestAndPeriodEnd: $daysBetween,
        );
    }

    /**
     * Validate tester independence.
     *
     * @param string $testerId User ID of tester
     * @param string $controlOwnerId User ID of control owner
     * @param array<string> $controlOperatorIds User IDs of control operators
     * @param ControlArea $controlArea Control area
     * @return ControlTestingRuleResult Validation result
     */
    public function validateTesterIndependence(
        string $testerId,
        string $controlOwnerId,
        array $controlOperatorIds,
        ControlArea $controlArea,
    ): ControlTestingRuleResult {
        // Tester cannot be control owner
        if ($testerId === $controlOwnerId) {
            return ControlTestingRuleResult::fail(
                message: 'Tester cannot be the control owner - independence violation',
                reason: 'TESTER_IS_OWNER',
                controlArea: $controlArea->value,
                conflictingUserId: $testerId,
            );
        }

        // Tester cannot be control operator
        if (in_array($testerId, $controlOperatorIds, true)) {
            return ControlTestingRuleResult::fail(
                message: 'Tester cannot be a control operator - independence violation',
                reason: 'TESTER_IS_OPERATOR',
                controlArea: $controlArea->value,
                conflictingUserId: $testerId,
            );
        }

        return ControlTestingRuleResult::pass(
            message: 'Tester independence verified',
            controlArea: $controlArea->value,
        );
    }

    /**
     * Validate complete test coverage for period.
     *
     * @param array<ControlArea> $testedControls Controls that have been tested
     * @param bool $includeItGeneralControls Whether to include IT general controls
     * @return ControlTestingRuleResult Validation result
     */
    public function validateTestCoverage(
        array $testedControls,
        bool $includeItGeneralControls = true,
    ): ControlTestingRuleResult {
        $requiredControls = ControlArea::cases();
        $testedControlValues = array_map(fn (ControlArea $c) => $c->value, $testedControls);

        $missingControls = [];
        $missingKeyControls = [];

        foreach ($requiredControls as $control) {
            if (!$includeItGeneralControls && $control === ControlArea::IT_GENERAL_CONTROLS) {
                continue;
            }

            if (!in_array($control->value, $testedControlValues, true)) {
                $missingControls[] = $control->value;

                if ($control->isKeyControl()) {
                    $missingKeyControls[] = $control->value;
                }
            }
        }

        if (!empty($missingKeyControls)) {
            return ControlTestingRuleResult::fail(
                message: 'Key controls not tested - coverage incomplete',
                reason: 'MISSING_KEY_CONTROLS',
                missingControls: $missingControls,
                missingKeyControls: $missingKeyControls,
                testedControlCount: count($testedControls),
                requiredControlCount: count($requiredControls),
            );
        }

        if (!empty($missingControls)) {
            return ControlTestingRuleResult::warn(
                message: 'Some non-key controls not tested',
                missingControls: $missingControls,
                testedControlCount: count($testedControls),
                requiredControlCount: count($requiredControls),
            );
        }

        return ControlTestingRuleResult::pass(
            message: 'All required controls have been tested',
            testedControlCount: count($testedControls),
            requiredControlCount: count($requiredControls),
        );
    }

    /**
     * Validate that DateInterval->days is a valid integer.
     *
     * @param \DateInterval $interval The interval to validate
     * @param ControlArea $controlArea The control area being validated
     * @param string $context Description of what's being calculated (e.g., "days between test and period end")
     * @return ControlTestingRuleResult|null Returns failure result if invalid, null if valid
     */
    private function validateIntervalDays(
        \DateInterval $interval,
        ControlArea $controlArea,
        string $context,
    ): ?ControlTestingRuleResult {
        if (!is_int($interval->days)) {
            return ControlTestingRuleResult::fail(
                message: "Unable to determine {$context}: invalid interval calculation",
                reason: 'INVALID_INTERVAL',
                controlArea: $controlArea->value,
            );
        }

        return null;
    }
}

/**
 * Result object for control testing rule validation.
 */
final readonly class ControlTestingRuleResult
{
    private function __construct(
        public bool $passed,
        public bool $isWarning,
        public string $message,
        public ?string $reason = null,
        public ?string $controlArea = null,
        public ?int $requiredSampleSize = null,
        public ?int $proposedSampleSize = null,
        public ?int $minRequiredSample = null,
        public ?int $recommendedSampleSize = null,
        public ?int $populationSize = null,
        public ?int $exceptionsFound = null,
        public ?float $exceptionRate = null,
        public ?float $toleranceRate = null,
        public ?int $expandedSampleSize = null,
        public ?string $recommendedAction = null,
        public ?int $daysBetweenTestAndPeriodEnd = null,
        public ?int $maxAllowedDays = null,
        public ?int $daysAfterPeriodEnd = null,
        public ?string $conflictingUserId = null,
        public array $missingControls = [],
        public array $missingKeyControls = [],
        public ?int $testedControlCount = null,
        public ?int $requiredControlCount = null,
    ) {}

    public static function pass(
        string $message,
        ?string $controlArea = null,
        ?int $proposedSampleSize = null,
        ?int $minRequiredSample = null,
        ?int $exceptionsFound = null,
        ?float $exceptionRate = null,
        ?int $daysBetweenTestAndPeriodEnd = null,
        ?int $testedControlCount = null,
        ?int $requiredControlCount = null,
    ): self {
        return new self(
            passed: true,
            isWarning: false,
            message: $message,
            controlArea: $controlArea,
            proposedSampleSize: $proposedSampleSize,
            minRequiredSample: $minRequiredSample,
            exceptionsFound: $exceptionsFound,
            exceptionRate: $exceptionRate,
            daysBetweenTestAndPeriodEnd: $daysBetweenTestAndPeriodEnd,
            testedControlCount: $testedControlCount,
            requiredControlCount: $requiredControlCount,
        );
    }

    public static function warn(
        string $message,
        ?string $controlArea = null,
        ?int $recommendedSampleSize = null,
        ?int $proposedSampleSize = null,
        ?int $exceptionsFound = null,
        ?float $exceptionRate = null,
        ?string $recommendedAction = null,
        ?int $daysAfterPeriodEnd = null,
        array $missingControls = [],
        ?int $testedControlCount = null,
        ?int $requiredControlCount = null,
    ): self {
        return new self(
            passed: true,
            isWarning: true,
            message: $message,
            controlArea: $controlArea,
            recommendedSampleSize: $recommendedSampleSize,
            proposedSampleSize: $proposedSampleSize,
            exceptionsFound: $exceptionsFound,
            exceptionRate: $exceptionRate,
            recommendedAction: $recommendedAction,
            daysAfterPeriodEnd: $daysAfterPeriodEnd,
            missingControls: $missingControls,
            testedControlCount: $testedControlCount,
            requiredControlCount: $requiredControlCount,
        );
    }

    public static function fail(
        string $message,
        string $reason,
        ?string $controlArea = null,
        ?int $requiredSampleSize = null,
        ?int $proposedSampleSize = null,
        ?int $populationSize = null,
        ?int $exceptionsFound = null,
        ?float $exceptionRate = null,
        ?float $toleranceRate = null,
        ?int $expandedSampleSize = null,
        ?string $recommendedAction = null,
        ?int $daysBetweenTestAndPeriodEnd = null,
        ?int $maxAllowedDays = null,
        ?int $daysAfterPeriodEnd = null,
        ?string $conflictingUserId = null,
        array $missingControls = [],
        array $missingKeyControls = [],
        ?int $testedControlCount = null,
        ?int $requiredControlCount = null,
    ): self {
        return new self(
            passed: false,
            isWarning: false,
            message: $message,
            reason: $reason,
            controlArea: $controlArea,
            requiredSampleSize: $requiredSampleSize,
            proposedSampleSize: $proposedSampleSize,
            populationSize: $populationSize,
            exceptionsFound: $exceptionsFound,
            exceptionRate: $exceptionRate,
            toleranceRate: $toleranceRate,
            expandedSampleSize: $expandedSampleSize,
            recommendedAction: $recommendedAction,
            daysBetweenTestAndPeriodEnd: $daysBetweenTestAndPeriodEnd,
            maxAllowedDays: $maxAllowedDays,
            daysAfterPeriodEnd: $daysAfterPeriodEnd,
            conflictingUserId: $conflictingUserId,
            missingControls: $missingControls,
            missingKeyControls: $missingKeyControls,
            testedControlCount: $testedControlCount,
            requiredControlCount: $requiredControlCount,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'passed' => $this->passed,
            'is_warning' => $this->isWarning,
            'message' => $this->message,
            'reason' => $this->reason,
            'control_area' => $this->controlArea,
            'required_sample_size' => $this->requiredSampleSize,
            'proposed_sample_size' => $this->proposedSampleSize,
            'min_required_sample' => $this->minRequiredSample,
            'recommended_sample_size' => $this->recommendedSampleSize,
            'population_size' => $this->populationSize,
            'exceptions_found' => $this->exceptionsFound,
            'exception_rate' => $this->exceptionRate,
            'tolerance_rate' => $this->toleranceRate,
            'expanded_sample_size' => $this->expandedSampleSize,
            'recommended_action' => $this->recommendedAction,
            'days_between_test_and_period_end' => $this->daysBetweenTestAndPeriodEnd,
            'max_allowed_days' => $this->maxAllowedDays,
            'days_after_period_end' => $this->daysAfterPeriodEnd,
            'conflicting_user_id' => $this->conflictingUserId,
            'missing_controls' => $this->missingControls,
            'missing_key_controls' => $this->missingKeyControls,
            'tested_control_count' => $this->testedControlCount,
            'required_control_count' => $this->requiredControlCount,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
