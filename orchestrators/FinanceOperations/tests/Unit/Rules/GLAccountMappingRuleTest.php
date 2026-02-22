<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\GLAccountMappingRule;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Unit tests for GLAccountMappingRule.
 *
 * Tests cover:
 * - All mappings valid (pass)
 * - Missing subledger type (fail)
 * - Missing transaction types (fail)
 * - Missing mapping for transaction type (fail)
 * - Invalid GL account (fail)
 * - Inactive GL account (fail)
 * - Different context property formats
 *
 * @since 1.0.0
 */
final class GLAccountMappingRuleTest extends TestCase
{
    // =========================================================================
    // Test Suite: Valid Mappings - Pass Cases
    // =========================================================================

    /**
     * Test all mappings valid passes validation.
     */
    public function testAllMappingsValidPassesValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return new class {
                        public function isActive(): bool {
                            return true;
                        }
                    };
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [
                        new class {
                            public function getTransactionType(): string {
                                return 'INVOICE';
                            }
                            public function getGLAccountCode(): string {
                                return '4000';
                            }
                        }
                    ];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = 'AR';
            public array $transactionTypes = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('gl_account_mapping', $result->ruleName);
    }

    // =========================================================================
    // Test Suite: Missing Required Fields - Fail Cases
    // =========================================================================

    /**
     * Test missing subledger type fails validation.
     */
    public function testMissingSubledgerTypeFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return null;
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = '';
            public array $transactionTypes = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    /**
     * Test missing transaction types fails validation.
     */
    public function testMissingTransactionTypesFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return null;
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = 'AR';
            public array $transactionTypes = [];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    // =========================================================================
    // Test Suite: Missing Mapping - Fail Cases
    // =========================================================================

    /**
     * Test missing mapping for transaction type fails validation.
     */
    public function testMissingMappingFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return null;
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    // Return empty mappings
                    return [];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = 'AR';
            public array $transactionTypes = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('missing_mapping', $result->violations[0]['type']);
    }

    // =========================================================================
    // Test Suite: Invalid Account - Fail Cases
    // =========================================================================

    /**
     * Test invalid GL account fails validation.
     */
    public function testInvalidGLAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    // Return null - account doesn't exist
                    return null;
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [
                        new class {
                            public function getTransactionType(): string {
                                return 'INVOICE';
                            }
                            public function getAccountCode(): string {
                                return 'INVALID';
                            }
                        }
                    ];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = 'AR';
            public array $transactionTypes = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('invalid_account', $result->violations[0]['type']);
    }

    // =========================================================================
    // Test Suite: Inactive Account - Fail Cases
    // =========================================================================

    /**
     * Test inactive GL account fails validation.
     */
    public function testInactiveGLAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return new class {
                        public function isActive(): bool {
                            return false;
                        }
                    };
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [
                        new class {
                            public function getTransactionType(): string {
                                return 'INVOICE';
                            }
                            public function getGLAccountCode(): string {
                                return '4000';
                            }
                        }
                    ];
                }
            }
        );

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $subledgerType = 'AR';
            public array $transactionTypes = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->violations);
        $this->assertEquals('inactive_account', $result->violations[0]['type']);
    }

    // =========================================================================
    // Test Suite: Context Property Format Variations
    // =========================================================================

    /**
     * Test context with underscore format properties.
     */
    public function testContextWithUnderscoreFormatProperties(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return new class {
                        public function isActive(): bool {
                            return true;
                        }
                    };
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [
                        new class {
                            public string $transaction_type = 'INVOICE';
                            public string $account_code = '4000';
                        }
                    ];
                }
            }
        );

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public string $subledger_type = 'AR';
            public array $transaction_types = ['INVOICE'];
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with getter methods.
     */
    public function testContextWithGetterMethods(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return new class {
                        public function isActive(): bool {
                            return true;
                        }
                    };
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [
                        new class {
                            public function getTransactionType(): string {
                                return 'INVOICE';
                            }
                            public function getAccountCode(): string {
                                return '4000';
                            }
                        }
                    ];
                }
            }
        );

        $context = new class {
            public function getTenantId(): string {
                return 'tenant-001';
            }
            public function getSubledgerType(): string {
                return 'AR';
            }
            public function getTransactionTypes(): array {
                return ['INVOICE'];
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
    public function testGetNameReturnsGLAccountMapping(): void
    {
        $rule = new GLAccountMappingRule(
            new class {
                public function find(string $tenantId, string $accountCode): ?object {
                    return null;
                }
            },
            new class {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array {
                    return [];
                }
            }
        );

        $this->assertEquals('gl_account_mapping', $rule->getName());
    }
}
