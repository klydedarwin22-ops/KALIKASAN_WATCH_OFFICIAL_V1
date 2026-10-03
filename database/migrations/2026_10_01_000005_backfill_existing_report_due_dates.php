<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $reports = DB::table('reports')
            ->whereNotNull('assigned_to')
            ->whereNotNull('estimated_days_to_finish')
            ->whereNull('estimated_due_at')
            ->get(['id', 'assigned_at', 'updated_at', 'created_at', 'estimated_days_to_finish']);

        foreach ($reports as $report) {
            $assignedAt = $report->assigned_at ?? $report->updated_at ?? $report->created_at;
            $dueAt = Carbon::parse($assignedAt)->addDays($report->estimated_days_to_finish);

            DB::table('reports')->where('id', $report->id)->update([
                'estimated_due_at' => $dueAt,
            ]);
        }
    }

    public function down(): void
    {
        // Backfilled dates cannot be distinguished from dates set after this migration.
    }
};