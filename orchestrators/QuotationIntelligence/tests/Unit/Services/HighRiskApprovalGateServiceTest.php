<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use Nexus\QuotationIntelligence\Services\HighRiskApprovalGateService;
use PHPUnit\Framework\TestCase;

final class HighRiskApprovalGateServiceTest extends TestCase
{
    public function test_requires_approval_when_high_risk_exists(): void
    {
        $service = new HighRiskApprovalGateService();

        $result = $service->evaluate(
            [[
                'vendor_id' => 'vendor-a',
                'line_count' => 1,
                'risks' => [['level' => 'high', 'message' => 'critical']],
            ]],
            ['ranking' => [['vendor_id' => 'vendor-a', 'total_score' => 80.0]]]
        );

        $this->assertTrue($result['required']);
        $this->assertSame('pending_approval', $result['status']);
    }

    public function test_auto_approves_when_low_risk_and_high_score(): void
    {
        $service = new HighRiskApprovalGateService();

        $result = $service->evaluate(
            [[
                'vendor_id' => 'vendor-a',
                'line_count' => 1,
                'risks' => [],
            ]],
            ['ranking' => [['vendor_id' => 'vendor-a', 'total_score' => 85.0]]]
        );

        $this->assertFalse($result['required']);
        $this->assertSame('auto_approved', $result['status']);
        $this->assertSame([], $result['reasons']);
    }

    public function test_requires_approval_when_top_score_below_threshold(): void
    {
        $service = new HighRiskApprovalGateService();

        $result = $service->evaluate(
            [[
                'vendor_id' => 'vendor-a',
                'line_count' => 1,
                'risks' => [],
            ]],
            ['ranking' => [['vendor_id' => 'vendor-a', 'total_score' => 60.0]]]
        );

        $this->assertTrue($result['required']);
        $this->assertSame('pending_approval', $result['status']);
        $this->assertNotEmpty($result['reasons']);
    }

    public function test_requires_approval_when_scoring_ranking_is_empty(): void
    {
        $service = new HighRiskApprovalGateService();

        $result = $service->evaluate(
            [[
                'vendor_id' => 'vendor-a',
                'line_count' => 1,
                'risks' => [],
            ]],
            ['ranking' => []]
        );

        $this->assertTrue($result['required']);
        $this->assertSame('pending_approval', $result['status']);
    }

    public function test_handles_invalid_top_ranking_shape_as_zero_score(): void
    {
        $service = new HighRiskApprovalGateService();

        $result = $service->evaluate(
            [[
                'vendor_id' => 'vendor-a',
                'line_count' => 1,
                'risks' => [],
            ]],
            ['ranking' => ['invalid-row']]
        );

        $this->assertTrue($result['required']);
        $this->assertSame('pending_approval', $result['status']);
    }
}
