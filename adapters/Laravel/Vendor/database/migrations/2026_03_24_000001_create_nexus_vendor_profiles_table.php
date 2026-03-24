<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nexus_vendor_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('party_id')->index();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('nexus_vendor_profiles');
    }
};
