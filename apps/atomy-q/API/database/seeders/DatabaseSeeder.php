<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Single-tenant petrochemical-style dataset (Nordfjord Process Chemicals demo).
     * Fixed tenant: set ATOMY_SEED_TENANT_ID or default ULID in PetrochemicalTenantSeeder.
     */
    public function run(): void
    {
        $this->call(PetrochemicalTenantSeeder::class);
    }
}
