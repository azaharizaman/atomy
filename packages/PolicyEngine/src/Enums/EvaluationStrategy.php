<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Enums;

enum EvaluationStrategy: string
{
    case FirstMatch = 'first_match';
    case CollectAll = 'collect_all';
}
