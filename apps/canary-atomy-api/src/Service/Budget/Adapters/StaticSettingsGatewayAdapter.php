<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\SettingsGatewayInterface;

final class StaticSettingsGatewayAdapter implements SettingsGatewayInterface
{
    public function getFloat(string $key, float $default = 0.0): float
    {
        return $default;
    }
}
