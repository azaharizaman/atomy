<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface CountryLawRepositoryInterface
{
    public function findByCountryCode(string $countryCode): ?object;
    
    public function getLeaveRules(string $countryCode, string $leaveType): array;
}
