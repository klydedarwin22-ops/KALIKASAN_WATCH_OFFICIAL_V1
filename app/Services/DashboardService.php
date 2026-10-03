<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service class for dashboard analytics and statistics.
 * Provides aggregated data for admin/officer dashboards.
 */
class DashboardService
{
    /**
     * Get overview statistics for the dashboard.
     *
     * @return array{total: int, pending: int, investigating: int, resolved: int, rejected: int}
     */
    public function getOverviewStats(): array
    {
        $statusCounts = Report::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($statusCounts),
            'pending' => $statusCounts['pending'] ?? 0,
            'investigating' => $statusCounts['investigating'] ?? 0,
            'resolved' => $statusCounts['resolved'] ?? 0,
            'rejected' => $statusCounts['rejected'] ?? 0,
        ];
    }

    /**
     * Get reports grouped by category with counts.
     *
     * @return Collection
     */
    public function getReportsByCategory(): Collection
    {
        return Report::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get reports grouped by barangay (from the submitting user's barangay).
     *
     * @return Collection
     */
    public function getReportsByBarangay(): Collection
    {
        return Report::join('users', 'reports.user_id', '=', 'users.id')
            ->select('users.barangay', DB::raw('count(*) as count'))
            ->whereNotNull('users.barangay')
            ->groupBy('users.barangay')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get reports grouped by severity.
     *
     * @return Collection
     */
    public function getReportsBySeverity(): Collection
    {
        return Report::select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get();
    }

    /**
     * Get recent reports for the dashboard table.
     *
     * @param  int  $limit
     * @return Collection
     */
    public function getRecentReports(int $limit = 10, ?int $assignedTo = null): Collection
    {
        $query = Report::with(['user', 'assignedOfficer']);

        if ($assignedTo !== null) {
            $query->where('assigned_to', $assignedTo);
        }

        return $query
            ->orderByRaw("CASE severity WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get monthly report counts for the current year (for charts).
     *
     * @return Collection
     */
    public function getMonthlyReportCounts(): Collection
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return Report::select(
                DB::raw("CAST(strftime('%m', created_at) AS INTEGER) as month"),
                DB::raw('count(*) as count')
            )
                ->whereRaw("strftime('%Y', created_at) = ?", [now()->year])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        return Report::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as count')
        )
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get recently assigned reports for a specific officer.
     *
     * @param  \App\Models\User  $user
     * @param  int  $limit
     * @return Collection
     */
    public function getRecentAssignedReports(User $user, ?int $limit = null): Collection
    {
        $query = Report::with(['user', 'assignedOfficer'])
            ->where('assigned_to', $user->id)
            ->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get available officers for assignment.
     *
     * @return Collection
     */
    public function getAvailableOfficers(): Collection
    {
        return User::where('role', 'officer')->orderBy('name')->get();
    }

    public function getOfficerAccountability(): Collection
    {
        return User::where('role', 'officer')
            ->with('assignedReports')
            ->orderBy('name')
            ->get()
            ->map(function (User $officer) {
                $reports = $officer->assignedReports;
                $completedReports = $reports->filter(fn (Report $report) => $report->status === 'resolved' || $report->is_completed);
                $inProgressReports = $reports->filter(fn (Report $report) => $report->status === 'investigating' && ! $report->is_completed);
                $overdueReports = $reports->filter(fn (Report $report) =>
                    ! $report->is_completed
                    && ! in_array($report->status, ['resolved', 'rejected'], true)
                    && $report->estimated_due_at?->isPast()
                )->values();

                return (object) [
                    'officer' => $officer,
                    'assigned_count' => $reports->count(),
                    'completed_count' => $completedReports->count(),
                    'in_progress_count' => $inProgressReports->count(),
                    'overdue_count' => $overdueReports->count(),
                    'attention_count' => $overdueReports->count(),
                    'overdue_reports' => $overdueReports,
                ];
            });
    }
}
