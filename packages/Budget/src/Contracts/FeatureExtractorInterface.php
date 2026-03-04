<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface FeatureExtractorInterface
{
    public function extract(object $entity): FeatureSetInterface;
}
