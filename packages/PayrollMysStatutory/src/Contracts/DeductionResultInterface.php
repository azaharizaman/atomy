<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Contracts;

interface DeductionResultInterface
{
    public function getTotalEmployeeDeductions(): float;
    public function getTotalEmployerContributions(): float;
    /** @return array<int,array{code:string,name:string,amount:float}> */
    public function getEmployeeDeductionsBreakdown(): array;
    /** @return array<int,array{code:string,name:string,amount:float}> */
    public function getEmployerContributionsBreakdown(): array;
    public function getNetPay(): float;
    public function getTotalCostToEmployer(): float;
    /** @return array<string,mixed> */
    public function getCalculationMetadata(): array;
}
