<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Nexus\Adapter\Laravel\Vendor\Exceptions\VendorNotFoundException;
use Nexus\Adapter\Laravel\Vendor\Mappers\LaravelVendorMapper;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendor;
use Nexus\Vendor\Contracts\VendorInterface;
use Nexus\Vendor\Contracts\VendorPersistInterface;
use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\Contracts\VendorStatusTransitionPolicyInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorId;

final readonly class EloquentVendorRepository implements VendorQueryInterface, VendorPersistInterface
{
    public function __construct(
        private VendorStatusTransitionPolicyInterface $statusTransitionPolicy,
    ) {
    }

    public function findByTenantAndId(string $tenantId, string $vendorId): ?VendorInterface
    {
        $tenantId = $this->requireTenantId($tenantId);
        $vendorId = $this->requireVendorId($vendorId);

        /** @var EloquentVendor|null $vendor */
        $vendor = EloquentVendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$tenantId])
            ->whereKey($vendorId)
            ->first();

        return $vendor instanceof EloquentVendor ? LaravelVendorMapper::fromModel($vendor) : null;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, VendorInterface>
     */
    public function search(string $tenantId, array $filters = []): array
    {
        $tenantId = $this->requireTenantId($tenantId);

        $query = EloquentVendor::query()->whereRaw('lower(tenant_id) = ?', [$tenantId]);
        $this->applyFilters($query, $filters);

        return $query
            ->orderBy('display_name')
            ->orderBy('id')
            ->get()
            ->map(static function (EloquentVendor $vendor): VendorInterface {
                return LaravelVendorMapper::fromModel($vendor);
            })
            ->values()
            ->all();
    }

    public function save(string $tenantId, VendorInterface $vendor): VendorInterface
    {
        $tenantId = $this->requireTenantId($tenantId);
        $vendorId = $this->requireVendorId((string) $vendor->getId());
        $model = EloquentVendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$tenantId])
            ->whereKey($vendorId)
            ->first() ?? new EloquentVendor();

        $model->forceFill([
            'id' => $vendorId,
            'tenant_id' => $tenantId,
            'legal_name' => $vendor->getLegalName()->getValue(),
            'display_name' => $vendor->getDisplayName()->getValue(),
            'registration_number' => $vendor->getRegistrationNumber()->getValue(),
            'country_of_registration' => $vendor->getCountryOfRegistration(),
            'status' => $vendor->getStatus()->value,
            'primary_contact_name' => $vendor->getPrimaryContactName(),
            'primary_contact_email' => $vendor->getPrimaryContactEmail(),
            'primary_contact_phone' => $vendor->getPrimaryContactPhone() ?? '',
            'name' => $vendor->getLegalName()->getValue(),
            'trading_name' => $vendor->getDisplayName()->getValue(),
            'country_code' => $vendor->getCountryOfRegistration(),
            'email' => $vendor->getPrimaryContactEmail(),
            'phone' => $vendor->getPrimaryContactPhone(),
        ]);

        $approvalRecord = $vendor->getApprovalRecord();
        if ($approvalRecord !== null) {
            $model->approved_by_user_id = $approvalRecord->getApprovedByUserId();
            $model->approved_at = $approvalRecord->getApprovedAt();
            $model->approval_note = $approvalRecord->getApprovalNote();
        }

        $model->save();

        return LaravelVendorMapper::fromModel($model);
    }

    public function updateStatus(
        string $tenantId,
        string $vendorId,
        VendorStatus $status,
        ?VendorApprovalRecord $approvalRecord = null,
    ): VendorInterface {
        $tenantId = $this->requireTenantId($tenantId);
        $vendorId = $this->requireVendorId($vendorId);

        /** @var EloquentVendor|null $vendor */
        $vendor = EloquentVendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$tenantId])
            ->whereKey($vendorId)
            ->first();

        if ($vendor === null) {
            throw new VendorNotFoundException($tenantId, $vendorId);
        }

        $this->statusTransitionPolicy->assertCanTransition($vendor->getStatus(), $status);

        $existingApprovalRecord = $vendor->getApprovalRecord();
        $vendor->status = $status->value;

        if ($approvalRecord !== null) {
            $vendor->approved_by_user_id = $approvalRecord->getApprovedByUserId();
            $vendor->approved_at = $approvalRecord->getApprovedAt();
            $vendor->approval_note = $approvalRecord->getApprovalNote();
        } elseif ($existingApprovalRecord !== null) {
            $vendor->approved_by_user_id = $existingApprovalRecord->getApprovedByUserId();
            $vendor->approved_at = $existingApprovalRecord->getApprovedAt();
            $vendor->approval_note = $existingApprovalRecord->getApprovalNote();
        }

        $vendor->save();

        return LaravelVendorMapper::fromModel($vendor);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $status = $filters['status'] instanceof VendorStatus
                ? $filters['status']->value
                : trim((string) $filters['status']);

            if ($status !== '') {
                $query->where('status', $status);
            }
        }

        foreach ([
            'display_name',
            'legal_name',
            'registration_number',
            'country_of_registration',
            'primary_contact_email',
            'primary_contact_name',
            'approved_by_user_id',
        ] as $column) {
            if (!isset($filters[$column])) {
                continue;
            }

            $value = trim((string) $filters[$column]);
            if ($value !== '') {
                $query->where($column, $value);
            }
        }
    }

    private function requireTenantId(string $tenantId): string
    {
        $tenantId = trim($tenantId);

        if ($tenantId === '') {
            throw new \InvalidArgumentException('tenantId cannot be empty.');
        }

        return strtolower($tenantId);
    }

    private function requireVendorId(string $vendorId): string
    {
        $vendorId = trim($vendorId);

        if ($vendorId === '') {
            throw new \InvalidArgumentException('vendorId cannot be empty.');
        }

        return (new VendorId($vendorId))->getValue();
    }
}
