<?php

declare(strict_types=1);

namespace Nexus\QualityControl\Contracts;

use Nexus\QualityControl\Enums\InspectionDecision;
use Nexus\QualityControl\Enums\InspectionStatus;

/**
 * Interface for an inspection lot/record
 */
interface InspectionInterface
{
    public function getId(): string;
    public function getProductId(): string;
    public function getBatchId(): ?string;
    public function getQuantity(): float;
    public function getSamplesCount(): int;
    public function getStatus(): InspectionStatus;
    public function getDecision(): ?InspectionDecision;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getCompletedAt(): ?\DateTimeImmutable;
}
