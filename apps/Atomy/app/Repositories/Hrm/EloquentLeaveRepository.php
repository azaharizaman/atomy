<?php

declare(strict_types=1);

namespace App\Repositories\Hrm;

use App\Models\Leave;
use Nexus\Hrm\Contracts\LeaveInterface;
use Nexus\Hrm\Contracts\LeaveRepositoryInterface;
use Nexus\Hrm\Exceptions\LeaveNotFoundException;

class EloquentLeaveRepository implements LeaveRepositoryInterface
{
    public function findById(string $id): LeaveInterface
    {
        $leave = Leave::find($id);
        
        if (!$leave) {
            throw LeaveNotFoundException::forId($id);
        }
        
        return $leave;
    }

    public function findByEmployee(string $employeeId, array $filters = []): array
    {
        $query = Leave::where('employee_id', $employeeId);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        
        if (isset($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }
        
        return $query->orderBy('start_date', 'desc')->get()->all();
    }

    public function findOverlapping(string $employeeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $excludeId = null): array
    {
        $query = Leave::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            });
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->get()->all();
    }

    public function getPendingLeavesByApprover(string $approverId): array
    {
        return Leave::whereHas('employee', function ($query) use ($approverId) {
            $query->where('manager_id', $approverId);
        })
        ->where('status', 'pending')
        ->orderBy('submitted_at', 'asc')
        ->get()
        ->all();
    }

    public function save(LeaveInterface $leave): LeaveInterface
    {
        if ($leave instanceof Leave) {
            $leave->save();
            return $leave;
        }
        
        throw new \InvalidArgumentException('Leave must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $leave = Leave::find($id);
        
        if (!$leave) {
            throw LeaveNotFoundException::forId($id);
        }
        
        $leave->delete();
    }
}
