<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates category-specific approval requirements.
 *
 * Certain purchase categories may require:
 * - Specific approver roles
 * - Additional approval levels
 * - Department head override
 */
final readonly class CategoryApprovalRule implements RuleInterface
{
    /**
     * Categories requiring special approval handling.
     *
     * @var array<string, array{
     *     required_roles: array<string>,
     *     min_level: int,
     *     description: string
     * }>
     */
    private const SPECIAL_CATEGORIES = [
        'CAPITAL' => [
            'required_roles' => ['finance_manager', 'cfo'],
            'min_level' => 3,
            'description' => 'Capital expenditure requires Finance Director approval',
        ],
        'IT_HARDWARE' => [
            'required_roles' => ['it_manager', 'cio'],
            'min_level' => 2,
            'description' => 'IT hardware requires IT Manager approval',
        ],
        'PROFESSIONAL_SERVICES' => [
            'required_roles' => ['department_head', 'legal'],
            'min_level' => 2,
            'description' => 'Professional services require Department Head approval',
        ],
        'TRAVEL' => [
            'required_roles' => ['manager'],
            'min_level' => 1,
            'description' => 'Travel requires direct manager approval',
        ],
    ];

    /**
     * Check category approval requirements.
     *
     * @param ApprovalChainContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof ApprovalChainContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected ApprovalChainContext'
            );
        }

        // Get category from metadata if available
        $category = $context->approvalSettings['category'] ?? null;

        // If no special category, rule passes
        if ($category === null || !isset(self::SPECIAL_CATEGORIES[$category])) {
            return RuleResult::pass($this->getName(), null, [
                'category' => $category,
                'special_handling' => false,
            ]);
        }

        $categoryConfig = self::SPECIAL_CATEGORIES[$category];
        $violations = [];

        // Check minimum approval level
        if ($context->requiredLevel->value < $categoryConfig['min_level']) {
            $violations[] = sprintf(
                'Category %s requires minimum approval level %d, but only level %d is configured',
                $category,
                $categoryConfig['min_level'],
                $context->requiredLevel->value
            );
        }

        // Check required roles in approval chain
        $approverRoles = [];
        foreach ($context->resolvedApprovers as $approver) {
            if (isset($approver['roles'])) {
                $approverRoles = array_merge($approverRoles, (array) $approver['roles']);
            }
        }

        $missingRoles = array_diff($categoryConfig['required_roles'], $approverRoles);
        if (!empty($missingRoles) && !empty($approverRoles)) {
            // Only flag if we have role data but are missing required roles
            $violations[] = sprintf(
                'Category %s requires approver with role(s): %s',
                $category,
                implode(', ', $categoryConfig['required_roles'])
            );
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                $categoryConfig['description'] . ': ' . implode('; ', $violations),
                [
                    'category' => $category,
                    'required_roles' => $categoryConfig['required_roles'],
                    'min_level' => $categoryConfig['min_level'],
                ]
            );
        }

        return RuleResult::pass($this->getName(), null, [
            'category' => $category,
            'special_handling' => true,
            'required_roles' => $categoryConfig['required_roles'],
        ]);
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'category_approval';
    }
}
