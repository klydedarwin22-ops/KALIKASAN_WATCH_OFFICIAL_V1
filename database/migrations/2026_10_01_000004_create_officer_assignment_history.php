<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('estimated_due_at')->nullable();
        });

        Schema::create('report_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['assigned_to', 'created_at']);
        });

        $reports = DB::table('reports')
            ->whereNotNull('assigned_to')
            ->get(['id', 'assigned_to', 'updated_at', 'created_at']);

        foreach ($reports as $report) {
            $assignedAt = $report->updated_at ?? $report->created_at;

            DB::table('reports')->where('id', $report->id)->update(['assigned_at' => $assignedAt]);
            DB::table('report_assignment_histories')->insert([
                'report_id' => $report->id,
                'previous_officer_id' => null,
                'assigned_to' => $report->assigned_to,
                'assigned_by' => null,
                'created_at' => $assignedAt,
                'updated_at' => $assignedAt,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_assignment_histories');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'estimated_due_at']);
        });
    }
};