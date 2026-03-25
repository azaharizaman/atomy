<?php

declare(strict_types=1);

namespace App\Services\ApprovalOperations;

use Illuminate\Support\Str;
use Nexus\Common\Contracts\UlidInterface;
use Symfony\Component\Uid\Ulid;

final readonly class LaravelUlidGenerator implements UlidInterface
{
    public function generate(): string
    {
        return (string) Str::ulid();
    }

    public function isValid(string $ulid): bool
    {
        return Ulid::isValid($ulid);
    }

    public function getTimestamp(string $ulid): \DateTimeImmutable
    {
        return Ulid::fromString($ulid)->getDateTime();
    }
}
