<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\AiRuntimeSnapshot;

interface AiRuntimeStatusProviderInterface
{
    public function getRuntimeSnapshot(): AiRuntimeSnapshot;
}
