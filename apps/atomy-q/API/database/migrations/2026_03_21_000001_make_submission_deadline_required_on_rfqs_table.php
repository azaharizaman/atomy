<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('rfqs')->select(['id', 'created_at'])->whereNull('submission_deadline')->get();
        $fallback = Carbon::now();
        foreach ($rows as $row) {
            $base = $row->created_at !== null
                ? Carbon::parse($row->created_at)
                : $fallback;
            DB::table('rfqs')->where('id', $row->id)->update([
                'submission_deadline' => $base->copy()->addDays(14),
            ]);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rfqs ALTER COLUMN submission_deadline SET NOT NULL');
        } else {
            Schema::table('rfqs', function (Blueprint $table): void {
                $table->timestamp('submission_deadline')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rfqs ALTER COLUMN submission_deadline DROP NOT NULL');
        } else {
            Schema::table('rfqs', function (Blueprint $table): void {
                $table->timestamp('submission_deadline')->nullable()->change();
            });
        }
    }
};
