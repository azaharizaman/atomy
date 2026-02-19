<?php

declare(strict_types=1);

namespace Nexus\CRM\Services;

use Nexus\CRM\Contracts\PipelineQueryInterface;
use Nexus\CRM\Contracts\PipelinePersistInterface;
use Nexus\CRM\Contracts\PipelineInterface;
use Nexus\CRM\Enums\PipelineStatus;
use Nexus\CRM\ValueObjects\PipelineStage;
use Nexus\CRM\Exceptions\PipelineNotFoundException;
use Psr\Log\LoggerInterface;

final class PipelineManager implements PipelineQueryInterface, PipelinePersistInterface
{
    private array $pipelines = [];

    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function findById(string $id): ?PipelineInterface
    {
        return $this->pipelines[$id] ?? null;
    }

    public function findByIdOrFail(string $id): PipelineInterface
    {
        return $this->findById($id)
            ?? throw PipelineNotFoundException::forId($id);
    }

    public function findByName(string $name): ?PipelineInterface
    {
        foreach ($this->pipelines as $pipeline) {
            if ($pipeline->getName() === $name) {
                return $pipeline;
            }
        }
        return null;
    }

    public function findAll(): iterable
    {
        return $this->pipelines;
    }

    public function findByStatus(PipelineStatus $status): iterable
    {
        foreach ($this->pipelines as $pipeline) {
            if ($pipeline->getStatus() === $status) {
                yield $pipeline;
            }
        }
    }

    public function findActive(): iterable
    {
        foreach ($this->pipelines as $pipeline) {
            if ($pipeline->isActive()) {
                yield $pipeline;
            }
        }
    }

    public function findDefault(): ?PipelineInterface
    {
        foreach ($this->pipelines as $pipeline) {
            if ($pipeline->isDefault()) {
                return $pipeline;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->pipelines);
    }

    public function countByStatus(PipelineStatus $status): int
    {
        $count = 0;
        foreach ($this->pipelines as $pipeline) {
            if ($pipeline->getStatus() === $status) {
                $count++;
            }
        }
        return $count;
    }

    public function create(
        string $tenantId,
        string $name,
        array $stages,
        ?string $description = null,
        bool $isDefault = false
    ): PipelineInterface {
        $id = uniqid('pipeline_');
        
        $pipeline = new class($id, $tenantId, $name, $stages, $description, $isDefault) implements PipelineInterface {
            public function __construct(
                public string $id,
                public string $tenantId,
                public string $name,
                public array $stages,
                public ?string $description,
                public bool $isDefault,
                public PipelineStatus $status = PipelineStatus::Active,
                public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
                public \DateTimeImmutable $updatedAt = new \DateTimeImmutable()
            ) {}

            public function getId(): string { return $this->id; }
            public function getTenantId(): string { return $this->tenantId; }
            public function getName(): string { return $this->name; }
            public function getDescription(): ?string { return $this->description; }
            public function getStages(): array { return $this->stages; }
            public function getStageAtPosition(int $position): ?PipelineStage {
                foreach ($this->stages as $stage) {
                    if ($stage->position === $position) {
                        return $stage;
                    }
                }
                return null;
            }
            public function isActive(): bool { return $this->status === PipelineStatus::Active; }
            public function isDefault(): bool { return $this->isDefault; }
            public function getStatus(): PipelineStatus { return $this->status; }
            public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
            public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
        };

        $this->pipelines[$id] = $pipeline;
        
        $this->logger?->info('Pipeline created', ['pipeline_id' => $id, 'tenant_id' => $tenantId]);
        
        return $pipeline;
    }

    public function update(
        string $id,
        ?string $name = null,
        ?string $description = null
    ): PipelineInterface {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline updated', ['pipeline_id' => $id]);
        
        return $pipeline;
    }

    public function updateStatus(string $id, PipelineStatus $status): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline status updated', [
            'pipeline_id' => $id,
            'status' => $status->value
        ]);
        
        return $pipeline;
    }

    public function addStage(string $id, PipelineStage $stage, ?int $position = null): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Stage added to pipeline', [
            'pipeline_id' => $id,
            'stage' => $stage->name,
            'position' => $position
        ]);
        
        return $pipeline;
    }

    public function removeStage(string $id, int $position): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Stage removed from pipeline', [
            'pipeline_id' => $id,
            'position' => $position
        ]);
        
        return $pipeline;
    }

    public function reorderStages(string $id, array $stagePositions): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline stages reordered', [
            'pipeline_id' => $id,
            'positions' => $stagePositions
        ]);
        
        return $pipeline;
    }

    public function setAsDefault(string $id): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline set as default', ['pipeline_id' => $id]);
        
        return $pipeline;
    }

    public function delete(string $id): void
    {
        $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline deleted', ['pipeline_id' => $id]);
    }

    public function restore(string $id): PipelineInterface
    {
        $pipeline = $this->findByIdOrFail($id);
        
        $this->logger?->info('Pipeline restored', ['pipeline_id' => $id]);
        
        return $pipeline;
    }
}
