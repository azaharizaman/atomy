<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Represents a payroll component (earning or deduction).
 */
interface ComponentInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getName(): string;
    
    public function getCode(): string;
    
    public function getType(): string; // 'earning' or 'deduction'
    
    public function getCalculationMethod(): string;
    
    public function getFixedAmount(): ?float;
    
    public function getPercentageOf(): ?string;
    
    public function getPercentageValue(): ?float;
    
    public function isStatutory(): bool;
    
    public function isTaxable(): bool;
    
    public function isActive(): bool;
    
    public function getMetadata(): array;
}
