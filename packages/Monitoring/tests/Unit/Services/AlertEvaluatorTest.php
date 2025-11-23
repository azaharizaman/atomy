<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Monitoring\Contracts\AlertDispatcherInterface;
use Nexus\Monitoring\Services\AlertEvaluator;
use Nexus\Monitoring\ValueObjects\AlertContext;
use Nexus\Monitoring\ValueObjects\AlertSeverity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(AlertEvaluator::class)]
#[Group('monitoring')]
#[Group('alerts')]
final class AlertEvaluatorTest extends TestCase
{
    private MockObject&AlertDispatcherInterface $dispatcher;
    private MockObject&LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(AlertDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    #[Test]
    public function it_evaluates_and_dispatches_critical_exception_alert(): void
    {
        $exception = new \RuntimeException('Database connection failed');
        
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (AlertContext $context) {
                return $context->severity === AlertSeverity::CRITICAL
                    && $context->message === 'Database connection failed'
                    && $context->throwable instanceof \RuntimeException
                    && isset($context->context['exception_class'])
                    && $context->context['exception_class'] === 'RuntimeException';
            }));
        
        $evaluator = new AlertEvaluator($this->dispatcher, $this->logger);
        $evaluator->evaluateException($exception);
    }
    
    #[Test]
    #[DataProvider('exceptionSeverityMappingProvider')]
    public function it_maps_exception_types_to_severity_levels(
        \Throwable $exception,
        AlertSeverity $expectedSeverity
    ): void {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn(AlertContext $c) => $c->severity === $expectedSeverity));
        
        $evaluator = new AlertEvaluator($this->dispatcher, $this->logger);
        $evaluator->evaluateException($exception);
    }
    
    public static function exceptionSeverityMappingProvider(): array
    {
        return [
            'RuntimeException is CRITICAL' => [
                new \RuntimeException('Error'),
                AlertSeverity::CRITICAL,
            ],
            'LogicException is WARNING' => [
                new \LogicException('Logic error'),
                AlertSeverity::WARNING,
            ],
            'InvalidArgumentException is WARNING' => [
                new \InvalidArgumentException('Invalid input'),
                AlertSeverity::WARNING,
            ],
            'Generic Exception is INFO' => [
                new \Exception('Generic issue'),
                AlertSeverity::INFO,
            ],
        ];
    }
    
    #[Test]
    public function it_deduplicates_identical_alerts_within_window(): void
    {
        $exception = new \RuntimeException('Same error');
        
        $this->dispatcher
            ->expects($this->once()) // Should only dispatch once due to deduplication
            ->method('dispatch');
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            deduplicationWindowSeconds: 300
        );
        
        // Send same exception twice within dedup window
        $evaluator->evaluateException($exception);
        $evaluator->evaluateException($exception); // Should be deduplicated
    }
    
    #[Test]
    public function it_dispatches_duplicate_alerts_after_window_expires(): void
    {
        $exception = new \RuntimeException('Recurring error');
        
        $this->dispatcher
            ->expects($this->exactly(2)) // Should dispatch twice
            ->method('dispatch');
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            deduplicationWindowSeconds: 1 // 1 second window
        );
        
        $evaluator->evaluateException($exception);
        sleep(2); // Wait for window to expire
        $evaluator->evaluateException($exception); // Should dispatch again
    }
    
    #[Test]
    public function it_bypasses_deduplication_when_disabled(): void
    {
        $exception = new \RuntimeException('No dedup');
        
        $this->dispatcher
            ->expects($this->exactly(3)) // All 3 should dispatch
            ->method('dispatch');
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            enableDeduplication: false
        );
        
        $evaluator->evaluateException($exception);
        $evaluator->evaluateException($exception);
        $evaluator->evaluateException($exception);
    }
    
    #[Test]
    public function it_includes_exception_metadata_in_alert_context(): void
    {
        $exception = new \RuntimeException('Detailed error', 500);
        
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (AlertContext $context) {
                return $context->context['exception_class'] === 'RuntimeException'
                    && $context->context['exception_code'] === 500
                    && isset($context->context['stack_trace'])
                    && isset($context->context['occurred_at'])
                    && $context->throwable instanceof \RuntimeException;
            }));
        
        $evaluator = new AlertEvaluator($this->dispatcher, $this->logger);
        $evaluator->evaluateException($exception);
    }
    
    #[Test]
    public function it_logs_alert_evaluation(): void
    {
        $exception = new \RuntimeException('Logged error');
        
        $this->dispatcher->method('dispatch');
        
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Alert evaluated and dispatched'),
                $this->callback(fn($context) => 
                    $context['severity'] === 'critical' &&
                    isset($context['message']) &&
                    isset($context['fingerprint'])
                )
            );
        
        $evaluator = new AlertEvaluator($this->dispatcher, $this->logger);
        $evaluator->evaluateException($exception);
    }
    
    #[Test]
    public function it_logs_deduplicated_alerts(): void
    {
        $exception = new \RuntimeException('Dedup logged');
        
        $this->dispatcher->method('dispatch');
        
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Alert deduplicated'));
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            deduplicationWindowSeconds: 300
        );
        
        $evaluator->evaluateException($exception);
        $evaluator->evaluateException($exception); // Triggers dedup log
    }
    
    #[Test]
    public function it_generates_consistent_fingerprints_for_same_exception(): void
    {
        $fingerprints = [];
        
        $this->dispatcher
            ->method('dispatch')
            ->willReturnCallback(function (AlertContext $context) use (&$fingerprints) {
                $fingerprints[] = $context->getFingerprint();
            });
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            enableDeduplication: false
        );
        
        // Same exception type and message should produce same fingerprint
        $evaluator->evaluateException(new \RuntimeException('Test'));
        $evaluator->evaluateException(new \RuntimeException('Test'));
        
        $this->assertCount(2, $fingerprints);
        $this->assertSame($fingerprints[0], $fingerprints[1]);
    }
    
    #[Test]
    public function it_generates_different_fingerprints_for_different_exceptions(): void
    {
        $fingerprints = [];
        
        $this->dispatcher
            ->method('dispatch')
            ->willReturnCallback(function (AlertContext $context) use (&$fingerprints) {
                $fingerprints[] = $context->getFingerprint();
            });
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            enableDeduplication: false
        );
        
        $evaluator->evaluateException(new \RuntimeException('Error A'));
        $evaluator->evaluateException(new \LogicException('Error B'));
        
        $this->assertCount(2, $fingerprints);
        $this->assertNotSame($fingerprints[0], $fingerprints[1]);
    }
    
    #[Test]
    public function it_clears_deduplication_cache_on_demand(): void
    {
        $exception = new \RuntimeException('Clear cache test');
        
        $this->dispatcher
            ->expects($this->exactly(2)) // Should dispatch twice after cache clear
            ->method('dispatch');
        
        $evaluator = new AlertEvaluator(
            dispatcher: $this->dispatcher,
            logger: $this->logger,
            deduplicationWindowSeconds: 300
        );
        
        $evaluator->evaluateException($exception);
        $evaluator->clearDeduplicationCache(); // Clear cache
        $evaluator->evaluateException($exception); // Should dispatch again
    }
    
    #[Test]
    public function it_handles_dispatcher_exceptions_gracefully(): void
    {
        $exception = new \RuntimeException('Original error');
        
        $this->dispatcher
            ->method('dispatch')
            ->willThrowException(new \Exception('Dispatcher failed'));
        
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to dispatch alert'),
                $this->arrayHasKey('dispatcher_error')
            );
        
        $evaluator = new AlertEvaluator($this->dispatcher, $this->logger);
        
        // Should not throw, just log the error
        $evaluator->evaluateException($exception);
    }
}
