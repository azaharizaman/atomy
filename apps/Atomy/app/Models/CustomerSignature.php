<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\FieldService\Contracts\CustomerSignatureInterface;
use Nexus\FieldService\ValueObjects\CustomerSignature as CustomerSignatureVO;

/**
 * Customer Signature Eloquent Model
 *
 * @property string $id
 * @property string $work_order_id
 * @property string $signature_data
 * @property string $signature_hash
 * @property string|null $timestamp_signature
 * @property string|null $customer_name
 * @property array|null $gps_location
 * @property \DateTimeInterface $captured_at
 */
class CustomerSignature extends Model implements CustomerSignatureInterface
{
    use HasUlids;

    protected $fillable = [
        'work_order_id',
        'signature_data',
        'signature_hash',
        'timestamp_signature',
        'customer_name',
        'gps_location',
        'captured_at',
    ];

    protected $casts = [
        'gps_location' => 'array',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // CustomerSignatureInterface implementation

    public function getWorkOrderId(): string
    {
        return $this->work_order_id;
    }

    public function getSignatureData(): string
    {
        return $this->signature_data;
    }

    public function getSignatureHash(): string
    {
        return $this->signature_hash;
    }

    public function getTimestampSignature(): ?string
    {
        return $this->timestamp_signature;
    }

    public function getCustomerName(): ?string
    {
        return $this->customer_name;
    }

    public function getGpsLocation(): ?array
    {
        return $this->gps_location;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->captured_at);
    }

    // Business Logic

    public function toValueObject(): CustomerSignatureVO
    {
        return CustomerSignatureVO::createWithHash(
            $this->signature_data,
            $this->signature_hash,
            $this->customer_name,
            $this->timestamp_signature
        );
    }

    public function verifyIntegrity(): bool
    {
        $computedHash = hash('sha256', $this->signature_data);
        return hash_equals($this->signature_hash, $computedHash);
    }

    // Eloquent Relationships

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
