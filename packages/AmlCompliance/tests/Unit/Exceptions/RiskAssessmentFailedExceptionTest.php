<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Exceptions;

use Nexus\AmlCompliance\Exceptions\RiskAssessmentFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RiskAssessmentFailedException::class)]
final class RiskAssessmentFailedExceptionTest extends TestCase
{
    public function test_missing_party_data_factory(): void
    {
        $exception = RiskAssessmentFailedException::missingPartyData(
            partyId: 'party-123',
            missingFields: ['country_code', 'industry_code']
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('country_code', $exception->getMessage());
        $this->assertSame(2001, $exception->getCode());
    }

    public function test_invalid_jurisdiction_factory(): void
    {
        $exception = RiskAssessmentFailedException::invalidJurisdiction(
            partyId: 'party-123',
            countryCode: 'XX'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('XX', $exception->getMessage());
        $this->assertSame(2002, $exception->getCode());
    }

    public function test_sanctions_check_failed_factory(): void
    {
        $exception = RiskAssessmentFailedException::sanctionsCheckFailed(
            partyId: 'party-123',
            reason: 'Timeout'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('Timeout', $exception->getMessage());
        $this->assertSame(2003, $exception->getCode());
    }

    public function test_calculation_error_factory(): void
    {
        $exception = RiskAssessmentFailedException::calculationError(
            partyId: 'party-123',
            component: 'jurisdiction_risk',
            reason: 'Division by zero'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('jurisdiction_risk', $exception->getMessage());
        $this->assertStringContainsString('Division by zero', $exception->getMessage());
        $this->assertSame(2004, $exception->getCode());
    }

    public function test_threshold_exceeded_factory(): void
    {
        $exception = RiskAssessmentFailedException::thresholdExceeded(
            partyId: 'party-123',
            score: 95,
            threshold: 90
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('95', $exception->getMessage());
        $this->assertSame(2005, $exception->getCode());
    }

    public function test_prohibited_jurisdiction_factory(): void
    {
        $exception = RiskAssessmentFailedException::prohibitedJurisdiction(
            partyId: 'party-123',
            countryCode: 'KP'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('KP', $exception->getMessage());
        $this->assertStringContainsString('prohibited', $exception->getMessage());
        $this->assertSame(2006, $exception->getCode());
    }

    public function test_timeout_factory(): void
    {
        $exception = RiskAssessmentFailedException::timeout(
            partyId: 'party-123',
            durationSeconds: 30.5
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('30.50', $exception->getMessage());
        $this->assertSame(2007, $exception->getCode());
    }
}
