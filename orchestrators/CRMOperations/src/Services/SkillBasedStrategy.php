<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\AssignmentStrategyInterface;

final readonly class SkillBasedStrategy implements AssignmentStrategyInterface
{
    public function assign(array $leadData, array $assignees): ?string
    {
        $leadIndustry = $leadData['industry'] ?? null;
        
        if ($leadIndustry === null) {
            return null;
        }

        $scoredAssignees = [];
        
        foreach ($assignees as $assignee) {
            $skills = $assignee['skills'] ?? [];
            $score = 0;
            
            foreach ($skills as $skill) {
                if (isset($skill['industry']) && $skill['industry'] === $leadIndustry) {
                    $score += $skill['proficiency'] ?? 1;
                }
            }
            
            if ($score > 0) {
                $scoredAssignees[] = [
                    'id' => $assignee['id'],
                    'score' => $score,
                    'workload' => $assignee['current_workload'] ?? 0
                ];
            }
        }
        
        if (empty($scoredAssignees)) {
            return null;
        }

        usort($scoredAssignees, function($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            return $a['workload'] <=> $b['workload'];
        });
        
        return $scoredAssignees[0]['id'];
    }

    public function getName(): string
    {
        return 'skill_based';
    }
}
