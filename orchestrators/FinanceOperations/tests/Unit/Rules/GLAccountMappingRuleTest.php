<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\GLAccountMappingRepositoryInterface;
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
        self::assertSame('inactive_account', $result->violations[0]['type']);
    }

    /**
     * @param array<string, bool> $accountStatuses
     */
    private function accountQuery(array $accountStatuses): GLAccountQueryInterface
    {
        return new class($accountStatuses) implements GLAccountQueryInterface {
            /**
             * @param array<string, bool> $accountStatuses
             */
            public function __construct(private array $accountStatuses) {}

            public function find(string $tenantId, string $accountCode): ?GLAccountRuleViewInterface
            {
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
    private function mappingRepository(array $mappings): GLAccountMappingRepositoryInterface
    {
        return new class($mappings) implements GLAccountMappingRepositoryInterface {
            /**
             * @param array<string, string> $mappings
             */
            public function __construct(private array $mappings) {}

            public function getMappingsForSubledger(string $tenantId, string $subledgerType): array
            {
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
