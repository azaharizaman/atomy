<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Budget Type enum
 * 
 * Classifies budgets by their purpose and accounting treatment.
 */
enum BudgetType: string
{
    case Operational = 'operational';
    case Capital = 'capital';
    case Project = 'project';
    case Revenue = 'revenue';

    /**
     * Check if this is an expense budget type
     */
    public function isExpense(): bool
    {
        return match($this) {
            self::Operational, self::Capital, self::Project => true,
            self::Revenue => false,
        };
    }

    /**
     * Check if this is a revenue budget type
     */
    public function isRevenue(): bool
    {
        return $this === self::Revenue;
    }

    /**
     * Get typical approval level required
     */
    public function getTypicalApprovalLevel(): ApprovalLevel
    {
        return match($this) {
            self::Operational => ApprovalLevel::Manager,
            self::Project => ApprovalLevel::Director,
            self::Capital, self::Revenue => ApprovalLevel::CFO,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Operational => 'Operational Budget',
            self::Capital => 'Capital Budget',
            self::Project => 'Project Budget',
            self::Revenue => 'Revenue Budget',
        };
    }
}
