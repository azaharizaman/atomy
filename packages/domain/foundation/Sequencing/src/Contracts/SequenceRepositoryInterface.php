<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Repository interface for sequence persistence operations.
 */
interface SequenceRepositoryInterface
{
    /**
     * Find a sequence by name and scope.
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function findByNameAndScope(string $name, ?string $scopeIdentifier = null): SequenceInterface;

    /**
     * Find a sequence by name and scope, or return null if not found.
     */
    public function findByNameAndScopeOrNull(string $name, ?string $scopeIdentifier = null): ?SequenceInterface;

    /**
     * Create a new sequence definition.
     *
     * @param array<string, mixed> $data
     * @throws \Nexus\Sequencing\Exceptions\InvalidPatternException
     */
    public function create(array $data): SequenceInterface;

    /**
     * Update an existing sequence definition.
     *
     * @param array<string, mixed> $data
     */
    public function update(SequenceInterface $sequence, array $data): SequenceInterface;

    /**
     * Delete a sequence definition.
     */
    public function delete(SequenceInterface $sequence): void;

    /**
     * Lock a sequence to prevent generation.
     */
    public function lock(SequenceInterface $sequence): void;

    /**
     * Unlock a sequence to allow generation.
     */
    public function unlock(SequenceInterface $sequence): void;

    /**
     * Get all sequences for a given scope.
     *
     * @return SequenceInterface[]
     */
    public function getAllForScope(?string $scopeIdentifier = null): array;
}
