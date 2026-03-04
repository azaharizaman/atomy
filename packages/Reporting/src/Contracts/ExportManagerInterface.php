<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\ExportDefinition;
use Nexus\Reporting\ValueObjects\ExportDestination;
use Nexus\Reporting\ValueObjects\ExportFormat;

interface ExportManagerInterface
{
    public function export(ExportDefinition $definition, ExportFormat $format, ExportDestination $destination): ExportResultInterface;
}
