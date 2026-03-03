<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface QueryResultInterface
{
    public function getQueryId(): string;

    /** @return array<int, array<string, mixed>> */
    public function getData(): array;

    /** @return array<string, mixed> */
    public function getMetadata(): array;
}
