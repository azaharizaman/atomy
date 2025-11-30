<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

/**
 * Model deployment strategy
 */
enum DeploymentStrategy: string
{
    case BLUE_GREEN = 'blue_green';
    case CANARY = 'canary';
    case IMMEDIATE = 'immediate';
    case ROLLING = 'rolling';
}
