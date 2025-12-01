<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Enums;

/**
 * Types of control over an entity.
 */
enum ControlType: string
{
    case SUBSIDIARY = 'subsidiary';
    case ASSOCIATE = 'associate';
    case JOINT_VENTURE = 'joint_venture';
    case INVESTMENT = 'investment';
}
