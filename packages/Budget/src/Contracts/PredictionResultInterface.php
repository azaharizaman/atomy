<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PredictionResultInterface
{
    public function getProbability(): float;

    public function getConfidence(): float;
}
