<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot;

interface AiHealthProbeInterface
{
    public function probe(AiEndpointConfig $endpointConfig): AiEndpointHealthSnapshot;
}
