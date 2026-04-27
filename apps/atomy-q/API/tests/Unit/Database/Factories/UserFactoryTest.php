<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\User;
use Tests\TestCase;

final class UserFactoryTest extends TestCase
{
    public function test_user_active_state(): void
    {
        $user = User::factory()->active()->create();

        $this->assertSame('active', $user->status);
    }

    public function test_user_locked_state(): void
    {
        $user = User::factory()->locked()->create();

        $this->assertSame('locked', $user->status);
    }

    public function test_user_admin_state(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame('admin', $user->role);
    }
}