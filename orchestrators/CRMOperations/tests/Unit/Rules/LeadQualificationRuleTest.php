<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\Rules;

use Nexus\CRMOperations\Rules\LeadQualificationRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LeadQualificationRule
 * 
 * @package Nexus\CRMOperations\Tests\Unit\Rules
 */
final class LeadQualificationRuleTest extends TestCase
{
    public function testDefaultThresholdsAreSet(): void
    {
        $rule = new LeadQualificationRule();
        $requirements = $rule->getRequirements();
        
        $this->assertEquals(50, $requirements['minimum_score']);
        $this->assertEquals(2, $requirements['minimum_activities']);
        $this->assertEquals(90, $requirements['maximum_age_days']);
    }

    public function testCustomThresholdsCanBeProvided(): void
    {
        $rule = new LeadQualificationRule([
            'minimum_score' => 70,
            'minimum_activities' => 3,
            'maximum_age_days' => 30,
        ]);
        
        $requirements = $rule->getRequirements();
        
        $this->assertEquals(70, $requirements['minimum_score']);
        $this->assertEquals(3, $requirements['minimum_activities']);
        $this->assertEquals(30, $requirements['maximum_age_days']);
    }

    public function testCanQualifyMethodExists(): void
    {
        $rule = new LeadQualificationRule();
        $this->assertTrue(method_exists($rule, 'canQualify'));
    }

    public function testEvaluateMethodExists(): void
    {
        $rule = new LeadQualificationRule();
        $this->assertTrue(method_exists($rule, 'evaluate'));
    }
}
