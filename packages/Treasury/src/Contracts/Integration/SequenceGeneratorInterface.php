<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface SequenceGeneratorInterface
{
    public function generate(string $type, string $tenantId): string;

    public function getNextNumber(string $type, string $tenantId): int;

    public function formatNumber(string $type, int $number): string;
}
