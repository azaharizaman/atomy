<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;
use Nexus\DataPrivacy\Exceptions\InvalidConsentException;
use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Consent::class)]
final class ConsentTest extends TestCase
{
    private DataSubjectId $dataSubjectId;

    protected function setUp(): void
    {
        $this->dataSubjectId = new DataSubjectId('subject-123');
    }

    public function testConstructorWithValidData(): void
    {
        $grantedAt = new DateTimeImmutable('2024-01-01');

        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: $grantedAt,
        );

        $this->assertSame('consent-123', $consent->id);
        $this->assertSame($this->dataSubjectId, $consent->dataSubjectId);
        $this->assertSame(ConsentPurpose::MARKETING_EMAIL, $consent->purpose);
        $this->assertSame(ConsentStatus::GRANTED, $consent->status);
    }

    public function testConstructorThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidConsentException::class);
        $this->expectExceptionMessage('Consent ID cannot be empty');

        new Consent(
            id: '',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable(),
        );
    }

    public function testConstructorThrowsWhenExpiryBeforeGrant(): void
    {
        $this->expectException(InvalidConsentException::class);
        $this->expectExceptionMessage('Expiry date must be after grant date');

        new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('2024-02-01'),
            expiresAt: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function testIsValidReturnsTrueForGrantedConsent(): void
    {
        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-1 day'),
        );

        $this->assertTrue($consent->isValid());
    }

    public function testIsValidReturnsFalseForWithdrawnConsent(): void
    {
        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::WITHDRAWN,
            grantedAt: new DateTimeImmutable('-30 days'),
            withdrawnAt: new DateTimeImmutable('-1 day'),
        );

        $this->assertFalse($consent->isValid());
    }

    public function testIsValidReturnsFalseForExpiredConsent(): void
    {
        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-60 days'),
            expiresAt: new DateTimeImmutable('-30 days'),
        );

        $this->assertFalse($consent->isValid());
    }

    public function testIsExpiredReturnsTrueAfterExpiryDate(): void
    {
        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-60 days'),
            expiresAt: new DateTimeImmutable('-1 day'),
        );

        $this->assertTrue($consent->isExpired());
    }

    public function testIsExpiredReturnsFalseWithNoExpiry(): void
    {
        $consent = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-60 days'),
        );

        $this->assertFalse($consent->isExpired());
    }

    public function testWithdrawReturnsNewInstanceWithWithdrawnStatus(): void
    {
        $original = new Consent(
            id: 'consent-123',
            dataSubjectId: $this->dataSubjectId,
            purpose: ConsentPurpose::MARKETING_EMAIL,
            status: ConsentStatus::GRANTED,
            grantedAt: new DateTimeImmutable('-30 days'),
        );

        $withdrawnAt = new DateTimeImmutable();
        $withdrawn = $original->withdraw($withdrawnAt);

        // Original unchanged
        $this->assertSame(ConsentStatus::GRANTED, $original->status);

        // New instance is withdrawn
        $this->assertSame(ConsentStatus::WITHDRAWN, $withdrawn->status);
        $this->assertSame($withdrawnAt, $withdrawn->withdrawnAt);
        $this->assertSame($original->id, $withdrawn->id);
    }
}
