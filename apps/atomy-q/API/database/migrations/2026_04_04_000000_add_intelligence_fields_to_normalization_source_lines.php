<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalization_source_lines', function (Blueprint $table): void {
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->string('taxonomy_code')->nullable();
            $table->string('mapping_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('normalization_source_lines', function (Blueprint $table): void {
            $table->dropColumn(['ai_confidence', 'taxonomy_code', 'mapping_version']);
        });
    }
};
