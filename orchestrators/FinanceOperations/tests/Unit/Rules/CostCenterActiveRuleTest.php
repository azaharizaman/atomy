<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\CostCenterQueryInterface;
use Nexus\FinanceOperations\Contracts\CostCenterRuleViewInterface;
use Nexus\FinanceOperations\DTOs\RuleContext;
use Nexus\FinanceOperations\Rules\CostCenterActiveRule;
use PHPUnit\Framework\TestCase;

final class CostCenterActiveRuleTest extends TestCase
{
    public function testAllCostCentersActivePassesValidation(): void
    {
        $rule = new CostCenterActiveRule(
            $this->query([
                'cc-001' => $this->view('Primary', true, true),
                'cc-002' => $this->view('Secondary', true, true),
            ])
        );

        $result = $rule->check(
            RuleContext::forCostCenterValidation('tenant-001', ['cc-001', 'cc-002'])
        );

        self::assertTrue($result->passed);
    }

    public function testEmptyCostCenterListPassesValidation(): void
    {
        $rule = new CostCenterActiveRule($this->query([]));

        $result = $rule->check(
            RuleContext::forCostCenterValidation('tenant-001', [])
        );

        self::assertTrue($result->passed);
    }

    public function testMissingCostCenterFailsValidation(): void
    {
        $rule = new CostCenterActiveRule($this->query([]));

        $result = $rule->check(
            RuleContext::forCostCenterValidation('tenant-001', ['unknown'])
        );

        self::assertFalse($result->passed);
        self::assertSame('not_found', $result->violations[0]['type']);
    }

    public function testInactiveCostCenterFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(
            $this->query([
                'cc-001' => $this->view('Inactive', false, true),
            ])
        );

        $result = $rule->check(
            RuleContext::forCostCenterValidation('tenant-001', ['cc-001'])
        );

        self::assertFalse($result->passed);
        self::assertSame('inactive', $result->violations[0]['type']);
    }

    public function testCostCenterUnableToReceiveAllocationsFailsValidation(): void
    {
        $rule = new CostCenterActiveRule(
            $this->query([
                'cc-001' => $this->view('Restricted', true, false),
            ])
        );

        $result = $rule->check(
            RuleContext::forCostCenterValidation('tenant-001', ['cc-001'])
        );

        self::assertFalse($result->passed);
        self::assertSame('cannot_receive', $result->violations[0]['type']);
    }

    public function testSingleCostCenterIdInContextIsSupported(): void
    {
        $rule = new CostCenterActiveRule(
            $this->query([
                'cc-001' => $this->view('Single', true, true),
            ])
        );

        $result = $rule->check(new RuleContext(
            tenantId: 'tenant-001',
            costCenterId: 'cc-001',
        ));

        self::assertTrue($result->passed);
    }

    /**
     * @param array<string, CostCenterRuleViewInterface> $entries
     */
    private function query(array $entries): CostCenterQueryInterface
    {
        return new class($entries) implements CostCenterQueryInterface {
            /**
             * @param array<string, CostCenterRuleViewInterface> $entries
             */
            public function __construct(private array $entries) {}

            public function find(string $tenantId, string $costCenterId): ?CostCenterRuleViewInterface
            {
                return $this->entries[$costCenterId] ?? null;
            }
        };
    }

    private function view(string $name, bool $active, bool $canReceive): CostCenterRuleViewInterface
    {
        return new class($name, $active, $canReceive) implements CostCenterRuleViewInterface {
            public function __construct(
                private string $name,
                private bool $active,
                private bool $canReceive,
            ) {}

            public function isActive(): bool
            {
                return $this->active;
            }

            public function canReceiveAllocations(): bool
            {
                return $this->canReceive;
            }

            public function getName(): string
            {
                return $this->name;
            }
        };
    }
}
