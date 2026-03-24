<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Contracts;

interface AwardInterface
{
    public function getId(): string;

    public function getQuotationId(): string;

    public function getVendorId(): string;
}
