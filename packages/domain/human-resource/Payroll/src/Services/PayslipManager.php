<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\Contracts\PayslipQueryInterface;
use Nexus\Payroll\Contracts\PayslipPersistInterface;
use Nexus\Payroll\Exceptions\PayslipNotFoundException;
use Nexus\Payroll\Exceptions\PayslipValidationException;
use Nexus\Payroll\ValueObjects\PayslipStatus;

/**
 * Service for managing payslips.
 */
final readonly class PayslipManager
{
    public function __construct(
        private PayslipQueryInterface $payslipQuery,
        private PayslipPersistInterface $payslipPersist,
    ) {
    }
    
    public function getPayslipById(string $id): PayslipInterface
    {
        $payslip = $this->payslipQuery->findById($id);
        
        if (!$payslip) {
            throw PayslipNotFoundException::forId($id);
        }
        
        return $payslip;
    }
    
    public function approvePayslip(string $id, string $approvedBy): PayslipInterface
    {
        $payslip = $this->getPayslipById($id);
        
        if ($payslip->isApproved() || $payslip->isPaid()) {
            throw PayslipValidationException::cannotModifyApproved();
        }
        
        return $this->payslipPersist->update($id, [
            'status' => PayslipStatus::APPROVED->value,
            'approved_by' => $approvedBy,
            'approved_at' => new \DateTime(),
        ]);
    }
    
    public function markAsPaid(string $id): PayslipInterface
    {
        $payslip = $this->getPayslipById($id);
        
        if (!$payslip->isApproved()) {
            throw new PayslipValidationException("Payslip must be approved before marking as paid.");
        }
        
        return $this->payslipPersist->update($id, [
            'status' => PayslipStatus::PAID->value,
        ]);
    }
    
    public function getEmployeePayslips(string $employeeId, ?int $year = null): array
    {
        return $this->payslipQuery->getEmployeePayslips($employeeId, $year);
    }
}
