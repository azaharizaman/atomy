<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\PipelineStatus;
use Nexus\CRM\ValueObjects\PipelineStage;

/**
 * Pipeline Persist Interface
 * 
 * Provides write operations for pipelines.
 * Implements CQRS command separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface PipelinePersistInterface
{
    /**
     * Create a new pipeline
     * 
     * @param PipelineStage[] $stages
     */
    public function create(
        string $tenantId,
        string $name,
        array $stages,
        ?string $description = null,
        bool $isDefault = false
    ): PipelineInterface;

    /**
     * Update pipeline details
     */
    public function update(
        string $id,
        ?string $name = null,
        ?string $description = null
    ): PipelineInterface;

    /**
     * Update pipeline status
     */
    public function updateStatus(string $id, PipelineStatus $status): PipelineInterface;

    /**
     * Add stage to pipeline
     */
    public function addStage(string $id, PipelineStage $stage, ?int $position = null): PipelineInterface;

    /**
     * Remove stage from pipeline
     */
    public function removeStage(string $id, int $position): PipelineInterface;

    /**
     * Reorder stages in pipeline
     * 
     * @param int[] $stagePositions Array of stage positions in new order
     */
    public function reorderStages(string $id, array $stagePositions): PipelineInterface;

    /**
     * Set as default pipeline for tenant
     */
    public function setAsDefault(string $id): PipelineInterface;

    /**
     * Delete pipeline (soft delete)
     */
    public function delete(string $id): void;

    /**
     * Restore deleted pipeline
     */
    public function restore(string $id): PipelineInterface;
}
