<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules;

interface RuleRegistryInterface
{
    public function register(RuleInterface $rule): void;

    public function get(string $name): ?RuleInterface;

    public function has(string $name): bool;

    public function all(): array;
}
