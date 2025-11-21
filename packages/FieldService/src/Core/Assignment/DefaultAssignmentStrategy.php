<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Assignment;

use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\ValueObjects\SkillSet;
use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Psr\Log\LoggerInterface;

/**
 * Default Technician Assignment Strategy (Tier 1)
 *
 * Assigns technicians based on:
 * 1. Required skills match
 * 2. Location proximity to job site
 * 3. Daily capacity availability (≤8 hours)
 */
final readonly class DefaultAssignmentStrategy implements TechnicianAssignmentStrategyInterface
{
    private const float MAX_DAILY_HOURS = 8.0;
    private const int MAX_SCORE = 100;

    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private DistanceCalculatorInterface $distanceCalculator,
        private GeoRepositoryInterface $geoRepository,
        private LoggerInterface $logger
    ) {
    }

    public function findBestTechnician(
        WorkOrderInterface $workOrder,
        array $availableTechnicians
    ): ?StaffInterface {
        $scoredTechnicians = [];

        foreach ($availableTechnicians as $technician) {
            // Check if technician has required skills
            if (!$this->hasRequiredSkills($workOrder, $technician)) {
                continue;
            }

            // Check if technician has capacity
            if (!$this->hasCapacity($technician, $workOrder)) {
                continue;
            }

            $score = $this->scoreTechnician($workOrder, $technician);
            $scoredTechnicians[] = [
                'technician' => $technician,
                'score' => $score,
            ];
        }

        if (empty($scoredTechnicians)) {
            $this->logger->warning('No suitable technician found for work order', [
                'work_order_id' => $workOrder->getId(),
                'service_type' => $workOrder->getServiceType()->value,
            ]);
            return null;
        }

        // Sort by score descending
        usort($scoredTechnicians, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredTechnicians[0]['technician'];
    }

    public function scoreTechnician(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        $scores = [];

        // Skills match score (40% weight)
        $scores['skills'] = $this->calculateSkillScore($workOrder, $technician) * 0.4;

        // Proximity score (40% weight)
        $scores['proximity'] = $this->calculateProximityScore($workOrder, $technician) * 0.4;

        // Capacity score (20% weight)
        $scores['capacity'] = $this->calculateCapacityScore($workOrder, $technician) * 0.2;

        $totalScore = array_sum($scores);

        $this->logger->debug('Technician scored', [
            'technician_id' => $technician->getId(),
            'work_order_id' => $workOrder->getId(),
            'scores' => $scores,
            'total' => $totalScore,
        ]);

        return $totalScore;
    }

    /**
     * Check if technician has all required skills.
     */
    private function hasRequiredSkills(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): bool {
        $requiredSkills = $this->getRequiredSkills($workOrder);
        
        if ($requiredSkills->isEmpty()) {
            return true; // No specific skills required
        }

        $technicianSkills = $this->getTechnicianSkills($technician);
        
        return $technicianSkills->matches($requiredSkills);
    }

    /**
     * Check if technician has capacity for this work order.
     */
    private function hasCapacity(
        StaffInterface $technician,
        WorkOrderInterface $workOrder
    ): bool {
        if ($workOrder->getScheduledStart() === null) {
            return true; // No specific date yet
        }

        $date = $workOrder->getScheduledStart();
        $scheduledHours = $this->workOrderRepository->getTechnicianScheduledHours(
            $technician->getId(),
            $date
        );

        $estimatedDuration = $this->estimateJobDuration($workOrder);
        
        return ($scheduledHours + $estimatedDuration) <= self::MAX_DAILY_HOURS;
    }

    /**
     * Calculate skill match score (0-100).
     */
    private function calculateSkillScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        $requiredSkills = $this->getRequiredSkills($workOrder);
        
        if ($requiredSkills->isEmpty()) {
            return self::MAX_SCORE; // No skills required = perfect match
        }

        $technicianSkills = $this->getTechnicianSkills($technician);
        
        // Calculate overlap percentage
        $intersection = $technicianSkills->intersect($requiredSkills);
        $matchPercentage = $intersection->count() / $requiredSkills->count();
        
        return $matchPercentage * self::MAX_SCORE;
    }

    /**
     * Calculate proximity score based on distance to job site (0-100).
     */
    private function calculateProximityScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        // If no location data, return neutral score
        if ($workOrder->getServiceLocationId() === null) {
            return 50.0;
        }

        // Get work order location from metadata
        $workOrderMetadata = $workOrder->getMetadata();
        if (!isset($workOrderMetadata['service_location_coordinates'])) {
            return 50.0;
        }

        $serviceCoords = $workOrderMetadata['service_location_coordinates'];
        if (!isset($serviceCoords['latitude']) || !isset($serviceCoords['longitude'])) {
            return 50.0;
        }

        // Get technician current location from metadata
        $technicianMetadata = $technician->getMetadata();
        if (!isset($technicianMetadata['current_location'])) {
            return 50.0; // No location data available
        }

        $techCoords = $technicianMetadata['current_location'];
        if (!isset($techCoords['latitude']) || !isset($techCoords['longitude'])) {
            return 50.0;
        }

        try {
            $serviceLocation = new Coordinates(
                (float) $serviceCoords['latitude'],
                (float) $serviceCoords['longitude']
            );
            $technicianLocation = new Coordinates(
                (float) $techCoords['latitude'],
                (float) $techCoords['longitude']
            );

            $distance = $this->distanceCalculator->calculate($technicianLocation, $serviceLocation);
            
            // Score based on distance: closer = higher score
            // 0-5 km: 100 points
            // 5-20 km: 80 points
            // 20-50 km: 50 points
            // 50+ km: 20 points
            $kilometers = $distance->meters / 1000;
            
            return match(true) {
                $kilometers <= 5 => 100.0,
                $kilometers <= 20 => 80.0,
                $kilometers <= 50 => 50.0,
                default => 20.0,
            };
        } catch (\Exception $e) {
            $this->logger->warning('Failed to calculate proximity score', [
                'error' => $e->getMessage(),
                'work_order_id' => $workOrder->getId(),
                'technician_id' => $technician->getId(),
            ]);
            return 50.0;
        }
    }

    /**
     * Calculate capacity score based on available time (0-100).
     */
    private function calculateCapacityScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        if ($workOrder->getScheduledStart() === null) {
            return self::MAX_SCORE;
        }

        $date = $workOrder->getScheduledStart();
        $scheduledHours = $this->workOrderRepository->getTechnicianScheduledHours(
            $technician->getId(),
            $date
        );

        $availableHours = self::MAX_DAILY_HOURS - $scheduledHours;
        $utilizationPercentage = $availableHours / self::MAX_DAILY_HOURS;
        
        return $utilizationPercentage * self::MAX_SCORE;
    }

    /**
     * Get required skills from work order metadata.
     */
    private function getRequiredSkills(WorkOrderInterface $workOrder): SkillSet
    {
        $metadata = $workOrder->getMetadata();
        
        if (!isset($metadata['required_skills'])) {
            return SkillSet::empty();
        }

        return SkillSet::fromArray($metadata['required_skills']);
    }

    /**
     * Get technician skills from staff metadata.
     */
    private function getTechnicianSkills(StaffInterface $technician): SkillSet
    {
        $metadata = $technician->getMetadata();
        
        if (!isset($metadata['skills']) && !isset($metadata['competencies'])) {
            return SkillSet::empty();
        }

        // Try 'skills' first, then fallback to 'competencies'
        $skillsData = $metadata['skills'] ?? $metadata['competencies'] ?? [];
        
        if (!is_array($skillsData)) {
            return SkillSet::empty();
        }

        // Extract skill names if array of objects/arrays
        $skillNames = [];
        foreach ($skillsData as $skill) {
            if (is_string($skill)) {
                $skillNames[] = $skill;
            } elseif (is_array($skill) && isset($skill['name'])) {
                $skillNames[] = $skill['name'];
            } elseif (is_array($skill) && isset($skill['skill'])) {
                $skillNames[] = $skill['skill'];
            }
        }

        return SkillSet::fromArray($skillNames);
    }

    /**
     * Estimate job duration based on service type.
     */
    private function estimateJobDuration(WorkOrderInterface $workOrder): float
    {
        $serviceType = $workOrder->getServiceType();
        $baseDuration = 2.0; // 2 hours base estimate
        
        return $baseDuration * $serviceType->durationMultiplier();
    }
}
