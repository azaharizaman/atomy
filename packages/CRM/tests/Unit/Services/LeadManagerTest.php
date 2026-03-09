<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Services;

use Nexus\CRM\Enums\LeadSource;
use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Exceptions\CRMException;
use Nexus\CRM\Exceptions\InvalidLeadStatusTransitionException;
use Nexus\CRM\Exceptions\LeadNotConvertibleException;
use Nexus\CRM\Services\LeadManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadManagerTest extends TestCase
{
    #[Test]
    public function it_applies_updates_to_lead_fields(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Original lead',
            source: LeadSource::Website,
            description: 'Original description',
            estimatedValue: 10_000,
            currency: 'USD',
            externalRef: 'ext-1',
        );

        $updated = $manager->update(
            id: $lead->getId(),
            title: 'Updated lead',
            description: 'Updated description',
            estimatedValue: 25_000,
            currency: 'EUR',
        );

        $this->assertSame('Updated lead', $updated->getTitle());
        $this->assertSame('Updated description', $updated->getDescription());
        $this->assertSame(25_000, $updated->getEstimatedValue());
        $this->assertSame('EUR', $updated->getCurrency());
    }

    #[Test]
    public function it_enforces_lead_status_transition_rules(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Transition lead',
            source: LeadSource::Website,
        );

        $manager->updateStatus($lead->getId(), LeadStatus::Contacted);
        $this->expectException(InvalidLeadStatusTransitionException::class);
        $manager->updateStatus($lead->getId(), LeadStatus::Converted);
    }

    #[Test]
    public function it_assigns_score_and_surfaces_high_scoring_leads(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Scored lead',
            source: LeadSource::Referral,
        );

        $manager->assignScore($lead->getId(), 88, ['engagement' => 80]);
        $highScoring = iterator_to_array($manager->findHighScoring(80));

        $this->assertCount(1, $highScoring);
        $this->assertSame(88, $highScoring[0]->getScore()?->getValue());
    }

    #[Test]
    public function it_converts_qualified_lead_and_tracks_conversion_metadata(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Qualified lead',
            source: LeadSource::Partner,
        );

        $manager->updateStatus($lead->getId(), LeadStatus::Contacted);
        $manager->updateStatus($lead->getId(), LeadStatus::Qualified);

        $opportunityId = $manager->convertToOpportunity($lead->getId());
        $convertedLead = $manager->findByIdOrFail($lead->getId());

        $this->assertTrue(str_starts_with($opportunityId, 'opp_'));
        $this->assertSame(LeadStatus::Converted, $convertedLead->getStatus());
        $this->assertNotNull($convertedLead->getConvertedAt());
        $this->assertSame($opportunityId, $convertedLead->getConvertedToOpportunityId());
    }

    #[Test]
    public function it_rejects_conversion_for_non_qualified_lead(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Unqualified lead',
            source: LeadSource::ColdOutreach,
        );

        $this->expectException(LeadNotConvertibleException::class);
        $manager->convertToOpportunity($lead->getId());
    }

    #[Test]
    public function it_soft_deletes_and_restores_lead(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Delete me',
            source: LeadSource::TradeShow,
        );

        $manager->delete($lead->getId());
        $this->assertNull($manager->findById($lead->getId()));

        $restoredLead = $manager->restore($lead->getId());
        $this->assertSame($lead->getId(), $restoredLead->getId());
        $this->assertNotNull($manager->findById($lead->getId()));
    }

    #[Test]
    public function it_returns_entities_that_cannot_be_mutated_externally(): void
    {
        $manager = new LeadManager();
        $lead = $manager->create(
            tenantId: 'tenant-1',
            title: 'Immutable entity',
            source: LeadSource::Website,
        );

        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        $lead->title = 'Tampered title';
    }

    #[Test]
    public function it_rejects_creating_leads_for_multiple_tenants_in_one_instance(): void
    {
        $manager = new LeadManager();
        $manager->create(
            tenantId: 'tenant-1',
            title: 'Tenant one lead',
            source: LeadSource::Website,
        );

        $this->expectException(CRMException::class);
        $manager->create(
            tenantId: 'tenant-2',
            title: 'Tenant two lead',
            source: LeadSource::Website,
        );
    }
}
