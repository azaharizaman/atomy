<?php

declare(strict_types=1);

namespace Nexus\PDPA\ValueObjects;

use DateTimeImmutable;
use Nexus\PDPA\Enums\DataSubjectRequestType;
use Nexus\PDPA\Enums\RequestStatus;

final readonly class DataSubjectRequest
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public DataSubjectRequestType $type,
        public RequestStatus $status,
        public DateTimeImmutable $submittedAt,
        public array $metadata = []
    ) {}
}
