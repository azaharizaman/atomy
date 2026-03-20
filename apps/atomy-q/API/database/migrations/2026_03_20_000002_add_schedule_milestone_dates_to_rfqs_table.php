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
            if (! Schema::hasColumn('rfqs', 'expected_award_at')) {
                $table->timestamp('expected_award_at')->nullable()->after('closing_date');
            }
            if (! Schema::hasColumn('rfqs', 'technical_review_due_at')) {
                $table->timestamp('technical_review_due_at')->nullable()->after('expected_award_at');
            }
            if (! Schema::hasColumn('rfqs', 'financial_review_due_at')) {
                $table->timestamp('financial_review_due_at')->nullable()->after('technical_review_due_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table): void {
            if (Schema::hasColumn('rfqs', 'financial_review_due_at')) {
                $table->dropColumn('financial_review_due_at');
            }
            if (Schema::hasColumn('rfqs', 'technical_review_due_at')) {
                $table->dropColumn('technical_review_due_at');
            }
            if (Schema::hasColumn('rfqs', 'expected_award_at')) {
                $table->dropColumn('expected_award_at');
            }
        });
    }
};
