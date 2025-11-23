<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Services;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class FeatureFlagManagerTest extends TestCase
{
    // ========================================
    // Context Normalization Tests
    // ========================================

    public function test_isEnabled_normalizes_array_context_to_evaluation_context(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createMock(FlagRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('test.flag', 'tenant-123')
            ->willReturn($flag);

        $evaluator = $this->createMock(FlagEvaluatorInterface::class);
        $evaluator->expects($this->once())
            ->method('evaluate')
            ->with(
                $flag,
                $this->callback(function (EvaluationContext $ctx) {
                    return $ctx->tenantId === 'tenant-123'
                        && $ctx->userId === 'user-456';
                })
            )
            ->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('test.flag', [
            'tenantId' => 'tenant-123',
            'userId' => 'user-456',
        ]);

        $this->assertTrue($result);
    }

    public function test_isEnabled_accepts_evaluation_context_directly(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        $repository = $this->createMock(FlagRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('test.flag', 'tenant-123')
            ->willReturn($flag);

        $evaluator = $this->createMock(FlagEvaluatorInterface::class);
        $evaluator->expects($this->once())
            ->method('evaluate')
            ->with($flag, $context)
            ->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('test.flag', $context);

        $this->assertTrue($result);
    }

    public function test_isEnabled_uses_empty_context_when_not_provided(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createMock(FlagRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('test.flag', null)
            ->willReturn($flag);

        $evaluator = $this->createMock(FlagEvaluatorInterface::class);
        $evaluator->expects($this->once())
            ->method('evaluate')
            ->with(
                $flag,
                $this->callback(function (EvaluationContext $ctx) {
                    return $ctx->tenantId === null
                        && $ctx->userId === null;
                })
            )
            ->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('test.flag');

        $this->assertTrue($result);
    }

    // ========================================
    // Flag Not Found (Fail-Closed) Tests
    // ========================================

    public function test_isEnabled_returns_false_by_default_when_flag_not_found(): void
    {
        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn(null);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Feature flag not found',
                $this->callback(function (array $ctx) {
                    return $ctx['flag'] === 'nonexistent.flag'
                        && $ctx['default_returned'] === false;
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('nonexistent.flag');

        $this->assertFalse($result, 'Fail-closed: should return false by default');
    }

    public function test_isEnabled_returns_custom_default_when_flag_not_found(): void
    {
        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn(null);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Feature flag not found',
                $this->callback(function (array $ctx) {
                    return $ctx['default_returned'] === true;
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('nonexistent.flag', defaultIfNotFound: true);

        $this->assertTrue($result);
    }

    // ========================================
    // Evaluation Success Tests
    // ========================================

    public function test_isEnabled_delegates_to_evaluator_when_flag_found(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createMock(FlagEvaluatorInterface::class);
        $evaluator->expects($this->once())
            ->method('evaluate')
            ->with($flag, $this->isInstanceOf(EvaluationContext::class))
            ->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Feature flag evaluated',
                $this->callback(function (array $ctx) {
                    return $ctx['flag'] === 'test.flag'
                        && $ctx['result'] === true;
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('test.flag');

        $this->assertTrue($result);
    }

    public function test_isEnabled_logs_evaluation_details(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::PERCENTAGE_ROLLOUT);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Feature flag evaluated',
                $this->callback(function (array $ctx) {
                    return $ctx['flag'] === 'test.flag'
                        && $ctx['result'] === false
                        && $ctx['strategy'] === 'percentage_rollout'
                        && $ctx['enabled'] === true;
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $manager->isEnabled('test.flag');
    }

    // ========================================
    // Evaluation Error Tests (Fail-Closed)
    // ========================================

    public function test_isEnabled_returns_false_and_logs_error_when_evaluation_throws(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluate')
            ->willThrowException(new \RuntimeException('Evaluator crashed'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Feature flag evaluation failed',
                $this->callback(function (array $ctx) {
                    return $ctx['flag'] === 'test.flag'
                        && $ctx['error'] === 'Evaluator crashed'
                        && $ctx['exception_class'] === \RuntimeException::class;
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('test.flag');

        $this->assertFalse($result, 'Fail-closed: should return false on evaluation error');
    }

    // ========================================
    // isDisabled Tests
    // ========================================

    public function test_isDisabled_returns_opposite_of_isEnabled(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isDisabled('test.flag');

        $this->assertFalse($result);
    }

    public function test_isDisabled_respects_defaultIfNotFound(): void
    {
        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn(null);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isDisabled('nonexistent.flag', defaultIfNotFound: true);

        $this->assertFalse($result, '!isEnabled(true) = false');
    }

    // ========================================
    // evaluateMany Tests
    // ========================================

    public function test_evaluateMany_returns_empty_array_for_empty_input(): void
    {
        $repository = $this->createStub(FlagRepositoryInterface::class);
        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $results = $manager->evaluateMany([]);

        $this->assertSame([], $results);
    }

    public function test_evaluateMany_bulk_loads_flags_from_repository(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('flag.two');

        $repository = $this->createMock(FlagRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findMany')
            ->with(['flag.one', 'flag.two'], 'tenant-123')
            ->willReturn([
                'flag.one' => $flag1,
                'flag.two' => $flag2,
            ]);

        $evaluator = $this->createMock(FlagEvaluatorInterface::class);
        $evaluator->expects($this->once())
            ->method('evaluateMany')
            ->willReturn([
                'flag.one' => true,
                'flag.two' => false,
            ]);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $results = $manager->evaluateMany(['flag.one', 'flag.two'], [
            'tenantId' => 'tenant-123',
        ]);

        $this->assertSame([
            'flag.one' => true,
            'flag.two' => false,
        ], $results);
    }

    public function test_evaluateMany_fills_false_for_flags_not_found(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('findMany')->willReturn([
            'flag.one' => $flag1,
            // flag.two not found
        ]);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluateMany')->willReturn([
            'flag.one' => true,
        ]);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $results = $manager->evaluateMany(['flag.one', 'flag.two']);

        $this->assertSame([
            'flag.one' => true,
            'flag.two' => false, // filled with false
        ], $results);
    }

    public function test_evaluateMany_handles_evaluation_error_gracefully(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('findMany')->willReturn(['flag.one' => $flag1]);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluateMany')
            ->willThrowException(new \RuntimeException('Bulk evaluation failed'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Bulk flag evaluation failed',
                $this->callback(function (array $ctx) {
                    return $ctx['error'] === 'Bulk evaluation failed';
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $results = $manager->evaluateMany(['flag.one', 'flag.two']);

        // Both should be false due to error + not found
        $this->assertSame([
            'flag.one' => false,
            'flag.two' => false,
        ], $results);
    }

    public function test_evaluateMany_logs_bulk_evaluation_results(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('findMany')->willReturn(['flag.one' => $flag1]);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluateMany')->willReturn(['flag.one' => true]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Bulk feature flags evaluated',
                $this->callback(function (array $ctx) {
                    return $ctx['flags'] === ['flag.one', 'flag.two']
                        && $ctx['found_count'] === 1
                        && $ctx['results'] === ['flag.one' => true, 'flag.two' => false];
                })
            );

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $manager->evaluateMany(['flag.one', 'flag.two']);
    }

    // ========================================
    // Integration Tests
    // ========================================

    public function test_full_flow_with_enabled_flag(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('new.feature');
        $flag->method('isEnabled')->willReturn(true);
        $flag->method('getStrategy')->willReturn(FlagStrategy::USER_LIST);
        $flag->method('getValue')->willReturn(['user-123']);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn(true);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('new.feature', ['userId' => 'user-123']);

        $this->assertTrue($result);
    }

    public function test_full_flow_with_disabled_flag(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('old.feature');
        $flag->method('isEnabled')->willReturn(false);
        $flag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $flag->method('getOverride')->willReturn(null);

        $repository = $this->createStub(FlagRepositoryInterface::class);
        $repository->method('find')->willReturn($flag);

        $evaluator = $this->createStub(FlagEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn(false);

        $logger = $this->createStub(LoggerInterface::class);

        $manager = new FeatureFlagManager($repository, $evaluator, $logger);

        $result = $manager->isEnabled('old.feature');

        $this->assertFalse($result);
    }
}
