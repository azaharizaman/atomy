<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Support;

final class SamplePath
{
    public static function root(): string
    {
        $configured = self::configuredRoot();
        if ($configured !== '') {
            return $configured;
        }

        if (!function_exists('base_path') || !function_exists('app')) {
            return '';
        }

        try {
            $app = app();
        } catch (\Throwable) {
            return '';
        }

        if (!is_object($app) || !method_exists($app, 'basePath')) {
            return '';
        }

        return realpath(dirname(base_path(), 3) . '/sample') ?: '';
    }

    private static function configuredRoot(): string
    {
        if (!function_exists('config')) {
            return '';
        }

        try {
            $configured = config('atomy.ai.sample_path');
        } catch (\Throwable) {
            return '';
        }

        if (!is_string($configured) || trim($configured) === '') {
            return '';
        }

        return realpath(trim($configured)) ?: '';
    }
}
