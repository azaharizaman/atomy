<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Contracts;

interface PayloadInterface
{
    public function getGrossPay(): float;
    public function getBasicSalary(): float;
    public function getYtdGrossPay(): float;
    public function getYtdTaxPaid(): float;
    /** @return array<string,mixed> */
    public function getEmployeeMetadata(): array;
    /** @return array<string,mixed> */
    public function getCompanyMetadata(): array;
}
