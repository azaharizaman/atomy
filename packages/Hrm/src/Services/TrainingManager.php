<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Nexus\Hrm\Contracts\TrainingInterface;
use Nexus\Hrm\Contracts\TrainingRepositoryInterface;
use Nexus\Hrm\Contracts\TrainingEnrollmentInterface;
use Nexus\Hrm\Contracts\TrainingEnrollmentRepositoryInterface;
use Nexus\Hrm\Exceptions\TrainingNotFoundException;
use Nexus\Hrm\Exceptions\TrainingValidationException;
use Nexus\Hrm\Exceptions\TrainingEnrollmentNotFoundException;
use Nexus\Hrm\ValueObjects\TrainingStatus;
use Nexus\Hrm\ValueObjects\EnrollmentStatus;

/**
 * Service for managing training programs and enrollments.
 */
readonly class TrainingManager
{
    public function __construct(
        private TrainingRepositoryInterface $trainingRepository,
        private TrainingEnrollmentRepositoryInterface $enrollmentRepository,
    ) {
    }
    
    public function createTraining(array $data): TrainingInterface
    {
        $data['status'] ??= TrainingStatus::PLANNED->value;
        
        return $this->trainingRepository->create($data);
    }
    
    public function updateTraining(string $id, array $data): TrainingInterface
    {
        $training = $this->getTrainingById($id);
        
        return $this->trainingRepository->update($id, $data);
    }
    
    public function enrollEmployee(string $trainingId, string $employeeId): TrainingEnrollmentInterface
    {
        $training = $this->getTrainingById($trainingId);
        
        // Check max participants
        if ($training->getMaxParticipants()) {
            $currentEnrollments = count($this->enrollmentRepository->getTrainingEnrollments($trainingId, [
                'status' => [EnrollmentStatus::ENROLLED->value, EnrollmentStatus::IN_PROGRESS->value]
            ]));
            
            if ($currentEnrollments >= $training->getMaxParticipants()) {
                throw TrainingValidationException::maxParticipantsReached($trainingId);
            }
        }
        
        return $this->enrollmentRepository->create([
            'training_id' => $trainingId,
            'employee_id' => $employeeId,
            'status' => EnrollmentStatus::ENROLLED->value,
            'enrolled_at' => new \DateTime(),
        ]);
    }
    
    public function completeEnrollment(string $enrollmentId, float $score, bool $isPassed): TrainingEnrollmentInterface
    {
        $enrollment = $this->getEnrollmentById($enrollmentId);
        
        return $this->enrollmentRepository->update($enrollmentId, [
            'status' => $isPassed ? EnrollmentStatus::PASSED->value : EnrollmentStatus::FAILED->value,
            'score' => $score,
            'is_passed' => $isPassed,
            'completed_at' => new \DateTime(),
        ]);
    }
    
    public function issueCertificate(string $enrollmentId): TrainingEnrollmentInterface
    {
        $enrollment = $this->getEnrollmentById($enrollmentId);
        
        if (!$enrollment->isPassed()) {
            throw new TrainingValidationException("Cannot issue certificate for failed enrollment.");
        }
        
        return $this->enrollmentRepository->update($enrollmentId, [
            'certificate_issued' => true,
            'certificate_issued_at' => new \DateTime(),
        ]);
    }
    
    public function getTrainingById(string $id): TrainingInterface
    {
        $training = $this->trainingRepository->findById($id);
        
        if (!$training) {
            throw TrainingNotFoundException::forId($id);
        }
        
        return $training;
    }
    
    public function getEnrollmentById(string $id): TrainingEnrollmentInterface
    {
        $enrollment = $this->enrollmentRepository->findById($id);
        
        if (!$enrollment) {
            throw TrainingEnrollmentNotFoundException::forId($id);
        }
        
        return $enrollment;
    }
    
    public function getEmployeeEnrollments(string $employeeId, array $filters = []): array
    {
        return $this->enrollmentRepository->getEmployeeEnrollments($employeeId, $filters);
    }
}
