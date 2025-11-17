<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents a performance review entity.
 */
interface PerformanceReviewInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getReviewTemplateId(): ?string;
    
    public function getReviewPeriodStart(): DateTimeInterface;
    
    public function getReviewPeriodEnd(): DateTimeInterface;
    
    public function getReviewType(): string;
    
    public function getReviewerId(): string;
    
    public function getOverallScore(): ?float;
    
    public function getStatus(): string;
    
    public function getSubmittedAt(): ?DateTimeInterface;
    
    public function getCompletedAt(): ?DateTimeInterface;
    
    public function getReviewerComments(): ?string;
    
    public function getEmployeeComments(): ?string;
    
    public function getStrengths(): ?string;
    
    public function getAreasForImprovement(): ?string;
    
    public function getGoalsForNextPeriod(): ?string;
    
    public function getMetadata(): array;
    
    public function isDraft(): bool;
    
    public function isPending(): bool;
    
    public function isCompleted(): bool;
}
