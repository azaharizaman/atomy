<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\ValueObjects;

/**
 * Immutable value object representing a query definition
 */
final readonly class QueryDefinition implements \Nexus\QueryEngine\Contracts\QueryDefinitionInterface
{
    /**
     * @param string $id
     * @param string $name
     * @param string $type
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $guards
     * @param array<string, mixed> $dataSources
     * @param bool $requiresTransaction
     * @param int $timeout
     * @param bool $supportsParallelExecution
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $type,
        private array $parameters = [],
        private array $guards = [],
        private array $dataSources = [],
        private bool $requiresTransaction = true,
        private int $timeout = 300,
        private bool $supportsParallelExecution = false
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getGuards(): array
    {
        return $this->guards;
    }

    public function getDataSources(): array
    {
        return $this->dataSources;
    }

    public function requiresTransaction(): bool
    {
        return $this->requiresTransaction;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function supportsParallelExecution(): bool
    {
        return $this->supportsParallelExecution;
    }

    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? throw new \InvalidArgumentException('Query ID is required'),
            name: $data['name'] ?? throw new \InvalidArgumentException('Query name is required'),
            type: $data['type'] ?? 'generic',
            parameters: $data['parameters'] ?? [],
            guards: $data['guards'] ?? [],
            dataSources: $data['data_sources'] ?? [],
            requiresTransaction: $data['requires_transaction'] ?? true,
            timeout: $data['timeout'] ?? 300,
            supportsParallelExecution: $data['supports_parallel_execution'] ?? false
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'parameters' => $this->parameters,
            'guards' => $this->guards,
            'data_sources' => $this->dataSources,
            'requires_transaction' => $this->requiresTransaction,
            'timeout' => $this->timeout,
            'supports_parallel_execution' => $this->supportsParallelExecution,
        ];
    }
}
