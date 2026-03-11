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
            return new JwtService(
                (string) config('jwt.secret'),
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
