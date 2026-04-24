<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

use DateTimeImmutable;
use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiEndpointHealthSnapshot
{
    /**
     * @var list<string>
     */
    public array $reasonCodes;

    /**
     * @var array<string, scalar|null>
     */
    public array $diagnostics;

    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        public string $endpointGroup,
        public string $health,
        public DateTimeImmutable $checkedAt,
        array $reasonCodes = [],
        public ?int $latencyMs = null,
        array $diagnostics = [],
    ) {
        AiStatusSchema::assertEndpointGroup($this->endpointGroup);
        AiStatusSchema::assertHealth($this->health);
        $this->assertReasonCodes($reasonCodes);
        $this->assertDiagnostics($diagnostics);
        $this->reasonCodes = AiStatusSchema::sanitizeReasonCodes($reasonCodes);
        $this->diagnostics = AiStatusSchema::sanitizeDiagnostics($diagnostics);

        if ($this->latencyMs !== null && $this->latencyMs < 0) {
            throw AiStatusContractException::invalidValue('AI endpoint latency');
        }
    }

    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup,
            'health' => $this->health,
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
                throw AiStatusContractException::invalidValue('AI endpoint reason codes');
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
                throw AiStatusContractException::invalidValue('AI endpoint diagnostics');
            }

            if (!is_scalar($value) && $value !== null) {
                throw AiStatusContractException::invalidValue('AI endpoint diagnostics');
            }
        }
    }
}
