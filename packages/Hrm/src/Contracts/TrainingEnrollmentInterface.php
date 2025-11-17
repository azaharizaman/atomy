<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents a training enrollment entity.
 */
interface TrainingEnrollmentInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getTrainingId(): string;
    
    public function getEmployeeId(): string;
    
    public function getEnrolledAt(): DateTimeInterface;
    
    public function getStatus(): string;
    
    public function getCompletedAt(): ?DateTimeInterface;
    
    public function getAttendancePercentage(): ?float;
    
    public function getScore(): ?float;
    
    public function getPassingScore(): ?float;
    
    public function isPassed(): ?bool;
    
    public function getCertificateIssued(): bool;
    
    public function getCertificateIssuedAt(): ?DateTimeInterface;
    
    public function getFeedback(): ?string;
    
    public function getMetadata(): array;
    
    public function isEnrolled(): bool;
    
    public function isCompleted(): bool;
    
    public function isCancelled(): bool;
}
