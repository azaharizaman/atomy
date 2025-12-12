<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Audit finding severity levels per SOX 404 framework.
 *
 * Classifications determine required management response and remediation timelines.
 */
enum AuditFindingSeverity: string
{
    case DEFICIENCY = 'deficiency';
    case SIGNIFICANT_DEFICIENCY = 'significant_deficiency';
    case MATERIAL_WEAKNESS = 'material_weakness';
    case OBSERVATION = 'observation';
    case BEST_PRACTICE = 'best_practice';

    /**
     * Get severity level (1-5, 5 being most severe).
     */
    public function getSeverityLevel(): int
    {
        return match ($this) {
            self::BEST_PRACTICE => 1,
            self::OBSERVATION => 2,
            self::DEFICIENCY => 3,
            self::SIGNIFICANT_DEFICIENCY => 4,
            self::MATERIAL_WEAKNESS => 5,
        };
    }

    /**
     * Get remediation deadline in days.
     */
    public function getRemediationDeadlineDays(): int
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS => 30,
            self::SIGNIFICANT_DEFICIENCY => 60,
            self::DEFICIENCY => 90,
            self::OBSERVATION => 180,
            self::BEST_PRACTICE => 365,
        };
    }

    /**
     * Check if finding requires board notification.
     */
    public function requiresBoardNotification(): bool
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS,
            self::SIGNIFICANT_DEFICIENCY => true,
            default => false,
        };
    }

    /**
     * Check if finding requires disclosure.
     */
    public function requiresDisclosure(): bool
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS => true,
            default => false,
        };
    }

    /**
     * Get required approval level for remediation.
     */
    public function getRequiredApprovalLevel(): string
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS => 'AUDIT_COMMITTEE',
            self::SIGNIFICANT_DEFICIENCY => 'CFO',
            self::DEFICIENCY => 'CONTROLLER',
            self::OBSERVATION => 'DEPARTMENT_MANAGER',
            self::BEST_PRACTICE => 'PROCESS_OWNER',
        };
    }

    /**
     * Get description per PCAOB standards.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS => 'A deficiency, or combination of deficiencies, such that there is a reasonable possibility that a material misstatement will not be prevented or detected on a timely basis.',
            self::SIGNIFICANT_DEFICIENCY => 'A deficiency, or combination of deficiencies, that is less severe than a material weakness, yet important enough to merit attention by those responsible for oversight.',
            self::DEFICIENCY => 'A control deficiency exists when the design or operation of a control does not allow management or employees to prevent or detect misstatements on a timely basis.',
            self::OBSERVATION => 'An area where controls could be improved but does not represent a deficiency in design or operation.',
            self::BEST_PRACTICE => 'A recommendation for process improvement that exceeds current compliance requirements.',
        };
    }

    /**
     * Check if severity is reportable to external auditors.
     */
    public function isExternallyReportable(): bool
    {
        return match ($this) {
            self::MATERIAL_WEAKNESS,
            self::SIGNIFICANT_DEFICIENCY,
            self::DEFICIENCY => true,
            default => false,
        };
    }
}
