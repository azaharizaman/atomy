<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Exceptions\TransferNotFoundException;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\InvalidTransferException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;

/**
 * Transfer Management API Controller
 */
class TransferController extends Controller
{
    public function __construct(
        private readonly TransferManagerInterface $manager,
        private readonly TransferRepositoryInterface $repository
    ) {}

    /**
     * List all transfers
     */
    public function index(Request $request): JsonResponse
    {
        $staffId = $request->get('staff_id');
        $status = $request->get('status');
        
        if ($staffId) {
            $transfers = $this->repository->getByStaff($staffId);
        } elseif ($status === 'pending') {
            $transfers = $this->repository->getPendingTransfers();
        } else {
            $transfers = [];
        }

        return response()->json(['data' => $transfers]);
    }

    /**
     * Store a new transfer request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|string|exists:staff,id',
            'transfer_type' => 'required|string|in:promotion,lateral_move,demotion,relocation',
            'from_department_id' => 'nullable|string|exists:departments,id',
            'to_department_id' => 'nullable|string|exists:departments,id',
            'from_office_id' => 'nullable|string|exists:offices,id',
            'to_office_id' => 'nullable|string|exists:offices,id',
            'from_position' => 'nullable|string|max:255',
            'to_position' => 'nullable|string|max:255',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string',
            'requested_by' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $transfer = $this->manager->createTransferRequest($validated);
            return response()->json(['data' => $transfer], 201);
        } catch (StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException | InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific transfer
     */
    public function show(string $id): JsonResponse
    {
        $transfer = $this->manager->getTransfer($id);
        
        if (!$transfer) {
            return response()->json(['error' => 'Transfer not found'], 404);
        }

        return response()->json(['data' => $transfer]);
    }

    /**
     * Update a transfer (limited fields)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'effective_date' => 'sometimes|date',
            'reason' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $transfer = $this->repository->update($id, $validated);
            return response()->json(['data' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete/cancel a transfer
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->cancelTransfer($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (TransferNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve a transfer
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'approved_by' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ]);

        try {
            $transfer = $this->manager->approveTransfer(
                $id,
                $validated['approved_by'],
                $validated['comment'] ?? ''
            );
            return response()->json(['data' => $transfer]);
        } catch (TransferNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Reject a transfer
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'rejected_by' => 'required|string|max:255',
            'reason' => 'required|string',
        ]);

        try {
            $transfer = $this->manager->rejectTransfer(
                $id,
                $validated['rejected_by'],
                $validated['reason']
            );
            return response()->json(['data' => $transfer]);
        } catch (TransferNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Complete a transfer
     */
    public function complete(string $id): JsonResponse
    {
        try {
            $transfer = $this->manager->completeTransfer($id);
            return response()->json(['data' => $transfer]);
        } catch (TransferNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Rollback a completed transfer
     */
    public function rollback(string $id): JsonResponse
    {
        try {
            $transfer = $this->manager->rollbackTransfer($id);
            return response()->json(['data' => $transfer]);
        } catch (TransferNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidTransferException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get transfer history for a staff member
     */
    public function history(string $staffId): JsonResponse
    {
        try {
            $history = $this->manager->getStaffTransferHistory($staffId);
            return response()->json(['data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
