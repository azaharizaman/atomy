<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nexus_sourcing_awards', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('quotation_id')->index();
            $table->ulid('vendor_id')->index();
            $table->ulid('purchase_order_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_sourcing_awards');
    }
};
