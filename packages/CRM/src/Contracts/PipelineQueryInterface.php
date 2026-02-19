<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\PipelineStatus;

/**
 * Pipeline Query Interface
 * 
 * Provides read-only query operations for pipelines.
 * Implements CQRS query separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface PipelineQueryInterface
{
    /**
     * Find pipeline by ID
     */
    public function findById(string $id): ?PipelineInterface;

    /**
     * Find pipeline by ID or throw exception
     * 
     * @throws \Nexus\CRM\Exceptions\PipelineNotFoundException
     */
    public function findByIdOrFail(string $id): PipelineInterface;

    /**
     * Find pipeline by name
     */
    public function findByName(string $name): ?PipelineInterface;

    /**
     * Find all pipelines for tenant
     * 
     * @return iterable<PipelineInterface>
     */
    public function findAll(): iterable;

    /**
     * Find pipelines by status
     * 
     * @return iterable<PipelineInterface>
     */
    public function findByStatus(PipelineStatus $status): iterable;

    /**
     * Find active pipelines
     * 
     * @return iterable<PipelineInterface>
     */
    public function findActive(): iterable;

    /**
     * Find default pipeline for tenant
     */
    public function findDefault(): ?PipelineInterface;

    /**
     * Count total pipelines
     */
    public function count(): int;

    /**
     * Count pipelines by status
     */
    public function countByStatus(PipelineStatus $status): int;
}