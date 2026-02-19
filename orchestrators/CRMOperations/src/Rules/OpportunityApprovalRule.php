<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Rules;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Enums\OpportunityStage;

/**
 * Opportunity Approval Rule
 * 
 * Determines if an opportunity requires approval before closing.
 * Business rule for deal approval requirements.
 * 
 * @package Nexus\CRMOperations\Rules
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class OpportunityApprovalRule
{
    /**
     * Default approval thresholds
     */
    private const DEFAULT_THRESHOLDS = [
        'requires_approval_value' => 500000, // $5,000.00 in cents
        'requires_approval_discount' => 20,  // 20% discount
        'manager_approval_value' => 1000000, // $10,000.00 in cents
        'director_approval_value' => 5000000, // $50,000.00 in cents
    ];

    /**
     * @param array<string, mixed> $thresholds Custom thresholds
     */
    public function __construct(
        private array $thresholds = self::DEFAULT_THRESHOLDS
    ) {}

    /**
     * Evaluate if opportunity requires approval
     * 
     * @param OpportunityInterface $opportunity Opportunity to evaluate
     * @param array<string, mixed> $context Additional context
     * @return ApprovalResult Evaluation result
     */
    public function evaluate(OpportunityInterface $opportunity, array $context = []): ApprovalResult
    {
        $value = $opportunity->getValue();
        $reasons = [];
        $approvalLevel = null;

        // Check if opportunity is ready for closing
        if (!$opportunity->isOpen()) {
            return new ApprovalResult(
                requiresApproval: false,
                approvalLevel: null,
                reasons: ['Opportunity is not open'],
                canClose: false
            );
        }

        // Check value-based approval requirements
        $directorValue = $this->thresholds['director_approval_value'] ?? 5000000;
        $managerValue = $this->thresholds['manager_approval_value'] ?? 1000000;
        $approvalValue = $this->thresholds['requires_approval_value'] ?? 500000;

        if ($value >= $directorValue) {
            $approvalLevel = 'director';
            $reasons[] = sprintf(
                'Deal value (%s) requires director approval',
                $this->formatValue($value, $opportunity->getCurrency())
            );
        } elseif ($value >= $managerValue) {
            $approvalLevel = 'manager';
            $reasons[] = sprintf(
                'Deal value (%s) requires manager approval',
                $this->formatValue($value, $opportunity->getCurrency())
            );
        } elseif ($value >= $approvalValue) {
            $approvalLevel = 'team_lead';
            $reasons[] = sprintf(
                'Deal value (%s) requires team lead approval',
                $this->formatValue($value, $opportunity->getCurrency())
            );
        }

        // Check discount-based approval
        $discountPercent = $context['discount_percent'] ?? 0;
        $discountThreshold = $this->thresholds['requires_approval_discount'] ?? 20;

        if ($discountPercent >= $discountThreshold) {
            $approvalLevel = $approvalLevel ?? 'team_lead';
            $reasons[] = sprintf(
                'Discount (%d%%) exceeds threshold (%d%%)',
                $discountPercent,
                $discountThreshold
            );
        }

        // Check for special conditions
        if ($context['has_special_terms'] ?? false) {
            $approvalLevel = $approvalLevel ?? 'team_lead';
            $reasons[] = 'Deal has special terms requiring approval';
        }

        if ($context['has_custom_pricing'] ?? false) {
            $approvalLevel = $approvalLevel ?? 'team_lead';
            $reasons[] = 'Deal has custom pricing requiring approval';
        }

        return new ApprovalResult(
            requiresApproval: $approvalLevel !== null,
            approvalLevel: $approvalLevel,
            reasons: $reasons,
            canClose: true
        );
    }

    /**
     * Check if opportunity can be closed without approval
     * 
     * @param OpportunityInterface $opportunity Opportunity to check
     * @param array<string, mixed> $context Additional context
     */
    public function canCloseWithoutApproval(
        OpportunityInterface $opportunity,
        array $context = []
    ): bool {
        $result = $this->evaluate($opportunity, $context);
        return $result->canClose && !$result->requiresApproval;
    }

    /**
     * Get required approval level
     * 
     * @param OpportunityInterface $opportunity Opportunity to check
     * @param array<string, mixed> $context Additional context
     * @return string|null Required approval level or null if not required
     */
    public function getRequiredApprovalLevel(
        OpportunityInterface $opportunity,
        array $context = []
    ): ?string {
        return $this->evaluate($opportunity, $context)->approvalLevel;
    }

    /**
     * Get approval thresholds
     * 
     * @return array<string, mixed>
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Format value for display
     */
    private function formatValue(int $value, string $currency): string
    {
        return sprintf('%s %s', number_format($value / 100, 2), $currency);
    }
}

/**
 * Approval Result DTO
 */
final readonly class ApprovalResult
{
    /**
     * @param bool $requiresApproval Whether approval is required
     * @param string|null $approvalLevel Required approval level
     * @param array<string> $reasons Reasons for the result
     * @param bool $canClose Whether opportunity can be closed
     */
    public function __construct(
        public bool $requiresApproval,
        public ?string $approvalLevel,
        public array $reasons,
        public bool $canClose
    ) {}
}