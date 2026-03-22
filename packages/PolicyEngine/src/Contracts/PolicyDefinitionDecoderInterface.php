<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Contracts;

use Nexus\PolicyEngine\Domain\PolicyDefinition;

interface PolicyDefinitionDecoderInterface
{
    public function decode(string $payload): PolicyDefinition;
}
