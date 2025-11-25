<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Nexus\Hrm\Contracts\DisciplinaryInterface;
use Nexus\Hrm\Contracts\DisciplinaryRepositoryInterface;
use Nexus\Hrm\Exceptions\DisciplinaryNotFoundException;
use Nexus\Hrm\Exceptions\DisciplinaryValidationException;
use Nexus\Hrm\ValueObjects\DisciplinaryStatus;

/**
 * Service for managing disciplinary cases.
 */
readonly class DisciplinaryManager
{
    public function __construct(
        private DisciplinaryRepositoryInterface $disciplinaryRepository,
    ) {
    }
    
    public function createCase(array $data): DisciplinaryInterface
    {
        $data['status'] ??= DisciplinaryStatus::REPORTED->value;
        $data['reported_date'] ??= new \DateTime();
        
        return $this->disciplinaryRepository->create($data);
    }
    
    public function updateCase(string $id, array $data): DisciplinaryInterface
    {
        $case = $this->getCaseById($id);
        
        if ($case->isClosed()) {
            throw DisciplinaryValidationException::cannotModifyClosed();
        }
        
        return $this->disciplinaryRepository->update($id, $data);
    }
    
    public function startInvestigation(string $id, string $investigatorId): DisciplinaryInterface
    {
        $case = $this->getCaseById($id);
        
        return $this->disciplinaryRepository->update($id, [
            'status' => DisciplinaryStatus::UNDER_INVESTIGATION->value,
            'investigated_by' => $investigatorId,
        ]);
    }
    
    public function resolveCase(string $id, string $resolution, string $actionTaken): DisciplinaryInterface
    {
        $case = $this->getCaseById($id);
        
        return $this->disciplinaryRepository->update($id, [
            'status' => DisciplinaryStatus::RESOLVED->value,
            'resolution' => $resolution,
            'action_taken' => $actionTaken,
            'investigation_completed_at' => new \DateTime(),
        ]);
    }
    
    public function closeCase(string $id, string $closedBy): DisciplinaryInterface
    {
        $case = $this->getCaseById($id);
        
        return $this->disciplinaryRepository->update($id, [
            'status' => DisciplinaryStatus::CLOSED->value,
            'closed_at' => new \DateTimeImmutable(),
            'closed_by' => $closedBy,
        ]);
    }
    
    public function getCaseById(string $id): DisciplinaryInterface
    {
        $case = $this->disciplinaryRepository->findById($id);
        
        if (!$case) {
            throw DisciplinaryNotFoundException::forId($id);
        }
        
        return $case;
    }
    
    public function getEmployeeCases(string $employeeId, array $filters = []): array
    {
        return $this->disciplinaryRepository->getEmployeeCases($employeeId, $filters);
    }
    
    public function getOpenCases(string $tenantId): array
    {
        return $this->disciplinaryRepository->getOpenCases($tenantId);
    }
}
