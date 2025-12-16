<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\Exceptions\HighRiskPartyException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HighRiskPartyException::class)]
final class HighRiskPartyExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-001',
            riskLevel: RiskLevel::HIGH,
            riskScore: 75
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_party_id(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-002',
            riskLevel: RiskLevel::VERY_HIGH,
            riskScore: 85
        );

        $this->assertSame('PARTY-002', $exception->getPartyId());
    }

    #[Test]
    public function it_stores_risk_level(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-003',
            riskLevel: RiskLevel::PROHIBITED,
            riskScore: 100
        );

        $this->assertSame(RiskLevel::PROHIBITED, $exception->getRiskLevel());
    }

    #[Test]
    public function it_stores_risk_score(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-004',
            riskLevel: RiskLevel::HIGH,
            riskScore: 78
        );

        $this->assertSame(78, $exception->getRiskScore());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-005',
            riskLevel: RiskLevel::VERY_HIGH,
            riskScore: 88
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('PARTY-005', $message);
        $this->assertStringContainsString('88', $message);
    }

    #[Test]
    public function it_stores_risk_factors(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-006',
            riskLevel: RiskLevel::HIGH,
            riskScore: 70,
            riskFactors: ['pep', 'high_risk_country', 'cash_intensive']
        );

        $this->assertSame(['pep', 'high_risk_country', 'cash_intensive'], $exception->getRiskFactors());
    }

    #[Test]
    public function it_returns_empty_array_when_no_risk_factors(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-007',
            riskLevel: RiskLevel::HIGH,
            riskScore: 65
        );

        $this->assertSame([], $exception->getRiskFactors());
    }

    #[Test]
    public function it_includes_risk_factors_in_message(): void
    {
        $exception = new HighRiskPartyException(
            partyId: 'PARTY-008',
            riskLevel: RiskLevel::VERY_HIGH,
            riskScore: 90,
            riskFactors: ['sanctions_match', 'pep']
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('sanctions_match', $message);
        $this->assertStringContainsString('pep', $message);
    }
}
