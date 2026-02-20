<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\DTOs;

/**
 * Conversion Result DTO
 * 
 * Represents the result of a lead conversion operation.
 * 
 * @package Nexus\CRMOperations\DTOs
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class ConversionResult
{
    public function __construct(
        public string $leadId,
        public string $opportunityId,
        public string $customerId
    ) {}
}
