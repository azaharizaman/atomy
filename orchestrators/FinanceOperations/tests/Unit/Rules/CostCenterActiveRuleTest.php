<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\CostCenterQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\CostCenterActiveRuleContext;
use Nexus\FinanceOperations\Rules\CostCenterActiveRule;
use PHPUnit\Framework\TestCase;

final class CostCenterActiveRuleTest extends TestCase
{
    public function testAllCostCentersActivePassesValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }

                    public function canReceiveAllocations(): bool
                    {
                        return true;
                    }

                    public function getName(): string
                    {
                        return 'Cost Center 1';
                    }
                };
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: ['cc-001', 'cc-002'],
        ));

        $this->assertTrue($result->passed);
        $this->assertEquals('cost_center_active', $result->ruleName);
    }

    public function testEmptyCostCenterListPassesValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: [],
        ));

        $this->assertTrue($result->passed);
    }

    public function testCostCenterNotFoundFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: ['non-existent'],
        ));

        $this->assertFalse($result->passed);
        $this->assertEquals('not_found', $result->violations[0]['type']);
    }

    public function testInactiveCostCenterFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return false;
                    }

                    public function canReceiveAllocations(): bool
                    {
                        return true;
                    }

                    public function getName(): string
                    {
                        return 'Inactive CC';
                    }
                };
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: ['cc-001'],
        ));

        $this->assertFalse($result->passed);
        $this->assertEquals('inactive', $result->violations[0]['type']);
    }

    public function testCannotReceiveAllocationsFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }

                    public function canReceiveAllocations(): bool
                    {
                        return false;
                    }

                    public function getName(): string
                    {
                        return 'Restricted CC';
                    }
                };
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: ['cc-001'],
        ));

        $this->assertFalse($result->passed);
        $this->assertEquals('cannot_receive', $result->violations[0]['type']);
    }

    public function testMultipleCostCentersWithViolations(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            private int $callCount = 0;

            public function find(string $tenantId, string $costCenterId): ?object
            {
                $this->callCount++;

                if ($this->callCount === 1) {
                    return new class {
                        public function isActive(): bool
                        {
                            return false;
                        }

                        public function canReceiveAllocations(): bool
                        {
                            return true;
                        }

                        public function getName(): string
                        {
                            return 'Inactive CC';
                        }
                    };
                }

                return null;
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: ['cc-001', 'cc-002'],
        ));

        $this->assertFalse($result->passed);
        $this->assertCount(2, $result->violations);
    }

    public function testMissingTenantIdFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: '  ',
            costCenterIds: ['cc-001'],
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['missing_field' => 'tenantId'], $result->violations);
    }

    public function testBlankCostCenterIdsAreIgnored(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new CostCenterActiveRuleContext(
            tenantId: 'tenant-001',
            costCenterIds: [' ', "\t", ''],
        ));

        $this->assertTrue($result->passed);
    }

    public function testGetNameReturnsCostCenterActive(): void
    {
        $rule = new CostCenterActiveRule(new class implements CostCenterQueryInterface {
            public function find(string $tenantId, string $costCenterId): ?object
            {
                return null;
            }
        });

        $this->assertEquals('cost_center_active', $rule->getName());
    }
}
