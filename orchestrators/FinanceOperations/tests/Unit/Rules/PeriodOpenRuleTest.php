<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\PeriodOpenRule;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Unit tests for PeriodOpenRule.
 *
 * Tests cover:
 * - Period is open (pass)
 * - Period is closed (fail)
 * - Period is not found (fail)
 * - Period ID is missing (fail)
 * - Different context object property formats (tenantId, tenant_id, getTenantId)
 * - Different period object property formats (isOpen, is_open, getIsOpen, status)
 *
 * @since 1.0.0
 */
final class PeriodOpenRuleTest extends TestCase
{
    private PeriodOpenRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a simple period manager mock using anonymous class
        $this->rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): ?object {
                return null; // Default to null for tests
            }
        });
    }

    // =========================================================================
    // Test Suite: Period is Open - Pass Cases
    // =========================================================================

    /**
     * Test period is open using isOpen() method.
     */
    public function testPeriodIsOpenUsingIsOpenMethod(): void
    {
        // Create rule with mock that returns open period
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public function isOpen(): bool {
                        return true;
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('period_open', $result->ruleName);
    }

    /**
     * Test period is open using getIsOpen() method.
     */
    public function testPeriodIsOpenUsingGetIsOpenMethod(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public function getIsOpen(): bool {
                        return true;
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test period is open using isOpen property.
     */
    public function testPeriodIsOpenUsingIsOpenProperty(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public bool $isOpen = true;
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test period is open using is_open property.
     */
    public function testPeriodIsOpenUsingIsOpenPropertyUnderscore(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public bool $is_open = true;
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test period is open using status property 'open'.
     */
    public function testPeriodIsOpenUsingStatusPropertyOpen(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public string $status = 'open';
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test period is open using status property 'OPEN' (case insensitive).
     */
    public function testPeriodIsOpenUsingStatusPropertyUppercase(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public string $status = 'OPEN';
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // Test Suite: Period is Closed - Fail Cases
    // =========================================================================

    /**
     * Test period is closed fails validation.
     */
    public function testPeriodIsClosedFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public function isOpen(): bool {
                        return false;
                    }
                    public function getStatus(): string {
                        return 'closed';
                    }
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2025-12';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not open', $result->message);
    }

    /**
     * Test period status 'Closed' fails validation.
     */
    public function testPeriodStatusClosedFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public string $status = 'Closed';
                };
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2025-12';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
    }

    // =========================================================================
    // Test Suite: Period Not Found - Fail Cases
    // =========================================================================

    /**
     * Test period not found fails validation.
     */
    public function testPeriodNotFoundFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): ?object {
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = 'non-existent';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not found', $result->message);
    }

    // =========================================================================
    // Test Suite: Missing Period ID - Fail Cases
    // =========================================================================

    /**
     * Test missing period ID fails validation.
     */
    public function testMissingPeriodIdFailsValidation(): void
    {
        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '';
        };

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    /**
     * Test no period ID property fails validation.
     */
    public function testNoPeriodIdPropertyFailsValidation(): void
    {
        $context = new class {
            public string $tenantId = 'tenant-001';
        };

        $result = $this->rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    // =========================================================================
    // Test Suite: Context Property Format Variations
    // =========================================================================

    /**
     * Test context with tenant_id property (underscore format).
     */
    public function testContextWithTenantIdUnderscoreFormat(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public function isOpen(): bool {
                        return true;
                    }
                };
            }
        });

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public string $period_id = '2026-01';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with getter methods.
     */
    public function testContextWithGetterMethods(): void
    {
        $rule = new PeriodOpenRule(new class {
            public function getPeriod(string $tenantId, string $periodId): object {
                return new class {
                    public function isOpen(): bool {
                        return true;
                    }
                };
            }
        });

        $context = new class {
            public function getTenantId(): string {
                return 'tenant-001';
            }
            public function getPeriodId(): string {
                return '2026-01';
            }
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
    public function testGetNameReturnsPeriodOpen(): void
    {
        $this->assertEquals('period_open', $this->rule->getName());
    }
}
