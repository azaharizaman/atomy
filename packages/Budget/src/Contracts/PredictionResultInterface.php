<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PredictionResultInterface
{
    /**
     * Get predicted probability of budget overrun.
     *
     * Expected range is inclusive `0.0` to `1.0`.
     */
    public function getProbability(): float;

    /**
     * Get model confidence score for the prediction.
     *
     * Expected range is inclusive `0.0` to `1.0`.
     */
    public function getConfidence(): float;
}
