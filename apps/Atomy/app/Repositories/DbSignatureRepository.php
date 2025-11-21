<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CustomerSignature;
use Nexus\FieldService\Contracts\CustomerSignatureInterface;
use Nexus\FieldService\Contracts\SignatureRepositoryInterface;

final readonly class DbSignatureRepository implements SignatureRepositoryInterface
{
    public function __construct() {}

    public function save(CustomerSignatureInterface $signature): void
    {
        if ($signature instanceof CustomerSignature) {
            $signature->save();
            return;
        }

        throw new \InvalidArgumentException('CustomerSignature must be an Eloquent model');
    }

    public function findByWorkOrder(string $workOrderId): ?CustomerSignatureInterface
    {
        return CustomerSignature::where('work_order_id', $workOrderId)->first();
    }

    public function delete(string $id): void
    {
        $signature = CustomerSignature::findOrFail($id);
        $signature->delete();
    }

    public function verifyIntegrity(string $signatureId): bool
    {
        $signature = CustomerSignature::findOrFail($signatureId);
        return $signature->verifyIntegrity();
    }
}
