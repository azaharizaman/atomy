<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        return $app;
    }

    public function test_forgot_password_returns_identical_payload_for_missing_and_existing_email(): void
    {
        Mail::fake();

        $expected = [
            'message' => 'If an account exists for this email, password reset instructions have been sent.',
        ];

        $missing = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);
        $missing->assertOk();
        $missing->assertExactJson($expected);
        Mail::assertNothingSent();

        $email = 'existing-' . Str::lower((string) Str::ulid()) . '@example.com';
        User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => $email,
            'name' => 'Existing User',
            'password_hash' => Hash::make('old-password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $existing = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $existing->assertOk();
        $existing->assertExactJson($expected);

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($email): bool {
            return $mail->hasTo($email);
        });
    }

    public function test_reset_password_updates_hash_and_invalidates_token(): void
    {
        Mail::fake();

        $email = 'reset-' . Str::lower((string) Str::ulid()) . '@example.com';
        User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => $email,
            'name' => 'Reset User',
            'password_hash' => Hash::make('old-password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ])->assertOk();

        $plainToken = '';
        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use (&$plainToken): bool {
            $plainToken = $mail->plainToken;

            return $plainToken !== '';
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'token' => $plainToken,
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertOk();

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('new-password-9', (string) $user->password_hash));

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'token' => $plainToken,
            'password' => 'another-password-9',
            'password_confirmation' => 'another-password-9',
        ])->assertStatus(422);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $email = 'x@example.com';
        User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => $email,
            'name' => 'X',
            'password_hash' => Hash::make('p'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'token' => str_repeat('a', 40),
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertStatus(422);
    }
}
