<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents a leave request entity.
 */
interface LeaveInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getLeaveTypeId(): string;
    
    public function getStartDate(): DateTimeInterface;
    
    public function getEndDate(): DateTimeInterface;
    
    public function getTotalDays(): float;
    
    public function getReason(): ?string;
    
    public function getStatus(): string;
    
    public function getSubmittedAt(): DateTimeInterface;
    
    public function getApprovedBy(): ?string;
    
    public function getApprovedAt(): ?DateTimeInterface;
    
    public function getRejectionReason(): ?string;
    
    public function getCancelledAt(): ?DateTimeInterface;
    
    public function getCancellationReason(): ?string;
    
    public function getMetadata(): array;
    
    public function isPending(): bool;
    
    public function isApproved(): bool;
    
    public function isRejected(): bool;
    
    public function isCancelled(): bool;
}
