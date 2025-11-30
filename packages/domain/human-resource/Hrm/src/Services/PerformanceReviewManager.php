<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Nexus\Hrm\Contracts\PerformanceReviewInterface;
use Nexus\Hrm\Contracts\PerformanceReviewRepositoryInterface;
use Nexus\Hrm\Exceptions\PerformanceReviewNotFoundException;
use Nexus\Hrm\Exceptions\PerformanceReviewValidationException;
use Nexus\Hrm\ValueObjects\ReviewStatus;

/**
 * Service for managing performance reviews.
 */
readonly class PerformanceReviewManager
{
    public function __construct(
        private PerformanceReviewRepositoryInterface $reviewRepository,
    ) {
    }
    
    public function createReview(array $data): PerformanceReviewInterface
    {
        $data['status'] ??= ReviewStatus::DRAFT->value;
        
        return $this->reviewRepository->create($data);
    }
    
    public function updateReview(string $id, array $data): PerformanceReviewInterface
    {
        $review = $this->getReviewById($id);
        
        if ($review->isCompleted()) {
            throw PerformanceReviewValidationException::cannotModifyCompleted();
        }
        
        return $this->reviewRepository->update($id, $data);
    }
    
    public function submitReview(string $id): PerformanceReviewInterface
    {
        $review = $this->getReviewById($id);
        
        return $this->reviewRepository->update($id, [
            'status' => ReviewStatus::PENDING->value,
            'submitted_at' => new \DateTime(),
        ]);
    }
    
    public function completeReview(string $id, float $overallScore): PerformanceReviewInterface
    {
        $review = $this->getReviewById($id);
        
        if ($overallScore < 0 || $overallScore > 100) {
            throw PerformanceReviewValidationException::invalidScore($overallScore);
        }
        
        return $this->reviewRepository->update($id, [
            'status' => ReviewStatus::COMPLETED->value,
            'overall_score' => $overallScore,
            'completed_at' => new \DateTime(),
        ]);
    }
    
    public function getReviewById(string $id): PerformanceReviewInterface
    {
        $review = $this->reviewRepository->findById($id);
        
        if (!$review) {
            throw PerformanceReviewNotFoundException::forId($id);
        }
        
        return $review;
    }
    
    public function getEmployeeReviews(string $employeeId, array $filters = []): array
    {
        return $this->reviewRepository->getEmployeeReviews($employeeId, $filters);
    }
    
    public function getPendingReviewsForReviewer(string $reviewerId): array
    {
        return $this->reviewRepository->getPendingReviewsForReviewer($reviewerId);
    }
}
