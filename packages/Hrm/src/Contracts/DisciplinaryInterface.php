<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents a disciplinary case entity.
 */
interface DisciplinaryInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getCaseNumber(): string;
    
    public function getIncidentDate(): DateTimeInterface;
    
    public function getReportedDate(): DateTimeInterface;
    
    public function getReportedBy(): string;
    
    public function getCategory(): string;
    
    public function getSeverity(): string;
    
    public function getDescription(): string;
    
    public function getStatus(): string;
    
    public function getInvestigationNotes(): ?string;
    
    public function getInvestigatedBy(): ?string;
    
    public function getInvestigationCompletedAt(): ?DateTimeInterface;
    
    public function getResolution(): ?string;
    
    public function getActionTaken(): ?string;
    
    public function getClosedAt(): ?DateTimeInterface;
    
    public function getClosedBy(): ?string;
    
    public function getFollowUpDate(): ?DateTimeInterface;
    
    public function getMetadata(): array;
    
    public function isOpen(): bool;
    
    public function isUnderInvestigation(): bool;
    
    public function isClosed(): bool;
}
