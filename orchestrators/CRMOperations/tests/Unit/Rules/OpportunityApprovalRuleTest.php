<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\Rules;

use Nexus\CRMOperations\Rules\OpportunityApprovalRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OpportunityApprovalRule
 * 
 * @package Nexus\CRMOperations\Tests\Unit\Rules
 */
final class OpportunityApprovalRuleTest extends TestCase
{
    public function testDefaultThresholdsAreSet(): void
    {
        $rule = new OpportunityApprovalRule();
        $thresholds = $rule->getThresholds();
        
        $this->assertEquals(500000, $thresholds['requires_approval_value']);
        $this->assertEquals(20, $thresholds['requires_approval_discount']);
        $this->assertEquals(1000000, $thresholds['manager_approval_value']);
        $this->assertEquals(5000000, $thresholds['director_approval_value']);
    }

    public function testCustomThresholdsCanBeProvided(): void
    {
        $rule = new OpportunityApprovalRule([
            'requires_approval_value' => 1000000,
            'requires_approval_discount' => 15,
        ]);
        
        $thresholds = $rule->getThresholds();
        
        $this->assertEquals(1000000, $thresholds['requires_approval_value']);
        $this->assertEquals(15, $thresholds['requires_approval_discount']);
    }

    public function testEvaluateMethodExists(): void
    {
        $rule = new OpportunityApprovalRule();
        $this->assertTrue(method_exists($rule, 'evaluate'));
    }

    public function testCanCloseWithoutApprovalMethodExists(): void
    {
        $rule = new OpportunityApprovalRule();
        $this->assertTrue(method_exists($rule, 'canCloseWithoutApproval'));
    }

    public function testGetRequiredApprovalLevelMethodExists(): void
    {
        $rule = new OpportunityApprovalRule();
        $this->assertTrue(method_exists($rule, 'getRequiredApprovalLevel'));
    }
}
