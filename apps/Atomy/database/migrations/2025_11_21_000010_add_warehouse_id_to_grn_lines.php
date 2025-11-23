<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add warehouse_id to goods_receipt_note_lines
        if (Schema::hasTable('goods_receipt_note_lines')) {
            Schema::table('goods_receipt_note_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('goods_receipt_note_lines', 'warehouse_id')) {
                    $table->ulid('warehouse_id')->nullable()->after('unit');
                    // $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('goods_receipt_note_lines')) {
            Schema::table('goods_receipt_note_lines', function (Blueprint $table) {
                if (Schema::hasColumn('goods_receipt_note_lines', 'warehouse_id')) {
                    $table->dropForeign(['warehouse_id']);
                    $table->dropColumn('warehouse_id');
                }
            });
        }
    }
};
