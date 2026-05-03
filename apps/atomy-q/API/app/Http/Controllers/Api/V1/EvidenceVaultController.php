<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinalizeEvidencePackRequest;
use App\Http\Requests\StoreSupportingEvidenceRequest;
use App\Models\Award;
use App\Models\EvidenceBundle;
use App\Models\QuoteSubmission;
use App\Models\RequisitionSelectedVendor;
use App\Models\Rfq;
use App\Models\SupportingEvidence;
use App\Models\User;
use App\Services\EvidenceVault\AwardEvidencePackFinalizer;
use App\Services\EvidenceVault\EvidencePackNotReadyException;
use App\Services\EvidenceVault\EvidenceVaultSummaryService;
use App\Services\EvidenceVault\SupportingEvidenceStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EvidenceVaultController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly EvidenceVaultSummaryService $summaryService,
        private readonly SupportingEvidenceStorageService $supportingEvidenceStorage,
        private readonly AwardEvidencePackFinalizer $awardEvidencePackFinalizer,
    ) {
    }

    public function show(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        return response()->json([
            'data' => $this->summaryService->summarize($tenantId, $rfq),
        ]);
    }

    public function storeSupportingEvidence(StoreSupportingEvidenceRequest $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        $actor = User::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($this->userId($request))
            ->firstOrFail();

        $validated = $request->validated();
        $file = $request->file('file');
        if (!$file instanceof UploadedFile) {
            abort(422, 'The file field is required.');
        }

        $relations = $this->validatedSupportingEvidenceRelations($tenantId, $rfq, $validated);

        try {
            $supportingEvidence = $this->supportingEvidenceStorage->store(
                $tenantId,
                $rfq,
                $actor,
                $file,
                (string) $validated['reason'],
                $relations,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'Could not store supporting evidence.',
            ], 500);
        }

        return response()->json([
            'data' => $this->supportingEvidenceData($supportingEvidence),
        ], 201);
    }

    public function finalizeAwardPack(FinalizeEvidencePackRequest $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        $actor = User::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($this->userId($request))
            ->firstOrFail();

        try {
            $bundle = $this->awardEvidencePackFinalizer->finalize($tenantId, $rfq, $actor);
        } catch (EvidencePackNotReadyException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'blockers' => $exception->blockers(),
                'errors' => [
                    'evidence' => $exception->blockers(),
                ],
            ], 422);
        }

        return response()->json([
            'data' => $this->bundleData($bundle),
        ], 201);
    }

    public function exportAwardPack(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        $bundle = EvidenceBundle::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('type', 'award_justification')
            ->where('status', 'finalized')
            ->orderByDesc('version')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'bundle_id' => (string) $bundle->id,
                'checksum' => $bundle->checksum,
                'manifest' => $bundle->manifest,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{vendor_id?: string|null, quote_submission_id?: string|null, award_id?: string|null}
     *
     * @throws ValidationException
     */
    private function validatedSupportingEvidenceRelations(string $tenantId, Rfq $rfq, array $validated): array
    {
        $relations = [
            'vendor_id' => $validated['vendor_id'] ?? null,
            'quote_submission_id' => $validated['quote_submission_id'] ?? null,
            'award_id' => $validated['award_id'] ?? null,
        ];
        $errors = [];

        if ($relations['vendor_id'] !== null && !RequisitionSelectedVendor::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('vendor_id', (string) $relations['vendor_id'])
            ->exists()) {
            $errors['vendor_id'] = ['The selected vendor is invalid for this RFQ.'];
        }

        if ($relations['quote_submission_id'] !== null && !QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->whereKey((string) $relations['quote_submission_id'])
            ->exists()) {
            $errors['quote_submission_id'] = ['The selected quote submission is invalid for this RFQ.'];
        }

        if ($relations['award_id'] !== null && !Award::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->whereKey((string) $relations['award_id'])
            ->exists()) {
            $errors['award_id'] = ['The selected award is invalid for this RFQ.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $relations;
    }

    /**
     * @return array<string, mixed>
     */
    private function supportingEvidenceData(SupportingEvidence $supportingEvidence): array
    {
        return [
            'id' => $supportingEvidence->id,
            'rfq_id' => $supportingEvidence->rfq_id,
            'reason' => $supportingEvidence->reason,
            'original_filename' => $supportingEvidence->original_filename,
            'file_type' => $supportingEvidence->file_type,
            'storage_path' => $supportingEvidence->storage_path,
            'checksum' => $supportingEvidence->checksum,
            'uploaded_by' => $supportingEvidence->uploaded_by,
            'uploaded_at' => $supportingEvidence->uploaded_at?->toAtomString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bundleData(EvidenceBundle $bundle): array
    {
        return [
            'id' => (string) $bundle->id,
            'rfq_id' => (string) $bundle->rfq_id,
            'type' => $bundle->type,
            'status' => $bundle->status,
            'version' => $bundle->version,
            'checksum' => $bundle->checksum,
            'finalized_at' => $bundle->finalized_at?->toAtomString(),
            'manifest' => $bundle->manifest,
        ];
    }
}
