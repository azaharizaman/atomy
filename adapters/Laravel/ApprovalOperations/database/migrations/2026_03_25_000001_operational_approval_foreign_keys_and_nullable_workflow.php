<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_approval_instances', function (Blueprint $table): void {
            $table->string('workflow_instance_id', 256)->nullable()->change();
        });

        Schema::table('operational_approval_instances', function (Blueprint $table): void {
            $table->foreign('template_id')
                ->references('id')
                ->on('operational_approval_templates')
                ->restrictOnDelete();
        });

        Schema::table('operational_approval_comments', function (Blueprint $table): void {
            $table->foreign('instance_id')
                ->references('id')
                ->on('operational_approval_instances')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('operational_approval_comments', function (Blueprint $table): void {
            $table->dropForeign(['instance_id']);
        });

        Schema::table('operational_approval_instances', function (Blueprint $table): void {
            $table->dropForeign(['template_id']);
        });

        DB::table('operational_approval_instances')
            ->whereNull('workflow_instance_id')
            ->update(['workflow_instance_id' => (string) Str::ulid()]);

        Schema::table('operational_approval_instances', function (Blueprint $table): void {
            $table->string('workflow_instance_id', 256)->nullable(false)->change();
        });
    }
};
