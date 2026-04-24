<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class VendorRecommendationExcludedCandidate
{
    public string $vendorId;
    public string $vendorName;
    public string $reason;

    public function __construct(
        string $vendorId,
        string $vendorName,
        string $reason,
    ) {
        $vendorId = trim($vendorId);
        $vendorName = trim($vendorName);
        $reason = trim($reason);

        $this->assertNonEmpty($vendorId, 'excluded vendor id');
        $this->assertNonEmpty($vendorName, 'excluded vendor name');
        $this->assertNonEmpty($reason, 'excluded vendor reason');

        $this->vendorId = $vendorId;
        $this->vendorName = $vendorName;
        $this->reason = $reason;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'reason' => $this->reason,
        ];
    }

    private function assertNonEmpty(string $value, string $subject): void
    {
        if ($value === '') {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }
}
