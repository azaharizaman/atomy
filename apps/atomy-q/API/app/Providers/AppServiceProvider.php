<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\JwtServiceInterface;
use App\Services\JwtService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JwtServiceInterface::class, function (): JwtServiceInterface {
            $secret = (string) config('jwt.secret');

            if ($secret === '') {
                throw new \InvalidArgumentException('JWT_SECRET is not set or empty in environment configuration.');
            }

            return new JwtService(
                $secret,
                (int) config('jwt.ttl'),
                (int) config('jwt.refresh_ttl'),
                (string) config('jwt.algo'),
                (string) config('jwt.issuer'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
