<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * Feature data type
 */
enum FeatureDataType: string
{
    case NUMERICAL = 'numerical';
    case CATEGORICAL = 'categorical';
    case BOOLEAN = 'boolean';
    case TEMPORAL = 'temporal';
    case TEXT = 'text';
}
