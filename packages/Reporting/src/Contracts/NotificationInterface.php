<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\Category;
use Nexus\Reporting\ValueObjects\Priority;

interface NotificationInterface
{
    /** @return array<string, mixed> */
    public function toEmail(): array;

    public function toSms(): string;

    /** @return array<string, mixed> */
    public function toPush(): array;

    /** @return array<string, mixed> */
    public function toInApp(): array;

    public function getPriority(): Priority;

    public function getCategory(): Category;
}
