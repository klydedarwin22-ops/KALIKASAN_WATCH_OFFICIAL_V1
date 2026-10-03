<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for the admin/officer dashboard.
 * Displays analytics, statistics, and recent reports.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Display the dashboard with analytics data.
     * Admins/officers see full analytics; citizens see their personal stats.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isCitizen()) {
            return $this->citizenDashboard($user);
        }

        return $this->adminDashboard($user);
    }

    /**
     * Admin/officer dashboard with full analytics.
     */
    private function adminDashboard($user): View
    {
        $stats = $this->dashboardService->getOverviewStats();
        $reportsByCategory = $this->dashboardService->getReportsByCategory();
        $reportsByBarangay = $this->dashboardService->getReportsByBarangay();
        $reportsBySeverity = $this->dashboardService->getReportsBySeverity();
        $recentReports = $this->dashboardService->getRecentReports();
        $monthlyReports = $this->dashboardService->getMonthlyReportCounts();
        $recentAssignedReports = $user->isOfficer()
            ? $this->dashboardService->getRecentAssignedReports($user)
            : collect();
        $officerAccountability = $this->dashboardService->getOfficerAccountability();

        if ($user->isOfficer()) {
            $officerAccountability = $officerAccountability
                ->filter(fn ($entry) => $entry->officer->is($user))
                ->values();
        }

        return view('dashboard', [
            'stats' => $stats,
            'reportsByCategory' => $reportsByCategory,
            'reportsByBarangay' => $reportsByBarangay,
            'reportsBySeverity' => $reportsBySeverity,
            'recentReports' => $recentReports,
            'monthlyReports' => $monthlyReports,
            'recentAssignedReports' => $recentAssignedReports,
            'officerAccountability' => $officerAccountability,
        ]);
    }

    /**
     * Citizen dashboard showing personal report summary.
     */
    private function citizenDashboard($user): View
    {
        $myReports = $user->reports()->latest()->limit(10)->get();
        $myStats = [
            'total' => $user->reports()->count(),
            'pending' => $user->reports()->where('status', 'pending')->count(),
            'investigating' => $user->reports()->where('status', 'investigating')->count(),
            'resolved' => $user->reports()->where('status', 'resolved')->count(),
        ];

        return view('dashboard', [
            'stats' => $myStats + ['rejected' => $user->reports()->where('status', 'rejected')->count()],
            'recentReports' => $myReports,
            'reportsByCategory' => collect(),
            'reportsByBarangay' => collect(),
            'reportsBySeverity' => collect(),
            'monthlyReports' => collect(),
            'officerAccountability' => collect(),
        ]);
    }
}
