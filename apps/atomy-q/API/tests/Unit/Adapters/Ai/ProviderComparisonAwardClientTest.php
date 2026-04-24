<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Exceptions\ComparisonAwardAiException;
use App\Adapters\Ai\ProviderComparisonAwardClient;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProviderComparisonAwardClientTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function actionProvider(): array
    {
        return [
            'comparison overlay' => ['comparisonOverlay', 'comparison_overlay'],
            'award guidance' => ['awardGuidance', 'award_guidance'],
            'award debrief draft' => ['awardDebriefDraft', 'award_debrief_draft'],
            'approval summary' => ['approvalSummary', 'approval_summary'],
        ];
    }

    #[DataProvider('actionProvider')]
    public function testMethodsForceTheExpectedActionAndDoNotAllowCallerOverride(
        string $method,
        string $expectedAction,
    ): void {
        $transport = $this->createMock(ProviderAiTransportInterface::class);
        $transport->expects($this->once())
            ->method('invoke')
            ->with(
                AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                $this->callback(static function (array $actualPayload) use ($expectedAction): bool {
                    return ($actualPayload['tenant_id'] ?? null) === 'tenant-1'
                        && ($actualPayload['action'] ?? null) === $expectedAction;
                }),
            )
            ->willReturn(['status' => 'ok']);

        $client = new ProviderComparisonAwardClient($transport);
        $request = $this->requestForMethod($method);
        $response = $client->{$method}($request);

        self::assertSame(['status' => 'ok'], $response->payload);
    }

    public function testProviderTransportFailuresAreWrappedWithActionContext(): void
    {
        $transport = $this->createMock(ProviderAiTransportInterface::class);
        $transport->expects($this->once())
            ->method('invoke')
            ->willThrowException(new \RuntimeException('transport failed'));

        $client = new ProviderComparisonAwardClient($transport);

        try {
            $client->awardGuidance(new AwardGuidanceRequest(
                tenantId: 'tenant-1',
                awardId: 'award-1',
                rfqId: 'rfq-1',
                comparisonRunId: 'run-1',
                award: [],
                comparisonContext: [],
            ));
            self::fail('Expected ComparisonAwardAiException to be thrown.');
        } catch (ComparisonAwardAiException $exception) {
            self::assertStringContainsString('award_guidance', $exception->getMessage());
            self::assertStringContainsString('"tenant_id":"tenant-1"', $exception->getMessage());
            self::assertStringNotContainsString('comparison_context', $exception->getMessage());
            self::assertStringNotContainsString('award_id', $exception->getMessage());
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }
    }

    private function requestForMethod(string $method): object
    {
        return match ($method) {
            'comparisonOverlay' => new ComparisonOverlayRequest(
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                mode: 'preview',
                comparison: ['winner' => 'vendor-1'],
                snapshot: null,
            ),
            'awardGuidance' => new AwardGuidanceRequest(
                tenantId: 'tenant-1',
                awardId: 'award-1',
                rfqId: 'rfq-1',
                comparisonRunId: 'run-1',
                award: ['award_id' => 'award-1'],
                comparisonContext: ['snapshot' => ['vendors' => []]],
            ),
            'awardDebriefDraft' => new AwardDebriefDraftRequest(
                tenantId: 'tenant-1',
                awardId: 'award-1',
                rfqId: 'rfq-1',
                comparisonRunId: 'run-1',
                vendorId: 'vendor-2',
                award: ['award_id' => 'award-1'],
                losingVendor: ['vendor_id' => 'vendor-2'],
                comparisonContext: ['snapshot' => ['vendors' => []]],
            ),
            'approvalSummary' => new ApprovalSummaryRequest(
                tenantId: 'tenant-1',
                approvalId: 'approval-1',
                rfqId: 'rfq-1',
                comparisonRunId: 'run-1',
                approval: ['approval_id' => 'approval-1'],
                comparisonContext: ['snapshot' => ['vendors' => []]],
            ),
            default => throw new \InvalidArgumentException('Unknown method ' . $method),
        };
    }
}
