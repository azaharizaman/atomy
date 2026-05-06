<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class LayerBoundaryTest extends TestCase
{
    public function test_metric_engine_does_not_depend_on_frameworks_or_domain_packages(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../../src')
        );

        $forbidden = [
            'Illuminate\\',
            'Symfony\\',
            'Nexus\\FinancialRatios\\',
            'Nexus\\Treasury\\',
            'Nexus\\ESG\\',
            'Nexus\\SourcingScoring\\',
            'Nexus\\PerformanceReview\\',
            'banded_health_score',
            'vendor health',
            'gross margin',
            'ServiceProvider',
            'healthy',
            'risk',
            'preferred vendor',
            'vendor quality',
        ];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $contents, $file->getPathname());
            }
        }
    }
}
