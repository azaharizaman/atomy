<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

interface FeatureExtractorInterface
{
    public function extract(object $entity): FeatureSetInterface;
    /** @return array<int,string> */
    public function getFeatureKeys(): array;
    public function getSchemaVersion(): string;
}
