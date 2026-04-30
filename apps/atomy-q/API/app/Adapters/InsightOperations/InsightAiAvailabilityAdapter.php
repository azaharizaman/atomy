<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Throwable;

final class InsightAiAvailabilityAdapter implements AiAvailabilityPortInterface
{
    /** @var array<string, list<string>> */
    private array $reasonCodeCache = [];

    public function __construct(
        private readonly AiRuntimeStatusInterface $runtimeStatus,
    ) {}

    /**
     * @return bool
     */
    public function isFeatureAvailable(string $featureKey): bool
    {
        try {
            $status =
                $this->runtimeStatus->snapshot()->capabilityStatuses[
                    $featureKey
                ] ?? null;
        } catch (Throwable $exception) {
            report($exception);
            $this->reasonCodeCache[$featureKey] = ["provider_unavailable"];

            return false;
        }

        $this->reasonCodeCache[$featureKey] = $status?->reasonCodes ?? [
            "ai_capability_not_configured",
        ];

        return $status?->available === true;
    }

    /**
     * @return list<string>
     */
    public function reasonCodes(string $featureKey): array
    {
        if (!array_key_exists($featureKey, $this->reasonCodeCache)) {
            $this->isFeatureAvailable($featureKey);
        }

        return $this->reasonCodeCache[$featureKey] ?? [
            "ai_capability_not_configured",
        ];
    }
}
