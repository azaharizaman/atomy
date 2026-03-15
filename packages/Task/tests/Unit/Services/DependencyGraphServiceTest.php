<?php

declare(strict_types=1);

namespace Nexus\Task\Tests\Unit\Services;

use Nexus\Task\Services\DependencyGraphService;
use PHPUnit\Framework\TestCase;

final class DependencyGraphServiceTest extends TestCase
{
    private DependencyGraphService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DependencyGraphService();
    }

    public function test_empty_graph_has_no_cycle(): void
    {
        self::assertFalse($this->service->hasCycle([]));
    }

    public function test_single_task_no_predecessors_has_no_cycle(): void
    {
        self::assertFalse($this->service->hasCycle(['a' => []]));
    }

    public function test_linear_chain_has_no_cycle(): void
    {
        $graph = [
            'a' => [],
            'b' => ['a'],
            'c' => ['b'],
        ];
        self::assertFalse($this->service->hasCycle($graph));
    }

    public function test_direct_cycle_is_detected(): void
    {
        $graph = [
            'a' => ['b'],
            'b' => ['a'],
        ];
        self::assertTrue($this->service->hasCycle($graph));
    }

    public function test_three_node_cycle_is_detected(): void
    {
        $graph = [
            'a' => ['c'],
            'b' => ['a'],
            'c' => ['b'],
        ];
        self::assertTrue($this->service->hasCycle($graph));
    }

    public function test_self_loop_is_detected(): void
    {
        $graph = ['a' => ['a']];
        self::assertTrue($this->service->hasCycle($graph));
    }

    public function test_diamond_no_cycle(): void
    {
        $graph = [
            'a' => [],
            'b' => ['a'],
            'c' => ['a'],
            'd' => ['b', 'c'],
        ];
        self::assertFalse($this->service->hasCycle($graph));
    }
}
