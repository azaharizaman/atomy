<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Types of SOX controls in the procurement process.
 *
 * These map to standard SOX 404 control categories for
 * financial reporting and compliance.
 */
enum SOXControlType: string
{
    /**
     * Preventive controls stop errors/fraud before they occur.
     */
    case PREVENTIVE = 'preventive';

    /**
     * Detective controls identify errors/fraud after they occur.
     */
    case DETECTIVE = 'detective';

    /**
     * Corrective controls fix identified issues.
     */
    case CORRECTIVE = 'corrective';

    /**
     * IT General Controls (ITGC) for system access and changes.
     */
    case ITGC = 'itgc';

    /**
     * Application controls embedded in the procurement system.
     */
    case APPLICATION = 'application';

    /**
     * Manual controls requiring human intervention.
     */
    case MANUAL = 'manual';

    /**
     * Automated controls executed by the system.
     */
    case AUTOMATED = 'automated';

    /**
     * Hybrid controls combining manual and automated elements.
     */
    case HYBRID = 'hybrid';

    /**
     * Get all control types that are fully automated.
     *
     * @return array<self>
     */
    public static function automatedTypes(): array
    {
        return [
            self::AUTOMATED,
            self::APPLICATION,
            self::ITGC,
        ];
    }

    /**
     * Check if this control type requires human attestation.
     */
    public function requiresAttestation(): bool
    {
        return match ($this) {
            self::MANUAL, self::HYBRID => true,
            default => false,
        };
    }

    /**
     * Get the recommended testing frequency in days.
     */
    public function recommendedTestingFrequencyDays(): int
    {
        return match ($this) {
            self::PREVENTIVE, self::AUTOMATED, self::APPLICATION => 90,
            self::DETECTIVE, self::CORRECTIVE => 30,
            self::ITGC => 365,
            self::MANUAL, self::HYBRID => 60,
        };
    }

    /**
     * Get a human-readable description of the control type.
     */
    public function description(): string
    {
        return match ($this) {
            self::PREVENTIVE => 'Prevents errors or fraud before occurrence',
            self::DETECTIVE => 'Detects errors or fraud after occurrence',
            self::CORRECTIVE => 'Corrects identified issues',
            self::ITGC => 'IT General Control for system access/changes',
            self::APPLICATION => 'Application-embedded control',
            self::MANUAL => 'Requires human intervention',
            self::AUTOMATED => 'Fully automated system control',
            self::HYBRID => 'Combination of manual and automated',
        };
    }
}
