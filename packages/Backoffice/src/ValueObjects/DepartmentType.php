<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum DepartmentType: string
{
    case FUNCTIONAL = 'functional';
    case DIVISIONAL = 'divisional';
    case MATRIX = 'matrix';
    case PROJECT_BASED = 'project_based';
}
