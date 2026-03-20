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
     * Token rows are scoped by the user's tenant_id.
     */
    public function sendResetLink(string $email): void
    {
        $email = strtolower(trim($email));

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return;
        }

        $tenantId = (string) $user->tenant_id;
        $plain = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ],
            [
                'token' => Hash::make($plain),
                'created_at' => now(),
            ],
        );

        Mail::to($email)->send(new PasswordResetMail($plain, $this->tokenTtlMinutes));
    }

    /**
     * @throws \InvalidArgumentException when token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $email = strtolower(trim($email));

        DB::transaction(function () use ($email, $token, $newPassword): void {
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                throw new \InvalidArgumentException('Invalid reset token.');
            }

            $tenantId = (string) $user->tenant_id;

            $row = DB::table('password_reset_tokens')
                ->where('tenant_id', $tenantId)
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($row === null || ! is_string($row->token)) {
                throw new \InvalidArgumentException('Invalid reset token.');
            }

            $createdAt = $row->created_at ?? null;
            if ($createdAt === null || $createdAt === '') {
                DB::table('password_reset_tokens')
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->delete();
                throw new \InvalidArgumentException('Invalid reset token.');
            }

            try {
                $created = \Carbon\Carbon::parse((string) $createdAt);
            } catch (\Throwable) {
                DB::table('password_reset_tokens')
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->delete();
                throw new \InvalidArgumentException('Invalid reset token.');
            }

            if ($created->copy()->addMinutes($this->tokenTtlMinutes)->isPast()) {
                DB::table('password_reset_tokens')
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->delete();
                throw new \InvalidArgumentException('Reset token has expired.');
            }

            if (! Hash::check($token, (string) $row->token)) {
                throw new \InvalidArgumentException('Invalid reset token.');
            }

            $user->password_hash = Hash::make($newPassword);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('tenant_id', $tenantId)
                ->where('email', $email)
                ->delete();
        });
    }
}
