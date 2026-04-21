<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Nexus\Adapter\Laravel\Vendor\Exceptions\UnreadableVendorRecordException;
use Nexus\Vendor\Contracts\VendorInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\ValueObjects\RegistrationNumber;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorDisplayName;
use Nexus\Vendor\ValueObjects\VendorId;
use Nexus\Vendor\ValueObjects\VendorLegalName;

final class EloquentVendor extends Model implements VendorInterface
{
    use HasUlids;

    protected $table = 'vendors';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'legal_name',
        'display_name',
        'registration_number',
        'country_of_registration',
        'status',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'approved_by_user_id',
        'approved_at',
        'approval_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function getId(): VendorId
    {
        $key = trim((string) $this->getKey());

        if ($key === '') {
            throw UnreadableVendorRecordException::missingField('id', '');
        }

        return new VendorId($key);
    }

    public function getLegalName(): VendorLegalName
    {
        return new VendorLegalName($this->requireString('legal_name'));
    }

    public function getDisplayName(): VendorDisplayName
    {
        return new VendorDisplayName($this->requireString('display_name'));
    }

    public function getRegistrationNumber(): RegistrationNumber
    {
        return new RegistrationNumber($this->requireString('registration_number'));
    }

    public function getCountryOfRegistration(): string
    {
        return $this->requireString('country_of_registration');
    }

    public function getPrimaryContactName(): string
    {
        return $this->requireString('primary_contact_name');
    }

    public function getPrimaryContactEmail(): string
    {
        return $this->requireString('primary_contact_email');
    }

    public function getPrimaryContactPhone(): ?string
    {
        return $this->nullableString('primary_contact_phone');
    }

    public function getStatus(): VendorStatus
    {
        $status = $this->nullableString('status');

        if ($status === null) {
            throw UnreadableVendorRecordException::missingField('status', (string) $this->getKey());
        }

        $status = VendorStatus::tryFrom($status);

        if ($status === null) {
            throw UnreadableVendorRecordException::invalidStatus((string) $this->status, (string) $this->getKey());
        }

        return $status;
    }

    public function getApprovalRecord(): ?VendorApprovalRecord
    {
        $approvedByUserId = $this->nullableString('approved_by_user_id');
        $approvedAt = $this->getAttribute('approved_at');

        if ($approvedByUserId === null && $approvedAt === null) {
            return null;
        }

        if ($approvedByUserId === null) {
            throw UnreadableVendorRecordException::missingField('approved_by_user_id', (string) $this->getKey());
        }

        if (!$approvedAt instanceof \DateTimeInterface) {
            if ($approvedAt === null) {
                throw UnreadableVendorRecordException::missingField('approved_at', (string) $this->getKey());
            }

            throw UnreadableVendorRecordException::invalidField('approved_at', (string) $this->getKey());
        }

        $approvedAtImmutable = $approvedAt instanceof DateTimeImmutable
            ? $approvedAt
            : DateTimeImmutable::createFromInterface($approvedAt);

        return new VendorApprovalRecord(
            $approvedByUserId,
            $approvedAtImmutable,
            $this->nullableString('approval_note'),
        );
    }

    public function getTenantId(): string
    {
        return $this->requireString('tenant_id');
    }

    private function requireString(string $attribute): string
    {
        $value = trim((string) $this->getAttribute($attribute));

        if ($value === '') {
            throw UnreadableVendorRecordException::missingField($attribute, (string) $this->getKey());
        }

        return $value;
    }

    private function nullableString(string $attribute): ?string
    {
        $value = trim((string) $this->getAttribute($attribute));

        return $value === '' ? null : $value;
    }
}
