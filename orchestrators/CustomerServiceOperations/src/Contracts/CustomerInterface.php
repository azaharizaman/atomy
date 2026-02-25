<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface CustomerInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getEmail(): string;
    public function getSupportTier(): string; // e.g., Basic, Premium
}
