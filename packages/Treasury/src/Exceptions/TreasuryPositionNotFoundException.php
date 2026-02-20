<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

use DateTimeImmutable;

final class TreasuryPositionNotFoundException extends TreasuryException
{
    public static function forId(string $id): self
    {
        return new self("Treasury position not found with ID: {$id}");
    }

    public static function forDate(DateTimeImmutable $date, string $tenantId): self
    {
        return new self(
            "Treasury position not found for date {$date->format('Y-m-d')} and tenant: {$tenantId}"
        );
    }

    public static function forEntity(string $entityId, string $tenantId): self
    {
        return new self("Treasury position not found for entity {$entityId} in tenant: {$tenantId}");
    }
}
