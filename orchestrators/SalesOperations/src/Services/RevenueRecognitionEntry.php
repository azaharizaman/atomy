<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class RevenueRecognitionEntry
{
    public function __construct(
        public string $contractId,
        public float $recognizedAmount,
        public \DateTimeImmutable $recognizedAt,
        public array $remainingSchedule,
    ) {}
}
