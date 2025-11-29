<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferManagerInterface $manager,
        private readonly TransferRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staffId = $request->query('staff_id');
        $status = $request->query('status');

        if ($staffId) {
            $transfers = $this->repository->getByStaff($staffId);
        } elseif ($status === 'pending') {
            $transfers = $this->repository->getPendingTransfers();
        } else {
            // Default to pending if no filters? Or maybe return empty/error?
            // For now let's return pending as a safe default for "inbox" style view
            $transfers = $this->repository->getPendingTransfers();
        }

        return response()->json($transfers);
    }

    public function show(string $id): JsonResponse
    {
        $transfer = $this->manager->getTransfer($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
        return response()->json($transfer);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'staff_id' => 'required|string',
            'from_department_id' => 'nullable|string',
            'to_department_id' => 'nullable|string',
            'from_office_id' => 'nullable|string',
            'to_office_id' => 'nullable|string',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string',
            'transfer_type' => 'required|string',
        ]);

        try {
            $transfer = $this->manager->createTransferRequest($data);
            return response()->json($transfer, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'approved_by' => 'required|string',
            'comment' => 'required|string',
        ]);

        try {
            $transfer = $this->manager->approveTransfer($id, $data['approved_by'], $data['comment']);
            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'rejected_by' => 'required|string',
            'reason' => 'required|string',
        ]);

        try {
            $transfer = $this->manager->rejectTransfer($id, $data['rejected_by'], $data['reason']);
            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        try {
            $this->manager->cancelTransfer($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function complete(string $id): JsonResponse
    {
        try {
            $transfer = $this->manager->completeTransfer($id);
            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
