<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nexus\PaymentGateway\Contracts\GatewayFactoryInterface;
use Nexus\PaymentGateway\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\Factories\GatewayFactory;
use Nexus\PaymentGateway\Services\CurlHttpClient;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HttpClientInterface::class, CurlHttpClient::class);
        $this->app->singleton(GatewayFactoryInterface::class, GatewayFactory::class);
    }
}
