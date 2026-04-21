<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\Services;

use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\Exceptions\InvalidVendorStatusTransition;
use Nexus\Vendor\Services\VendorStatusTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VendorStatusTransitionPolicyTest extends TestCase
{
    private VendorStatusTransitionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new VendorStatusTransitionPolicy();
    }

    /**
     * @return array<string, array{0: VendorStatus, 1: VendorStatus}>
     */
    public static function allowedTransitionsProvider(): array
    {
        return [
            'draft to under review' => [VendorStatus::Draft, VendorStatus::UnderReview],
            'under review to approved' => [VendorStatus::UnderReview, VendorStatus::Approved],
            'approved to restricted' => [VendorStatus::Approved, VendorStatus::Restricted],
            'approved to suspended' => [VendorStatus::Approved, VendorStatus::Suspended],
            'restricted to approved' => [VendorStatus::Restricted, VendorStatus::Approved],
            'suspended to approved' => [VendorStatus::Suspended, VendorStatus::Approved],
            'approved to archived' => [VendorStatus::Approved, VendorStatus::Archived],
        ];
    }

    /**
     * @return array<string, array{0: VendorStatus, 1: VendorStatus, 2: string}>
     */
    public static function rejectedTransitionsProvider(): array
    {
        return [
            'draft to approved' => [
                VendorStatus::Draft,
                VendorStatus::Approved,
                'Cannot transition vendor status from Draft to Approved.',
            ],
            'archived to approved' => [
                VendorStatus::Archived,
                VendorStatus::Approved,
                'Cannot transition vendor status from Archived to Approved.',
            ],
            'archived to draft' => [
                VendorStatus::Archived,
                VendorStatus::Draft,
                'Cannot transition vendor status from Archived to Draft.',
            ],
        ];
    }

    #[DataProvider('allowedTransitionsProvider')]
    public function testAllowedTransitionsDoNotThrow(VendorStatus $from, VendorStatus $to): void
    {
        $this->policy->assertCanTransition($from, $to);
        self::addToAssertionCount(1);
    }

    #[DataProvider('rejectedTransitionsProvider')]
    public function testRejectedTransitionsThrow(VendorStatus $from, VendorStatus $to, string $message): void
    {
        $this->expectException(InvalidVendorStatusTransition::class);
        $this->expectExceptionMessage($message);

        $this->policy->assertCanTransition($from, $to);
    }
}
