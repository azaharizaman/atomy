<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Enums;

enum PolicyKind: string
{
    case Authorization = 'authorization';
    case Workflow = 'workflow';
    case Threshold = 'threshold'; // Supported at runtime.
}
