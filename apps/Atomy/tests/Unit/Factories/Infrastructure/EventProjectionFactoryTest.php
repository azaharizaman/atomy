<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Infrastructure;

use App\Models\Infrastructure\EventProjection;
use Database\Factories\Infrastructure\EventProjectionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventProjectionFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_projection_with_default_attributes(): void
    {
        $projection = EventProjection::factory()->make();

        $this->assertEquals('TestProjector', $projection->projector_name);
        $this->assertEquals('active', $projection->status);
        $this->assertNull($projection->error_message);
    }

    /** @test */
    public function it_marks_projection_as_active(): void
    {
        $projection = EventProjection::factory()->active()->make();

        $this->assertEquals('active', $projection->status);
        $this->assertNull($projection->error_message);
    }

    /** @test */
    public function it_marks_projection_as_paused(): void
    {
        $projection = EventProjection::factory()->paused()->make();

        $this->assertEquals('paused', $projection->status);
    }

    /** @test */
    public function it_marks_projection_with_error(): void
    {
        $projection = EventProjection::factory()->withError('Database connection failed')->make();

        $this->assertEquals('error', $projection->status);
        $this->assertEquals('Database connection failed', $projection->error_message);
    }

    /** @test */
    public function it_sets_projector_name_with_named_state(): void
    {
        $projection = EventProjection::factory()->named('BalanceProjector')->make();

        $this->assertEquals('BalanceProjector', $projection->projector_name);
    }

    /** @test */
    public function it_chains_state_methods(): void
    {
        $projection = EventProjection::factory()
            ->named('CustomProjector')
            ->paused()
            ->make();

        $this->assertEquals('CustomProjector', $projection->projector_name);
        $this->assertEquals('paused', $projection->status);
    }

    /** @test */
    public function it_returns_new_factory_instance_for_chaining(): void
    {
        $factory1 = EventProjection::factory();
        $factory2 = $factory1->active();

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(EventProjectionFactory::class, $factory2);
    }
}
