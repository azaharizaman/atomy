<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\AchReturnCode;
use PHPUnit\Framework\TestCase;

final class AchReturnCodeTest extends TestCase
{
    public function test_description_is_defined_for_all_codes(): void
    {
        foreach (AchReturnCode::cases() as $code) {
            self::assertNotSame('', $code->description());
        }
    }

    public function test_category_helpers_classify_common_scenarios(): void
    {
        self::assertTrue(AchReturnCode::R01->isAdministrative());
        self::assertFalse(AchReturnCode::R10->isAdministrative());

        self::assertTrue(AchReturnCode::R01->isInsufficientFunds());
        self::assertTrue(AchReturnCode::R09->isInsufficientFunds());
        self::assertFalse(AchReturnCode::R02->isInsufficientFunds());

        self::assertTrue(AchReturnCode::R05->isAuthorizationIssue());
        self::assertTrue(AchReturnCode::R29->isAuthorizationIssue());
        self::assertFalse(AchReturnCode::R04->isAuthorizationIssue());
    }

    public function test_requiresAccountUpdate_flags_account_information_issues(): void
    {
        self::assertTrue(AchReturnCode::R02->requiresAccountUpdate());
        self::assertTrue(AchReturnCode::R03->requiresAccountUpdate());
        self::assertTrue(AchReturnCode::R04->requiresAccountUpdate());
        self::assertTrue(AchReturnCode::R12->requiresAccountUpdate());
        self::assertFalse(AchReturnCode::R01->requiresAccountUpdate());
    }

    public function test_isRetriable_is_true_for_funds_related_returns_only(): void
    {
        self::assertTrue(AchReturnCode::R01->isRetriable());
        self::assertTrue(AchReturnCode::R09->isRetriable());
        self::assertFalse(AchReturnCode::R02->isRetriable());
    }

    public function test_suggestedAction_is_specific_for_known_codes_and_defaults_otherwise(): void
    {
        self::assertSame(
            'Retry payment after sufficient funds are available',
            AchReturnCode::R01->suggestedAction()
        );

        self::assertSame(
            'Contact customer for updated account information',
            AchReturnCode::R02->suggestedAction()
        );

        self::assertSame(
            'Verify and correct account/routing number',
            AchReturnCode::R28->suggestedAction()
        );

        self::assertSame(
            'Review and resolve based on specific circumstances',
            AchReturnCode::R85->suggestedAction()
        );
    }
}
