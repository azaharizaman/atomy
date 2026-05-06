<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\ValueObjects\FormulaCatalog;
use Nexus\MetricEngine\ValueObjects\FormulaGraph;

interface FormulaGraphServiceInterface
{
    public function build(FormulaCatalog $catalog): FormulaGraph;
}
