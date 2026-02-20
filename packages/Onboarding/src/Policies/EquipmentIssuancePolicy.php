<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Policies;

final readonly class EquipmentIssuancePolicy
{
    public function getStandardEquipment(): array
    {
        return ['laptop', 'mouse', 'keyboard'];
    }
}
