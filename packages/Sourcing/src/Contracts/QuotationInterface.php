<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Contracts;

use Nexus\Sourcing\ValueObjects\NormalizationLine;

interface QuotationInterface
{
    public function getId(): string;

    public function getVendorId(): string;

    public function getStatus(): string;

    /**
     * @return array<NormalizationLine>
     */
    public function getNormalizationLines(): array;
}
