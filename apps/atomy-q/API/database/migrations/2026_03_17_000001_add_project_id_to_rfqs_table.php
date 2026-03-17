<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table): void {
            if (! Schema::hasColumn('rfqs', 'project_id')) {
                $table->ulid('project_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table): void {
            if (Schema::hasColumn('rfqs', 'project_id')) {
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};

