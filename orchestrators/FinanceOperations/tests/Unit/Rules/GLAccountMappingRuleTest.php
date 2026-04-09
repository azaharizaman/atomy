<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\GLAccountQueryInterface;
use Nexus\FinanceOperations\Contracts\GLMappingRepositoryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\GLAccountMappingRuleContext;
use Nexus\FinanceOperations\Enums\SubledgerType;
use Nexus\FinanceOperations\Rules\GLAccountMappingRule;
use PHPUnit\Framework\TestCase;

final class GLAccountMappingRuleTest extends TestCase
{
    public function testAllMappingsValidPassesValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return new class {
                        public function isActive(): bool
                        {
                            return true;
                        }
                    };
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [
                        new class {
                            public function getTransactionType(): string
                            {
                                return 'INVOICE';
                            }

                            public function getGLAccountCode(): string
                            {
                                return '4000';
                            }
                        },
                    ];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: 'tenant-001',
            subledgerType: SubledgerType::AR,
            transactionTypes: ['INVOICE'],
        ));

        $this->assertTrue($result->passed);
        $this->assertSame('gl_account_mapping', $result->ruleName);
    }

    public function testMissingTransactionTypesFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return null;
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: 'tenant-001',
            subledgerType: SubledgerType::AR,
            transactionTypes: [],
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', (string) $result->message);
    }

    public function testMissingMappingFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return null;
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: 'tenant-001',
            subledgerType: SubledgerType::AR,
            transactionTypes: ['INVOICE'],
        ));

        $this->assertFalse($result->passed);
        $this->assertSame('missing_mapping', $result->violations[0]['type'] ?? null);
    }

    public function testInvalidGLAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return null;
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [
                        new class {
                            public function getTransactionType(): string
                            {
                                return 'INVOICE';
                            }

                            public function getAccountCode(): string
                            {
                                return 'INVALID';
                            }
                        },
                    ];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: 'tenant-001',
            subledgerType: SubledgerType::AR,
            transactionTypes: ['INVOICE'],
        ));

        $this->assertFalse($result->passed);
        $this->assertSame('invalid_account', $result->violations[0]['type'] ?? null);
    }

    public function testInactiveGLAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return new class {
                        public function isActive(): bool
                        {
                            return false;
                        }
                    };
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [
                        new class {
                            public function getTransactionType(): string
                            {
                                return 'INVOICE';
                            }

                            public function getGLAccountCode(): string
                            {
                                return '4000';
                            }
                        },
                    ];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: 'tenant-001',
            subledgerType: SubledgerType::AR,
            transactionTypes: ['INVOICE'],
        ));

        $this->assertFalse($result->passed);
        $this->assertSame('inactive_account', $result->violations[0]['type'] ?? null);
    }

    public function testMissingTenantIdFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return null;
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [];
                }
            }
        );

        $result = $rule->check(new GLAccountMappingRuleContext(
            tenantId: '',
            subledgerType: SubledgerType::AR,
            transactionTypes: ['INVOICE'],
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['missing_field' => 'tenantId'], $result->violations);
    }

    public function testGetNameReturnsGLAccountMapping(): void
    {
        $rule = new GLAccountMappingRule(
            new class implements GLAccountQueryInterface {
                public function find(string $tenantId, string $accountCode): ?object
                {
                    return null;
                }
            },
            new class implements GLMappingRepositoryInterface {
                public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
                {
                    return [];
                }
            }
        );

        $this->assertSame('gl_account_mapping', $rule->getName());
    }
}
