<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\Contracts\PayslipRepositoryInterface;
use Nexus\Payroll\Exceptions\PayslipNotFoundException;
use Nexus\Payroll\Exceptions\PayslipValidationException;
use Nexus\Payroll\ValueObjects\PayslipStatus;

/**
 * Service for managing payslips.
 */
readonly class PayslipManager
{
    public function __construct(
        private PayslipRepositoryInterface $payslipRepository,
    ) {
    }
    
    public function getPayslipById(string $id): PayslipInterface
    {
        $payslip = $this->payslipRepository->findById($id);
        
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
        
        return $this->payslipRepository->update($id, [
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
        
        return $this->payslipRepository->update($id, [
            'status' => PayslipStatus::PAID->value,
        ]);
    }
    
    public function getEmployeePayslips(string $employeeId, ?int $year = null): array
    {
        return $this->payslipRepository->getEmployeePayslips($employeeId, $year);
    }
}
