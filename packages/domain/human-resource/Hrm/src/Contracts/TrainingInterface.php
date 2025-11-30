<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents a training program entity.
 */
interface TrainingInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getTitle(): string;
    
    public function getDescription(): ?string;
    
    public function getCategory(): ?string;
    
    public function getProvider(): ?string;
    
    public function getStartDate(): ?DateTimeInterface;
    
    public function getEndDate(): ?DateTimeInterface;
    
    public function getDurationHours(): ?float;
    
    public function getLocation(): ?string;
    
    public function getMaxParticipants(): ?int;
    
    public function getCost(): ?float;
    
    public function getCurrency(): ?string;
    
    public function getStatus(): string;
    
    public function getMetadata(): array;
    
    public function isActive(): bool;
    
    public function isCompleted(): bool;
    
    public function isCancelled(): bool;
}
