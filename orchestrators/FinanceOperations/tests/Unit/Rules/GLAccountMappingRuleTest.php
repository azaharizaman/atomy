<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\GLAccountMappingQueryInterface;
use Nexus\FinanceOperations\Contracts\GLAccountMappingRuleViewInterface;
use Nexus\FinanceOperations\Contracts\GLAccountQueryInterface;
use Nexus\FinanceOperations\Contracts\GLAccountRuleViewInterface;
use Nexus\FinanceOperations\DTOs\RuleContext;
use Nexus\FinanceOperations\Rules\GLAccountMappingRule;
use PHPUnit\Framework\TestCase;

final class GLAccountMappingRuleTest extends TestCase
{
    public function testAllMappingsValidPassesValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery(['4000' => true]),
            mappingRepository: $this->mappingRepository([
                'INVOICE' => '4000',
            ]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', 'AR', ['INVOICE'])
        );

        self::assertTrue($result->passed);
    }

    public function testMissingSubledgerTypeFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery([]),
            mappingRepository: $this->mappingRepository([]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', '', ['INVOICE'])
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    public function testMissingTransactionTypesFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery([]),
            mappingRepository: $this->mappingRepository([]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', 'AR', [])
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    public function testMissingMappingFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery([]),
            mappingRepository: $this->mappingRepository([]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', 'AR', ['INVOICE'])
        );

        self::assertFalse($result->passed);
        self::assertNotEmpty($result->violations);
        self::assertSame('missing_mapping', $result->violations[0]['type']);
    }

    public function testInvalidGlAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery([]),
            mappingRepository: $this->mappingRepository([
                'INVOICE' => '9999',
            ]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', 'AR', ['INVOICE'])
        );

        self::assertFalse($result->passed);
        self::assertNotEmpty($result->violations);
        self::assertSame('invalid_account', $result->violations[0]['type']);
    }

    public function testInactiveGlAccountFailsValidation(): void
    {
        $rule = new GLAccountMappingRule(
            chartOfAccountQuery: $this->accountQuery(['4000' => false]),
            mappingRepository: $this->mappingRepository([
                'INVOICE' => '4000',
            ]),
        );

        $result = $rule->check(
            RuleContext::forGlAccountMappingValidation('tenant-001', 'AR', ['INVOICE'])
        );

        self::assertFalse($result->passed);
        self::assertNotEmpty($result->violations);
        self::assertSame('inactive_account', $result->violations[0]['type']);
    }

    /**
     * @param array<string, bool> $accountStatuses
     */
    private function accountQuery(array $accountStatuses, string $expectedTenantId = 'tenant-001'): GLAccountQueryInterface
    {
        return new class($accountStatuses, $expectedTenantId) implements GLAccountQueryInterface {
            /**
             * @param array<string, bool> $accountStatuses
             */
            public function __construct(private array $accountStatuses, private string $expectedTenantId) {}

            public function find(string $tenantId, string $accountCode): ?GLAccountRuleViewInterface
            {
                if ($tenantId !== $this->expectedTenantId) {
                    throw new \InvalidArgumentException("Unexpected tenant: got $tenantId, expected {$this->expectedTenantId}");
                }

                if (!array_key_exists($accountCode, $this->accountStatuses)) {
                    return null;
                }

                return new class($this->accountStatuses[$accountCode]) implements GLAccountRuleViewInterface {
                    public function __construct(private bool $active) {}

                    public function isActive(): bool
                    {
                        return $this->active;
                    }
                };
            }
        };
    }

    /**
     * @param array<string, string> $mappings
     */
    private function mappingRepository(array $mappings, string $expectedTenantId = 'tenant-001', string $expectedSubledgerType = 'AR'): GLAccountMappingQueryInterface
    {
        return new class($mappings, $expectedTenantId, $expectedSubledgerType) implements GLAccountMappingQueryInterface {
            /**
             * @param array<string, string> $mappings
             */
            public function __construct(private array $mappings, private string $expectedTenantId, private string $expectedSubledgerType) {}

            public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
            {
                if ($tenantId !== $this->expectedTenantId || $subledgerType !== $this->expectedSubledgerType) {
                    throw new \InvalidArgumentException("Unexpected params: tenant $tenantId/$subledgerType vs expected {$this->expectedTenantId}/{$this->expectedSubledgerType}");
                }

                $items = [];
                foreach ($this->mappings as $transactionType => $accountCode) {
                    $items[] = new class($transactionType, $accountCode) implements GLAccountMappingRuleViewInterface {
                        public function __construct(
                            private string $transactionType,
                            private string $accountCode,
                        ) {}

                        public function getTransactionType(): string
                        {
                            return $this->transactionType;
                        }

                        public function getGLAccountCode(): string
                        {
                            return $this->accountCode;
                        }
                    };
                }

                return $items;
            }
        };
    }
}
