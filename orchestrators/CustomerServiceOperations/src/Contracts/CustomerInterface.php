<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

use Nexus\CustomerServiceOperations\Enums\SupportTier;

interface CustomerInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getEmail(): string;
    public function getSupportTier(): SupportTier;
}
