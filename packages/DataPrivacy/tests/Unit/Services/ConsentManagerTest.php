<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\ConsentPersistInterface;
use Nexus\DataPrivacy\Contracts\ConsentQueryInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;
use Nexus\DataPrivacy\Exceptions\ConsentNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidConsentException;
use Nexus\DataPrivacy\Services\ConsentManager;
use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConsentManager::class)]
final class ConsentManagerTest extends TestCase
{
    private ConsentQueryInterface&MockObject $consentQuery;
    private ConsentPersistInterface&MockObject $consentPersist;
    private AuditLoggerInterface&MockObject $auditLogger;
    private ConsentManager $manager;

    protected function setUp(): void
    {
        $this->consentQuery = $this->createMock(ConsentQueryInterface::class);
        $this->consentPersist = $this->createMock(ConsentPersistInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->manager = new ConsentManager(
            $this->consentQuery,
            $this->consentPersist,
            $this->auditLogger
        );
    }

    public function testGrantConsentSucceedsWithNoExistingConsent(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $this->consentQuery
            ->expects($this->once())
            ->method('findByDataSubjectAndPurpose')
            ->with('subject-123', $purpose)
            ->willReturn(null);

        $this->consentPersist
            ->expects($this->once())
            ->method('save')
            ->willReturn('consent-id-123');

        $consent = $this->manager->grantConsent($dataSubjectId, $purpose);

        $this->assertInstanceOf(Consent::class, $consent);
        $this->assertSame($purpose, $consent->purpose);
    }

    public function testGrantConsentThrowsWhenValidConsentExists(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $existingConsent = new Consent(
            id: 'consent-existing',
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-1 day'),
        );

        $this->consentQuery
            ->expects($this->once())
            ->method('findByDataSubjectAndPurpose')
            ->willReturn($existingConsent);

        $this->expectException(InvalidConsentException::class);
        $this->expectExceptionMessage('Valid consent already exists');

        $this->manager->grantConsent($dataSubjectId, $purpose);
    }

    public function testWithdrawConsentSucceeds(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $existingConsent = new Consent(
            id: 'consent-123',
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-30 days'),
        );

        $this->consentQuery
            ->expects($this->once())
            ->method('findByDataSubjectAndPurpose')
            ->willReturn($existingConsent);

        $this->consentPersist
            ->expects($this->once())
            ->method('update');

        $consent = $this->manager->withdrawConsent($dataSubjectId, $purpose);

        $this->assertSame(ConsentStatus::WITHDRAWN, $consent->status);
    }

    public function testWithdrawConsentThrowsWhenNotFound(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $this->consentQuery
            ->expects($this->once())
            ->method('findByDataSubjectAndPurpose')
            ->willReturn(null);

        $this->expectException(ConsentNotFoundException::class);

        $this->manager->withdrawConsent($dataSubjectId, $purpose);
    }

    public function testWithdrawConsentThrowsWhenAlreadyWithdrawn(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $existingConsent = new Consent(
            id: 'consent-123',
            dataSubjectId: $dataSubjectId,
            purpose: $purpose,
            status: ConsentStatus::WITHDRAWN,
            grantedAt: new DateTimeImmutable('-30 days'),
            withdrawnAt: new DateTimeImmutable('-1 day'),
        );

        $this->consentQuery
            ->expects($this->once())
            ->method('findByDataSubjectAndPurpose')
            ->willReturn($existingConsent);

        $this->expectException(InvalidConsentException::class);
        $this->expectExceptionMessage('already withdrawn');

        $this->manager->withdrawConsent($dataSubjectId, $purpose);
    }

    public function testHasValidConsentReturnsTrueWhenValidConsentExists(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $this->consentQuery
            ->expects($this->once())
            ->method('hasValidConsent')
            ->with('subject-123', $purpose)
            ->willReturn(true);

        $this->assertTrue($this->manager->hasValidConsent($dataSubjectId, $purpose));
    }

    public function testHasValidConsentReturnsFalseWhenNoConsent(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::MARKETING_EMAIL;

        $this->consentQuery
            ->expects($this->once())
            ->method('hasValidConsent')
            ->with('subject-123', $purpose)
            ->willReturn(false);

        $this->assertFalse($this->manager->hasValidConsent($dataSubjectId, $purpose));
    }

    public function testManagerWorksWithoutAuditLogger(): void
    {
        $managerWithoutLogger = new ConsentManager(
            $this->consentQuery,
            $this->consentPersist,
            null
        );

        $dataSubjectId = new DataSubjectId('subject-123');
        $purpose = ConsentPurpose::ANALYTICS;

        $this->consentQuery
            ->method('findByDataSubjectAndPurpose')
            ->willReturn(null);

        $this->consentPersist
            ->method('save')
            ->willReturn('consent-id');

        // Should not throw even without audit logger
        $consent = $managerWithoutLogger->grantConsent($dataSubjectId, $purpose);
        $this->assertInstanceOf(Consent::class, $consent);
    }
}
