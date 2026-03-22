<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Domain;

use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;

final readonly class BeginDecision
{
    public function __construct(
        public BeginOutcome $outcome,
        public ?ResultEnvelope $replayResult,
        public ?IdempotencyRecord $record,
    ) {
    }
}
