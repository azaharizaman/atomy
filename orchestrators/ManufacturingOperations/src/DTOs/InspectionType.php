<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

enum InspectionType: string
{
    case FirstArticle = 'FirstArticle';
    case InProcess = 'InProcess';
    case Final = 'Final';
}
