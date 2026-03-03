<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PredictionServiceInterface
{
    public function predict(object $subject, string $modelKey): PredictionResultInterface;
}
