<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface QuoteSubmissionInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getRfqId(): string;

    public function getVendorName(): string;
}
