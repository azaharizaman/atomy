<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface NormalizationSourceLineReadInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getQuoteSubmissionId(): string;

    public function getRfqLineItemId(): string;

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array;
}
