<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final readonly class PasswordResetService
{
    public function __construct(
        private int $tokenTtlMinutes,
    ) {}

    /**
     * Idempotent: always safe to call; only sends mail when a user exists.
     */
    public function sendResetLink(string $email): void
    {
        $email = strtolower(trim($email));

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return;
        }

        $plain = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plain),
                'created_at' => now(),
            ],
        );

        Mail::to($email)->send(new PasswordResetMail($plain));
    }

    /**
     * @throws \InvalidArgumentException when token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $email = strtolower(trim($email));
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($row === null || ! is_string($row->token)) {
            throw new \InvalidArgumentException('Invalid reset token.');
        }

        $createdAt = $row->created_at ?? null;
        if ($createdAt !== null) {
            $created = \Carbon\Carbon::parse((string) $createdAt);
            if ($created->copy()->addMinutes($this->tokenTtlMinutes)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                throw new \InvalidArgumentException('Reset token has expired.');
            }
        }

        if (! Hash::check($token, (string) $row->token)) {
            throw new \InvalidArgumentException('Invalid reset token.');
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \InvalidArgumentException('Invalid reset token.');
        }

        $user->password_hash = Hash::make($newPassword);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
