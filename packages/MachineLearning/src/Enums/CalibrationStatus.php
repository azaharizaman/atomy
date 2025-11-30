<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

/**
 * Calibration status
 */
enum CalibrationStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
}
