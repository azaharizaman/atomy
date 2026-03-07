<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Coordinators\SourceToContractCoordinator;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\QuoteSubmission;
use Nexus\ProcurementOperations\DTOs\RFQRequest;
use Nexus\ProcurementOperations\Services\QuoteComparisonService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SourceToContractCoordinator::class)]
final class SourceToContractCoordinatorTest extends TestCase
{
    private SecureIdGeneratorInterface&MockObject $secureIdGenerator;
    private SourceToContractCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->secureIdGenerator = $this->createMock(SecureIdGeneratorInterface::class);

        $this->coordinator = new SourceToContractCoordinator(
            comparisonService: new QuoteComparisonService(),
            logger: new NullLogger(),
            secureIdGenerator: $this->secureIdGenerator,
        );
    }

    #[Test]
    public function publish_rfq_requires_tenant_context(): void
    {
        $result = $this->coordinator->publishRFQ(new RFQRequest(
            tenantId: ' ',
            title: 'Network Equipment',
            items: [],
        ));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Tenant ID', (string) $result->message);
    }

    #[Test]
    public function publish_rfq_uses_secure_random_suffix_for_identifiers(): void
    {
        $this->secureIdGenerator
            ->expects($this->once())
            ->method('randomHex')
            ->with(4)
            ->willReturn('a1b2c3d4');

        $result = $this->coordinator->publishRFQ(new RFQRequest(
            tenantId: 'tenant-1',
            title: 'Office Supplies RFQ',
            items: [
                ['productId' => 'prod-1', 'description' => 'Paper', 'quantity' => 10.0, 'uom' => 'BOX'],
            ],
        ));

        $this->assertTrue($result->success);
        $this->assertSame('rfq-a1b2c3d4', $result->rfqId);
        $this->assertStringEndsWith('-A1B2C3D4', (string) $result->rfqNumber);
        $this->assertSame('published', $result->status);
    }

    #[Test]
    public function submit_quote_rejects_blank_vendor_id(): void
    {
        $result = $this->coordinator->submitQuote(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            submission: new QuoteSubmission(vendorId: ' ', items: []),
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('vendor ID', (string) $result->message);
    }

    #[Test]
    public function compare_and_award_fails_when_no_submissions_exist(): void
    {
        $result = $this->coordinator->compareAndAward(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            rankingWeights: ['price' => 0.5, 'quality' => 0.3, 'delivery' => 0.2],
        );

        $this->assertFalse($result->success);
        $this->assertSame('rfq-1', $result->rfqId);
        $this->assertStringContainsString('No quote submissions', (string) $result->message);
    }
}
