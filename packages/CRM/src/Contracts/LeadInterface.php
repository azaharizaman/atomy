<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\LeadSource;
use Nexus\CRM\ValueObjects\LeadScore;

/**
 * Lead Interface
 * 
 * Represents a sales lead in the CRM system.
 * A lead is a potential customer who has shown interest in products or services.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface LeadInterface
{
    /**
     * Get unique lead identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier for multi-tenancy
     */
    public function getTenantId(): string;

    /**
     * Get lead title or name
     */
    public function getTitle(): string;

    /**
     * Get lead description
     */
    public function getDescription(): ?string;

    /**
     * Get current lead status
     */
    public function getStatus(): LeadStatus;

    /**
     * Get lead source (how the lead was acquired)
     */
    public function getSource(): LeadSource;

    /**
     * Get calculated lead score
     */
    public function getScore(): ?LeadScore;

    /**
     * Get estimated value of potential deal
     */
    public function getEstimatedValue(): ?int;

    /**
     * Get currency code for estimated value (ISO 4217)
     */
    public function getCurrency(): ?string;

    /**
     * Get external reference ID (from marketing automation, etc.)
     */
    public function getExternalRef(): ?string;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last modification timestamp
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Get timestamp when lead was converted to opportunity
     */
    public function getConvertedAt(): ?\DateTimeImmutable;

    /**
     * Get ID of the opportunity created from this lead
     */
    public function getConvertedToOpportunityId(): ?string;

    /**
     * Check if lead is qualified
     */
    public function isQualified(): bool;

    /**
     * Check if lead is convertible to opportunity
     */
    public function isConvertible(): bool;
}
