<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface ForecastPortInterface
{
    /**
     * @param array<string, mixed> $context
     * @return array{status:string,data:array<string,mixed>|null,confidence:float|null,model_version:string|null,error:string|null}
     */
    public function forecast(string $modelId, array $context, int $maxAttempts, int $pollIntervalMs): array;
}
