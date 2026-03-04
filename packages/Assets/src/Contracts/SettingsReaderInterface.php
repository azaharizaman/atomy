<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

interface SettingsReaderInterface
{
    public function getString(string $key, string $default = ''): string;

    public function getBool(string $key, bool $default = false): bool;
}
