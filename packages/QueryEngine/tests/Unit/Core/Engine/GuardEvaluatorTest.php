<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Tests\Unit\Core\Engine;

use DateTimeImmutable;
use DateTimeInterface;
use Nexus\QueryEngine\Contracts\AnalyticsContextInterface;
use Nexus\QueryEngine\Contracts\ClockInterface;
use Nexus\QueryEngine\Core\Engine\DefaultClock;
use Nexus\QueryEngine\Core\Engine\GuardEvaluator;
use Nexus\QueryEngine\Exceptions\GuardConditionFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuardEvaluator::class)]
final class GuardEvaluatorTest extends TestCase
{
    #[Test]
    public function it_allows_role_guard_when_user_has_required_role(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(userRoles: ['analyst']);

        $result = $evaluator->evaluateAll([
            'role_required' => [
                'type' => 'role_required',
                'roles' => ['admin', 'analyst'],
            ],
        ], $context);

        self::assertTrue($result);
    }

    #[Test]
    public function it_rejects_role_guard_when_user_lacks_required_role(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(userRoles: ['viewer']);

        $this->expectException(GuardConditionFailedException::class);
        $this->expectExceptionMessage("Guard condition 'role_required' failed");

        $evaluator->evaluateAll([
            'role_required' => [
                'type' => 'role_required',
                'roles' => ['admin', 'analyst'],
            ],
        ], $context);
    }

    #[Test]
    public function it_supports_role_guard_without_explicit_type(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(userRoles: ['manager']);

        $result = $evaluator->evaluateAll([
            'role_required' => [
                'roles' => ['manager'],
            ],
        ], $context);

        self::assertTrue($result);
    }

    #[Test]
    public function it_rejects_unknown_guard_types(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext();

        $this->expectException(GuardConditionFailedException::class);
        $this->expectExceptionMessage("Guard condition 'custom_guard' failed");

        $evaluator->evaluateAll([
            'custom_guard' => [
                'type' => 'unexpected',
            ],
        ], $context);
    }

    #[Test]
    public function it_rejects_non_array_guard_configuration(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext();

        $this->expectException(GuardConditionFailedException::class);
        $this->expectExceptionMessage("Guard condition 'tenant_match' failed");

        $evaluator->evaluateAll([
            'tenant_match' => 'tenant-a',
        ], $context);
    }

    #[Test]
    public function it_handles_numeric_guard_keys_without_type_errors(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext();

        $this->expectException(GuardConditionFailedException::class);
        $this->expectExceptionMessage("Guard condition '0' failed");

        $evaluator->evaluateAll([
            0 => [
                'type' => 'unexpected',
            ],
        ], $context);
    }

    #[Test]
    public function it_rejects_tenant_guard_without_required_tenant_id(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(tenantId: 'tenant-a');

        $this->expectException(GuardConditionFailedException::class);

        $evaluator->evaluateAll([
            'tenant_match' => [
                'type' => 'tenant_match',
            ],
        ], $context);
    }

    #[Test]
    public function it_rejects_tenant_guard_when_current_tenant_is_missing(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(tenantId: null);

        $this->expectException(GuardConditionFailedException::class);

        $evaluator->evaluateAll([
            'tenant_match' => [
                'type' => 'tenant_match',
                'tenant_id' => 'tenant-a',
            ],
        ], $context);
    }

    #[Test]
    public function it_allows_tenant_guard_when_tenant_matches(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(tenantId: 'tenant-a');

        $result = $evaluator->evaluateAll([
            'tenant_match' => [
                'type' => 'tenant_match',
                'tenant_id' => 'tenant-a',
            ],
        ], $context);

        self::assertTrue($result);
    }

    #[Test]
    public function it_rejects_tenant_guard_when_tenant_does_not_match(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext(tenantId: 'tenant-b');

        $this->expectException(GuardConditionFailedException::class);
        $this->expectExceptionMessage("Guard condition 'tenant_match' failed");

        $evaluator->evaluateAll([
            'tenant_match' => [
                'type' => 'tenant_match',
                'tenant_id' => 'tenant-a',
            ],
        ], $context);
    }

    #[Test]
    public function it_rejects_time_window_guard_with_invalid_date_format(): void
    {
        $evaluator = GuardEvaluator::withDefaultClock();
        $context = $this->createContext();

        $this->expectException(GuardConditionFailedException::class);

        $evaluator->evaluateAll([
            'time_window' => [
                'type' => 'time_window',
                'start' => 'not-a-date',
            ],
        ], $context);
    }

    #[Test]
    public function it_rejects_time_window_guard_when_start_is_after_end(): void
    {
        $clock = new class (new DateTimeImmutable('2024-01-01T00:00:00+00:00')) implements ClockInterface {
            public function __construct(
                private readonly DateTimeImmutable $frozenTime
            ) {
            }

            public function now(): DateTimeImmutable
            {
                return $this->frozenTime;
            }
        };

        $evaluator = new GuardEvaluator($clock);
        $context = $this->createContext();

        $this->expectException(GuardConditionFailedException::class);

        $evaluator->evaluateAll([
            'time_window' => [
                'type' => 'time_window',
                'start' => (new DateTimeImmutable('+10 minutes'))->format(DateTimeInterface::ATOM),
                'end' => (new DateTimeImmutable('-10 minutes'))->format(DateTimeInterface::ATOM),
            ],
        ], $context);
    }

    #[Test]
    public function it_allows_time_window_guard_when_current_time_is_in_range(): void
    {
        $fixedTime = new DateTimeImmutable('2024-01-15T12:00:00+00:00');
        $clock = new class ($fixedTime) implements ClockInterface {
            public function __construct(
                private readonly DateTimeImmutable $frozenTime
            ) {
            }

            public function now(): DateTimeImmutable
            {
                return $this->frozenTime;
            }
        };

        $evaluator = new GuardEvaluator($clock);
        $context = $this->createContext();

        $result = $evaluator->evaluateAll([
            'time_window' => [
                'type' => 'time_window',
                'start' => (new DateTimeImmutable('2024-01-01T00:00:00+00:00'))->format(DateTimeInterface::ATOM),
                'end' => (new DateTimeImmutable('2024-12-31T23:59:59+00:00'))->format(DateTimeInterface::ATOM),
            ],
        ], $context);

        self::assertTrue($result);
    }

    /**
     * @param array<int, string> $userRoles
     */
    private function createContext(?string $tenantId = 'tenant-a', array $userRoles = ['analyst']): AnalyticsContextInterface
    {
        return new class ($tenantId, $userRoles) implements AnalyticsContextInterface {
            /**
             * @param array<int, string> $userRoles
             */
            public function __construct(
                private readonly ?string $tenantId,
                private readonly array $userRoles
            ) {
            }

            public function getUserId(): ?string
            {
                return 'user-123';
            }

            public function getTenantId(): ?string
            {
                return $this->tenantId;
            }

            public function getUserRoles(): array
            {
                return $this->userRoles;
            }

            public function getContextData(): array
            {
                return [];
            }

            public function getIpAddress(): ?string
            {
                return null;
            }

            public function getUserAgent(): ?string
            {
                return null;
            }
        };
    }
}