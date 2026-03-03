<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum ExportFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case HTML = 'html';
}
