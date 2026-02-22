<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\CostCenterActiveRule;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Unit tests for CostCenterActiveRule.
 *
 * Tests cover:
 * - All cost centers active (pass)
 * - Empty cost center list (pass)
 * - Cost center not found (fail)
 * - Cost center inactive (fail)
 * - Cost center cannot receive allocations (fail)
 * - Multiple cost centers with violations
 * - Different context property formats
 *
 * @since 1.0.0
 */
final class CostCenterActiveRuleTest extends TestCase
{
    // =========================================================================
    // Test Suite: Cost Centers Active - Pass Cases
    // =========================================================================

    /**
     * Test all cost centers active passes validation.
     */
    public function testAllCostCentersActivePassesValidation(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'Cost Center 1';
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['cc-001', 'cc-002'];
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('cost_center_active', $result->ruleName);
    }

    /**
     * Test empty cost center list passes validation.
     */
    public function testEmptyCostCenterListPassesValidation(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = [];
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // Test Suite: Cost Center Not Found - Fail Cases
    // =========================================================================

    /**
     * Test cost center not found fails validation.
     */
    public function testCostCenterNotFoundFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['non-existent'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        // Check violations array for the actual message
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('not_found', $result->violations[0]['type']);
    }

    // =========================================================================
    // Test Suite: Cost Center Inactive - Fail Cases
    // =========================================================================

    /**
     * Test inactive cost center fails validation.
     */
    public function testInactiveCostCenterFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return false;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'Inactive CC';
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['cc-001'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        // Check violations array for the actual message
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('inactive', $result->violations[0]['type']);
    }

    /**
     * Test inactive cost center using is_active property.
     */
    public function testInactiveCostCenterUsingIsActiveProperty(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public bool $is_active = false;
                    public bool $can_receive_allocations = true;
                    public string $name = 'CC';
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['cc-001'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
    }

    // =========================================================================
    // Test Suite: Cannot Receive Allocations - Fail Cases
    // =========================================================================

    /**
     * Test cost center cannot receive allocations fails validation.
     */
    public function testCannotReceiveAllocationsFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return false;
                    }
                    public function getName(): string {
                        return 'Restricted CC';
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['cc-001'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        // Check violations array for the actual message
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('cannot_receive', $result->violations[0]['type']);
    }

    // =========================================================================
    // Test Suite: Multiple Cost Centers
    // =========================================================================

    /**
     * Test multiple cost centers with violations.
     */
    public function testMultipleCostCentersWithViolations(): void
    {
        $rule = new CostCenterActiveRule(new class {
            private int $callCount = 0;
            public function find(string $tenantId, string $costCenterId): ?object {
                $this->callCount++;
                if ($this->callCount === 1) {
                    // First call - inactive
                    return new class {
                        public function isActive(): bool {
                            return false;
                        }
                        public function canReceiveAllocations(): bool {
                            return true;
                        }
                        public function getName(): string {
                            return 'Inactive CC';
                        }
                    };
                }
                // Second call - not found
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public array $costCenterIds = ['cc-001', 'cc-002'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertCount(2, $result->violations);
    }

    // =========================================================================
    // Test Suite: Context Property Format Variations
    // =========================================================================

    /**
     * Test context with underscore format properties.
     */
    public function testContextWithUnderscoreFormatProperties(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'CC';
                    }
                };
            }
        });

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public array $cost_center_ids = ['cc-001'];
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with getter methods.
     */
    public function testContextWithGetterMethods(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'CC';
                    }
                };
            }
        });

        $context = new class {
            public function getTenantId(): string {
                return 'tenant-001';
            }
            public function getCostCenterIds(): array {
                return ['cc-001'];
            }
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with single cost center ID.
     */
    public function testContextWithSingleCostCenterId(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'CC';
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $costCenterId = 'cc-001';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with single cost center ID using underscore format.
     */
    public function testContextWithSingleCostCenterIdUnderscore(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                    public function canReceiveAllocations(): bool {
                        return true;
                    }
                    public function getName(): string {
                        return 'CC';
                    }
                };
            }
        });

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public string $cost_center_id = 'cc-001';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // Test Suite: getName()
    // =========================================================================

    /**
     * Test getName returns correct rule name.
     */
    public function testGetNameReturnsCostCenterActive(): void
    {
        $rule = new CostCenterActiveRule(new class {
            public function find(string $tenantId, string $costCenterId): ?object {
                return null;
            }
        });

        $this->assertEquals('cost_center_active', $rule->getName());
    }
}
