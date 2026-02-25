<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

use DateTimeImmutable;
use Nexus\CustomerServiceOperations\Enums\SlaStatus;

interface SlaInterface
{
    public function getTicketId(): string;
    public function getResponseDeadline(): DateTimeImmutable;
    public function getResolutionDeadline(): DateTimeImmutable;
    public function getStatus(): SlaStatus;
}
