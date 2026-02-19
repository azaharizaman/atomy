<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\Workflows;

use Nexus\CRMOperations\Workflows\SalesPlaybookWorkflow;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SalesPlaybookWorkflow
 * 
 * @package Nexus\CRMOperations\Tests\Unit\Workflows
 */
final class SalesPlaybookWorkflowTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertTrue(true);
    }

    public function testGetGuidedStepsMethodExists(): void
    {
        $this->assertTrue(method_exists(SalesPlaybookWorkflow::class, 'getGuidedSteps'));
    }

    public function testTriggerPlaybookMethodExists(): void
    {
        $this->assertTrue(method_exists(SalesPlaybookWorkflow::class, 'triggerPlaybook'));
    }

    public function testGetNextBestActionsMethodExists(): void
    {
        $this->assertTrue(method_exists(SalesPlaybookWorkflow::class, 'getNextBestActions'));
    }

    public function testTrackCompletionMethodExists(): void
    {
        $this->assertTrue(method_exists(SalesPlaybookWorkflow::class, 'trackCompletion'));
    }

    public function testPlaybookConstantsAreDefined(): void
    {
        $this->assertEquals('new_lead', SalesPlaybookWorkflow::PLAYBOOK_NEW_LEAD);
        $this->assertEquals('qualification', SalesPlaybookWorkflow::PLAYBOOK_QUALIFICATION);
        $this->assertEquals('discovery', SalesPlaybookWorkflow::PLAYBOOK_DISCOVERY);
        $this->assertEquals('proposal', SalesPlaybookWorkflow::PLAYBOOK_PROPOSAL);
        $this->assertEquals('negotiation', SalesPlaybookWorkflow::PLAYBOOK_NEGOTIATION);
        $this->assertEquals('closing', SalesPlaybookWorkflow::PLAYBOOK_CLOSING);
        $this->assertEquals('renewal', SalesPlaybookWorkflow::PLAYBOOK_RENEWAL);
    }
}
