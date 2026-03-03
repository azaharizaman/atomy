<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface SettingsGatewayInterface
{
    public function getFloat(string $key, float $default = 0.0): float;
}
