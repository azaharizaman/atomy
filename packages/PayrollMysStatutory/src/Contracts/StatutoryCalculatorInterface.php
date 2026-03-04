<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Contracts;

interface StatutoryCalculatorInterface
{
    public function calculate(PayloadInterface $payload): DeductionResultInterface;
    public function getSupportedCountryCode(): string;
    /** @return array<int,string> */
    public function getRequiredEmployeeFields(): array;
    /** @return array<int,string> */
    public function getRequiredCompanyFields(): array;
    public function validatePayload(PayloadInterface $payload): void;
}
