<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Enums;

enum ConditionOperator: string
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case In = 'in';
    case NotIn = 'not_in';
    case Exists = 'exists';
    case Contains = 'contains';
    case GreaterThan = 'gt';
    case GreaterThanOrEquals = 'gte';
    case LessThan = 'lt';
    case LessThanOrEquals = 'lte';
    case Between = 'between';
}
