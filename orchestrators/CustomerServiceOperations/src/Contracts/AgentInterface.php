<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface AgentInterface
{
    public function getId(): string;
    public function getName(): string;

    /**
     * @return string[]
     */
    public function getSkills(): array;

    public function getCurrentLoad(): int;
    public function isAvailable(): bool;
}
