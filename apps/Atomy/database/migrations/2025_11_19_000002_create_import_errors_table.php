<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('import_id')->index();
            $table->unsignedInteger('row_number')->nullable()->index();
            $table->string('field', 100)->nullable()->index();
            $table->string('severity', 20)->index(); // ERROR, WARNING, INFO
            $table->string('code', 100)->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestampsTz();

            $table->foreign('import_id')
                ->references('id')
                ->on('imports')
                ->onDelete('cascade');

            $table->index(['import_id', 'severity']);
            $table->index(['import_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
