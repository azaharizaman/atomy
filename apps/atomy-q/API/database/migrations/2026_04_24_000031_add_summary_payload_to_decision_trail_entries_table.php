<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decision_trail_entries', function (Blueprint $table): void {
            // Durable artifact summary used by decision-trail hashing and provenance reads.
            // Schema is documented in API IMPLEMENTATION_SUMMARY.md and currently stores:
            // artifact_kind/artifact_origin/feature_key (string), optional award_id,
            // approval_id, vendor_id (string), optional available (bool),
            // optional provenance (object), and optional artifact (object).
            $table->json('summary_payload')->nullable()->after('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('decision_trail_entries', function (Blueprint $table): void {
            $table->dropColumn('summary_payload');
        });
    }
};
