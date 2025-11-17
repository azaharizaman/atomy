<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowDelegation;
use Nexus\Workflow\Contracts\{DelegationInterface, DelegationRepositoryInterface};

/**
 * Eloquent implementation of DelegationRepositoryInterface
 */
final readonly class DbDelegationRepository implements DelegationRepositoryInterface
{
    public function findById(string $id): DelegationInterface
    {
        return WorkflowDelegation::findOrFail($id);
    }

    public function findActiveForUser(string $userId): array
    {
        return WorkflowDelegation::where('delegator_id', $userId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->get()
            ->all();
    }

    public function getDelegationChain(string $userId): array
    {
        $chain = [];
        $currentUserId = $userId;
        $depth = 0;
        
        while ($depth < 3) {
            $delegation = WorkflowDelegation::where('delegator_id', $currentUserId)
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->first();
            
            if (!$delegation) {
                break;
            }
            
            $chain[] = $delegation;
            $currentUserId = $delegation->delegatee_id;
            $depth++;
        }
        
        return $chain;
    }

    public function save(DelegationInterface $delegation): void
    {
        if ($delegation instanceof WorkflowDelegation) {
            $delegation->save();
        }
    }

    public function delete(string $id): void
    {
        WorkflowDelegation::destroy($id);
    }
}
