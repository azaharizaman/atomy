<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Enums;

use Nexus\CRM\Enums\LeadSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadSourceTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $cases = LeadSource::cases();

        $this->assertCount(11, $cases);
        $this->assertContains(LeadSource::Website, $cases);
        $this->assertContains(LeadSource::Referral, $cases);
        $this->assertContains(LeadSource::ColdOutreach, $cases);
        $this->assertContains(LeadSource::TradeShow, $cases);
        $this->assertContains(LeadSource::SocialMedia, $cases);
        $this->assertContains(LeadSource::EmailCampaign, $cases);
        $this->assertContains(LeadSource::PaidAds, $cases);
        $this->assertContains(LeadSource::OrganicSearch, $cases);
        $this->assertContains(LeadSource::DirectInquiry, $cases);
        $this->assertContains(LeadSource::Partner, $cases);
        $this->assertContains(LeadSource::Other, $cases);
    }

    #[Test]
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('website', LeadSource::Website->value);
        $this->assertSame('referral', LeadSource::Referral->value);
        $this->assertSame('cold_outreach', LeadSource::ColdOutreach->value);
        $this->assertSame('trade_show', LeadSource::TradeShow->value);
        $this->assertSame('social_media', LeadSource::SocialMedia->value);
        $this->assertSame('email_campaign', LeadSource::EmailCampaign->value);
        $this->assertSame('paid_ads', LeadSource::PaidAds->value);
        $this->assertSame('organic_search', LeadSource::OrganicSearch->value);
        $this->assertSame('direct_inquiry', LeadSource::DirectInquiry->value);
        $this->assertSame('partner', LeadSource::Partner->value);
        $this->assertSame('other', LeadSource::Other->value);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Website', LeadSource::Website->label());
        $this->assertSame('Referral', LeadSource::Referral->label());
        $this->assertSame('Cold Outreach', LeadSource::ColdOutreach->label());
        $this->assertSame('Trade Show', LeadSource::TradeShow->label());
        $this->assertSame('Social Media', LeadSource::SocialMedia->label());
        $this->assertSame('Email Campaign', LeadSource::EmailCampaign->label());
        $this->assertSame('Paid Advertising', LeadSource::PaidAds->label());
        $this->assertSame('Organic Search', LeadSource::OrganicSearch->label());
        $this->assertSame('Direct Inquiry', LeadSource::DirectInquiry->label());
        $this->assertSame('Partner', LeadSource::Partner->label());
        $this->assertSame('Other', LeadSource::Other->label());
    }

    #[Test]
    #[DataProvider('inboundSourceProvider')]
    public function it_identifies_inbound_sources_correctly(LeadSource $source, bool $expectedIsInbound): void
    {
        $this->assertSame($expectedIsInbound, $source->isInbound());
    }

    public static function inboundSourceProvider(): array
    {
        return [
            'website is inbound' => [LeadSource::Website, true],
            'referral is inbound' => [LeadSource::Referral, true],
            'social media is inbound' => [LeadSource::SocialMedia, true],
            'organic search is inbound' => [LeadSource::OrganicSearch, true],
            'direct inquiry is inbound' => [LeadSource::DirectInquiry, true],
            'partner is inbound' => [LeadSource::Partner, true],
            'cold outreach is not inbound' => [LeadSource::ColdOutreach, false],
            'trade show is not inbound' => [LeadSource::TradeShow, false],
            'email campaign is not inbound' => [LeadSource::EmailCampaign, false],
            'paid ads is not inbound' => [LeadSource::PaidAds, false],
            'other is not inbound' => [LeadSource::Other, false],
        ];
    }

    #[Test]
    #[DataProvider('outboundSourceProvider')]
    public function it_identifies_outbound_sources_correctly(LeadSource $source, bool $expectedIsOutbound): void
    {
        $this->assertSame($expectedIsOutbound, $source->isOutbound());
    }

    public static function outboundSourceProvider(): array
    {
        return [
            'cold outreach is outbound' => [LeadSource::ColdOutreach, true],
            'email campaign is outbound' => [LeadSource::EmailCampaign, true],
            'paid ads is outbound' => [LeadSource::PaidAds, true],
            'website is not outbound' => [LeadSource::Website, false],
            'referral is not outbound' => [LeadSource::Referral, false],
            'trade show is not outbound' => [LeadSource::TradeShow, false],
            'social media is not outbound' => [LeadSource::SocialMedia, false],
            'organic search is not outbound' => [LeadSource::OrganicSearch, false],
            'direct inquiry is not outbound' => [LeadSource::DirectInquiry, false],
            'partner is not outbound' => [LeadSource::Partner, false],
            'other is not outbound' => [LeadSource::Other, false],
        ];
    }

    #[Test]
    #[DataProvider('paidSourceProvider')]
    public function it_identifies_paid_sources_correctly(LeadSource $source, bool $expectedIsPaid): void
    {
        $this->assertSame($expectedIsPaid, $source->isPaid());
    }

    public static function paidSourceProvider(): array
    {
        return [
            'paid ads is paid' => [LeadSource::PaidAds, true],
            'trade show is paid' => [LeadSource::TradeShow, true],
            'website is not paid' => [LeadSource::Website, false],
            'referral is not paid' => [LeadSource::Referral, false],
            'cold outreach is not paid' => [LeadSource::ColdOutreach, false],
            'social media is not paid' => [LeadSource::SocialMedia, false],
            'email campaign is not paid' => [LeadSource::EmailCampaign, false],
            'organic search is not paid' => [LeadSource::OrganicSearch, false],
            'direct inquiry is not paid' => [LeadSource::DirectInquiry, false],
            'partner is not paid' => [LeadSource::Partner, false],
            'other is not paid' => [LeadSource::Other, false],
        ];
    }

    #[Test]
    #[DataProvider('categoryProvider')]
    public function it_returns_correct_categories(LeadSource $source, string $expectedCategory): void
    {
        $this->assertSame($expectedCategory, $source->getCategory());
    }

    public static function categoryProvider(): array
    {
        return [
            'website is organic' => [LeadSource::Website, 'Organic'],
            'organic search is organic' => [LeadSource::OrganicSearch, 'Organic'],
            'direct inquiry is organic' => [LeadSource::DirectInquiry, 'Organic'],
            'referral is relationship' => [LeadSource::Referral, 'Relationship'],
            'partner is relationship' => [LeadSource::Partner, 'Relationship'],
            'cold outreach is outbound' => [LeadSource::ColdOutreach, 'Outbound'],
            'email campaign is outbound' => [LeadSource::EmailCampaign, 'Outbound'],
            'paid ads is paid' => [LeadSource::PaidAds, 'Paid'],
            'trade show is paid' => [LeadSource::TradeShow, 'Paid'],
            'social media is social' => [LeadSource::SocialMedia, 'Social'],
            'other is uncategorized' => [LeadSource::Other, 'Uncategorized'],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $source = LeadSource::from('referral');

        $this->assertSame(LeadSource::Referral, $source);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(\ValueError::class);

        LeadSource::from('invalid_source');
    }

    #[Test]
    public function it_can_try_from_string_safely(): void
    {
        $source = LeadSource::tryFrom('cold_outreach');

        $this->assertSame(LeadSource::ColdOutreach, $source);
    }

    #[Test]
    public function it_returns_null_for_invalid_try_from(): void
    {
        $source = LeadSource::tryFrom('invalid_source');

        $this->assertNull($source);
    }

    #[Test]
    public function inbound_and_outbound_are_mutually_exclusive(): void
    {
        foreach (LeadSource::cases() as $source) {
            // A source cannot be both inbound and outbound
            $this->assertFalse(
                $source->isInbound() && $source->isOutbound(),
                sprintf('%s should not be both inbound and outbound', $source->name)
            );
        }
    }
}
