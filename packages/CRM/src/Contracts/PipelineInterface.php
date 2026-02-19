<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\PipelineStatus;
use Nexus\CRM\ValueObjects\PipelineStage;

/**
 * Pipeline Interface
 * 
 * Represents a sales pipeline in the CRM system.
 * A pipeline defines the stages an opportunity goes through.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface PipelineInterface
{
    /**
     * Get unique pipeline identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier for multi-tenancy
     */
    public function getTenantId(): string;

    /**
     * Get pipeline name
     */
    public function getName(): string;

    /**
     * Get pipeline description
     */
    public function getDescription(): ?string;

    /**
     * Get pipeline status
     */
    public function getStatus(): PipelineStatus;

    /**
     * Get pipeline stages in order
     * 
     * @return PipelineStage[]
     */
    public function getStages(): array;

    /**
     * Get number of stages
     */
    public function getStageCount(): int;

    /**
     * Get a specific stage by position
     */
    public function getStageAtPosition(int $position): ?PipelineStage;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last modification timestamp
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Check if pipeline is active
     */
    public function isActive(): bool;

    /**
     * Check if pipeline is default for tenant
     */
    public function isDefault(): bool;
}
