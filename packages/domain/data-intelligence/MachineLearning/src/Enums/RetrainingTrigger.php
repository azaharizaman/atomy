<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

/**
 * Retraining trigger type
 */
enum RetrainingTrigger: string
{
    case DRIFT = 'drift';
    case MANUAL = 'manual';
    case SCHEDULED = 'scheduled';
    case ACCURACY_DROP = 'accuracy_drop';
    case ADVERSARIAL_FAILURE = 'adversarial_failure';
}
