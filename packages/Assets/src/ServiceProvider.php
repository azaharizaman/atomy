<?php

declare(strict_types=1);

namespace Nexus\Assets;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nexus\Assets\Contracts\SettingsReaderInterface;
use Nexus\Assets\Services\SettingsReader;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingsReaderInterface::class, SettingsReader::class);
    }
}
