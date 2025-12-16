<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use Nexus\KycVerification\Exceptions\BeneficialOwnershipException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeneficialOwnershipException::class)]
final class BeneficialOwnershipExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new BeneficialOwnershipException(
            partyId: 'PARTY-001',
            validationErrors: ['Incomplete ownership structure']
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_party_id(): void
    {
        $exception = new BeneficialOwnershipException(
            partyId: 'PARTY-002',
            validationErrors: ['Test error']
        );

        $this->assertSame('PARTY-002', $exception->getPartyId());
    }

    #[Test]
    public function it_stores_validation_errors(): void
    {
        $exception = new BeneficialOwnershipException(
            partyId: 'PARTY-003',
            validationErrors: ['Error 1', 'Error 2', 'Error 3']
        );

        $this->assertSame(['Error 1', 'Error 2', 'Error 3'], $exception->getValidationErrors());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new BeneficialOwnershipException(
            partyId: 'PARTY-004',
            validationErrors: ['Circular ownership detected']
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('PARTY-004', $message);
        $this->assertStringContainsString('Circular ownership detected', $message);
    }

    #[Test]
    public function missing_declaration_factory_creates_exception(): void
    {
        $exception = BeneficialOwnershipException::missingDeclaration('PARTY-005');

        $this->assertSame('PARTY-005', $exception->getPartyId());
        $this->assertStringContainsString('UBO declaration is missing', $exception->getValidationErrors()[0]);
    }

    #[Test]
    public function incomplete_ownership_chain_factory_creates_exception(): void
    {
        $exception = BeneficialOwnershipException::incompleteOwnershipChain(
            partyId: 'PARTY-006',
            identifiedPercentage: 75.5
        );

        $this->assertSame('PARTY-006', $exception->getPartyId());
        $this->assertStringContainsString('75.50%', $exception->getValidationErrors()[0]);
    }

    #[Test]
    public function unverified_ubo_factory_creates_exception(): void
    {
        $exception = BeneficialOwnershipException::unverifiedUbo(
            partyId: 'PARTY-007',
            uboName: 'John Doe'
        );

        $this->assertSame('PARTY-007', $exception->getPartyId());
        $this->assertStringContainsString('John Doe', $exception->getValidationErrors()[0]);
    }

    #[Test]
    public function circular_ownership_factory_creates_exception(): void
    {
        $exception = BeneficialOwnershipException::circularOwnership('PARTY-008');

        $this->assertSame('PARTY-008', $exception->getPartyId());
        $this->assertStringContainsString('Circular ownership', $exception->getValidationErrors()[0]);
    }
}
