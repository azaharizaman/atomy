<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Workflows;

use Nexus\FinanceOperations\Contracts\WorkflowStepInterface;
use Nexus\FinanceOperations\DTOs\WorkflowResult;
use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;
use Nexus\FinanceOperations\Exceptions\CoordinationException;
use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;
use PHPUnit\Framework\TestCase;

final class AbstractFinanceWorkflowTest extends TestCase
{
    public function testCanStartReturnsFalseWhenRequiredFieldMissing(): void
    {
        $workflow = new TestFinanceWorkflow(
            steps: [],
            requiredFields: ['tenant_id', 'period_id'],
        );

        self::assertFalse($workflow->canStart(['tenant_id' => 'tenant-1']));
    }

    public function testExecuteReturnsSuccessAndStepResultsForHappyPath(): void
    {
        $firstStep = new TestWorkflowStep('gather', ['records' => 2]);
        $secondStep = new TestWorkflowStep('post', ['journal_entries' => 3]);

        $workflow = new TestFinanceWorkflow(
            steps: [$firstStep, $secondStep],
            requiredFields: ['tenant_id'],
        );

        $result = $workflow->execute([
            'tenant_id' => 'tenant-1',
            'period_id' => '2026-01',
        ]);

        self::assertTrue($result->success);
        self::assertArrayHasKey('step_results', $result->data);
        
        // Guard nested keys access
        self::assertArrayHasKey('gather', $result->data['step_results']);
        self::assertSame(2, $result->data['step_results']['gather']['records']);
        
        self::assertArrayHasKey('post', $result->data['step_results']);
        self::assertSame(3, $result->data['step_results']['post']['journal_entries']);
        
        self::assertNotEmpty($result->data['execution_log']);
        self::assertStringStartsWith('WF-', $result->workflowId);
        self::assertFalse($firstStep->compensated);
        self::assertFalse($secondStep->compensated);
    }

    public function testExecuteCompensatesExecutedStepsWhenAStepReturnsFailure(): void
    {
        $firstStep = new TestWorkflowStep('first', ['done' => true]);
        $secondStep = new TestWorkflowStep('second', [], failWith: 'validation failed');

        $workflow = new TestFinanceWorkflow(
            steps: [$firstStep, $secondStep],
            requiredFields: ['tenant_id'],
        );

        $result = $workflow->execute(['tenant_id' => 'tenant-1']);

        self::assertFalse($result->success);
        
        // Guard errors array access
        self::assertArrayHasKey('failed_step', $result->errors);
        self::assertSame('second', $result->errors['failed_step']);
        
        self::assertArrayHasKey('error', $result->errors);
        self::assertSame('validation failed', $result->errors['error']);
        
        self::assertArrayHasKey('executed_steps', $result->errors);
        self::assertSame(['first'], $result->errors['executed_steps']);
        
        self::assertTrue($firstStep->compensated);
        self::assertFalse($secondStep->compensated);
    }

    public function testExecuteCompensatesCompletedStepsWhenLaterStepThrows(): void
    {
        $firstStep = new TestWorkflowStep('first', ['done' => true]);
        $secondStep = new TestWorkflowStep('second', [], throwsOnExecute: true);

        $workflow = new TestFinanceWorkflow(
            steps: [$firstStep, $secondStep],
            requiredFields: ['tenant_id'],
        );

        $result = $workflow->execute(['tenant_id' => 'tenant-1']);

        self::assertFalse($result->success);
        self::assertArrayHasKey('exception', $result->errors);
        self::assertStringContainsString('step exception', $result->errors['exception']);
        
        self::assertArrayHasKey('executed_steps', $result->errors);
        self::assertSame(['first'], $result->errors['executed_steps']);
        
        self::assertTrue($firstStep->compensated);
    }

    public function testCompensateRunsAllStepsInReverseOrderForManualCompensation(): void
    {
        $firstStep = new TestWorkflowStep('first', ['done' => true]);
        $secondStep = new TestWorkflowStep('second', ['done' => true]);

        $workflow = new TestFinanceWorkflow(
            steps: [$firstStep, $secondStep],
            requiredFields: ['tenant_id'],
        );

        $result = WorkflowResult::failure('WF-test', [
            'tenant_id' => 'tenant-1',
            'execution_log' => [],
        ]);

        $workflow->compensate($result);

        self::assertTrue($firstStep->compensated);
        self::assertTrue($secondStep->compensated);
        self::assertSame('WF-test', $firstStep->compensationContext?->workflowId);
        self::assertSame('WF-test', $secondStep->compensationContext?->workflowId);
    }

    public function testMetadataHelpersReturnExpectedDefaults(): void
    {
        $step = new TestWorkflowStep('single');
        $workflow = new TestFinanceWorkflow(
            steps: [$step],
            requiredFields: ['tenant_id'],
        );

        self::assertSame('TestFinanceWorkflow', $workflow->getName());
        self::assertNull($workflow->getCurrentStep());
        self::assertTrue($workflow->canRetry());
        self::assertFalse($workflow->isComplete());
        self::assertSame([], $workflow->getExecutionLog());
        self::assertCount(1, $workflow->getSteps());
        self::assertSame(1, $workflow->getTotalSteps());
    }
}

final readonly class TestFinanceWorkflow extends AbstractFinanceWorkflow
{
    /**
     * @param WorkflowStepInterface[] $steps
     * @param string[] $requiredFields
     */
    public function __construct(
        array $steps,
        private array $requiredFields,
    ) {
        parent::__construct(steps: $steps);
    }

    public function getWorkflowName(): string
    {
        return 'TestFinanceWorkflow';
    }

    public function getRequiredContextFields(): array
    {
        return $this->requiredFields;
    }
}

final class TestWorkflowStep implements WorkflowStepInterface
{
    public bool $compensated = false;

    public ?WorkflowStepContext $compensationContext = null;

    /**
     * @param array<string, mixed> $resultData
     */
    public function __construct(
        private string $name,
        private array $resultData = [],
        private ?string $failWith = null,
        private bool $throwsOnExecute = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function execute(WorkflowStepContext $context): WorkflowStepResult
    {
        if ($this->throwsOnExecute) {
            throw CoordinationException::workflowFailed('TestCoordinator', $context->workflowId, $this->name, 'step exception');
        }

        if ($this->failWith !== null) {
            return WorkflowStepResult::failure($this->name, $this->failWith);
        }

        return WorkflowStepResult::success($this->name, $this->resultData);
    }

    public function compensate(WorkflowStepContext $context): WorkflowStepResult
    {
        $this->compensated = true;
        $this->compensationContext = $context;

        return WorkflowStepResult::success($this->name . '.compensated');
    }
}
