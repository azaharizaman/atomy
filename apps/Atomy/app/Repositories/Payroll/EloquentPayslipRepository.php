<?php

declare(strict_types=1);

namespace App\Repositories\Payroll;

use App\Models\Payslip;
use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\Contracts\PayslipRepositoryInterface;
use Nexus\Payroll\Exceptions\PayslipNotFoundException;

class EloquentPayslipRepository implements PayslipRepositoryInterface
{
    public function findById(string $id): PayslipInterface
    {
        $payslip = Payslip::find($id);
        
        if (!$payslip) {
            throw PayslipNotFoundException::forId($id);
        }
        
        return $payslip;
    }

    public function findByPayslipNumber(string $tenantId, string $payslipNumber): PayslipInterface
    {
        $payslip = Payslip::where('tenant_id', $tenantId)
            ->where('payslip_number', $payslipNumber)
            ->first();
        
        if (!$payslip) {
            throw PayslipNotFoundException::forPayslipNumber($payslipNumber);
        }
        
        return $payslip;
    }

    public function getEmployeePayslips(string $employeeId, ?int $year = null): array
    {
        $query = Payslip::where('employee_id', $employeeId);
        
        if ($year) {
            $query->whereYear('period_start', $year);
        }
        
        return $query->orderBy('period_start', 'desc')->get()->all();
    }

    public function getPayslipsForPeriod(string $tenantId, \DateTimeInterface $periodStart, \DateTimeInterface $periodEnd, array $filters = []): array
    {
        $query = Payslip::where('tenant_id', $tenantId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        
        return $query->with('employee')->get()->all();
    }

    public function save(PayslipInterface $payslip): PayslipInterface
    {
        if ($payslip instanceof Payslip) {
            $payslip->save();
            return $payslip;
        }
        
        throw new \InvalidArgumentException('Payslip must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $payslip = Payslip::find($id);
        
        if (!$payslip) {
            throw PayslipNotFoundException::forId($id);
        }
        
        $payslip->delete();
    }

    public function payslipNumberExists(string $tenantId, string $payslipNumber, ?string $excludeId = null): bool
    {
        $query = Payslip::where('tenant_id', $tenantId)
            ->where('payslip_number', $payslipNumber);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
