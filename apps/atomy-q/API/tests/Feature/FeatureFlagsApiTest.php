<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\Feature\Api\ApiTestCase;

final class FeatureFlagsApiTest extends ApiTestCase
{
    public function test_feature_flags_returns_mirror_of_config(): void
    {
        Config::set('features.projects', true);
        Config::set('features.tasks', false);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/feature-flags');

        $response->assertOk();
        $response->assertJsonPath('data.projects', true);
        $response->assertJsonPath('data.tasks', false);
    }
}
