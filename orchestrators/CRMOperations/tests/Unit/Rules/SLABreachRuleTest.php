<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\Rules;

use Nexus\CRMOperations\Rules\SLABreachRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SLABreachRule
 * 
 * @package Nexus\CRMOperations\Tests\Unit\Rules
 */
final class SLABreachRuleTest extends TestCase
{
    public function testDefaultSLAThresholdsAreSet(): void
    {
        $rule = new SLABreachRule();
        $thresholds = $rule->getThresholds();
        
        $this->assertEquals(24, $thresholds['lead_first_contact']);
        $this->assertEquals(72, $thresholds['lead_qualification']);
        $this->assertEquals(48, $thresholds['opportunity_follow_up']);
        $this->assertEquals(168, $thresholds['opportunity_proposal']);
    }

    public function testCustomThresholdsCanBeProvided(): void
    {
        $rule = new SLABreachRule([
            'lead_first_contact' => 12,
            'lead_qualification' => 48,
        ]);
        
        $thresholds = $rule->getThresholds();
        
        $this->assertEquals(12, $thresholds['lead_first_contact']);
        $this->assertEquals(48, $thresholds['lead_qualification']);
    }

    public function testEvaluateLeadMethodExists(): void
    {
        $rule = new SLABreachRule();
        $this->assertTrue(method_exists($rule, 'evaluateLead'));
    }

    public function testEvaluateOpportunityMethodExists(): void
    {
        $rule = new SLABreachRule();
        $this->assertTrue(method_exists($rule, 'evaluateOpportunity'));
    }

    public function testHasLeadBreachMethodExists(): void
    {
        $rule = new SLABreachRule();
        $this->assertTrue(method_exists($rule, 'hasLeadBreach'));
    }

    public function testHasOpportunityBreachMethodExists(): void
    {
        $rule = new SLABreachRule();
        $this->assertTrue(method_exists($rule, 'hasOpportunityBreach'));
    }
}
