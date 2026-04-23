<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

final readonly class AiEndpointHealthSnapshot
{
    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        public AiEndpointGroup $endpointGroup,
        public AiHealth $health,
        public DateTimeImmutable $checkedAt,
        public array $reasonCodes = [],
        public ?int $latencyMs = null,
        public array $diagnostics = [],
    ) {
        $this->assertReasonCodes($this->reasonCodes);
        $this->assertDiagnostics($this->diagnostics);

        if ($this->latencyMs !== null && $this->latencyMs < 0) {
            throw AiRuntimeContractException::invalidValue('AI endpoint latency');
        }
    }

    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup->value,
            'health' => $this->health->value,
            'checked_at' => $this->checkedAt->format(DATE_ATOM),
            'reason_codes' => $this->reasonCodes,
            'latency_ms' => $this->latencyMs,
            'diagnostics' => $this->diagnostics,
        ];
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function assertReasonCodes(array $reasonCodes): void
    {
        foreach ($reasonCodes as $reasonCode) {
            if (!is_string($reasonCode) || trim($reasonCode) === '') {
                throw AiRuntimeContractException::invalidValue('AI endpoint reason codes');
            }
        }
    }

    /**
     * @param array<string, scalar|null> $diagnostics
     */
    private function assertDiagnostics(array $diagnostics): void
    {
        foreach ($diagnostics as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw AiRuntimeContractException::invalidValue('AI endpoint diagnostics');
            }

            if (!is_scalar($value) && $value !== null) {
                throw AiRuntimeContractException::invalidValue('AI endpoint diagnostics');
            }
        }
    }
}
