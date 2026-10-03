<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Report;
use App\Models\User;
use App\Services\OfficerAssignmentNotifier;
use App\Services\ReportService;
use App\Services\ReportSeverityEstimator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller for managing environmental reports.
 * Handles CRUD operations, status updates, and officer assignment.
 * Business logic is delegated to ReportService.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportSeverityEstimator $severityEstimator,
        private readonly OfficerAssignmentNotifier $assignmentNotifier
    ) {}

    /**
     * Display a paginated, filterable list of reports.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category', 'severity', 'search']);

        if (Auth::user()->isAdmin() && $request->filled('assigned_to')) {
            $request->validate([
                'assigned_to' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'officer'))],
            ]);
            $filters['assigned_to'] = $request->integer('assigned_to');
        }

        // Citizens see only their own reports
        if (Auth::user()->isCitizen()) {
            $filters['user_id'] = Auth::id();
        }

        $reports = $this->reportService->getFilteredReports($filters);

        return view('reports.index', [
            'reports' => $reports,
            'categories' => Report::CATEGORIES,
            'statuses' => Report::STATUSES,
            'severities' => Report::SEVERITIES,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new report.
     */
    public function create(): View
    {
        $this->authorize('create', Report::class);

        return view('reports.create', [
            'categories' => Report::CATEGORIES,
        ]);
    }

    /**
     * Store a newly created report.
     * Rate limited to 5 submissions per minute per user.
     */
    public function store(StoreReportRequest $request): RedirectResponse
    {
        $this->authorize('create', Report::class);

        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['status'] = 'investigating';
        $data['assigned_to'] = null;

        $image = $request->file('image');

        $data['severity'] = $this->severityEstimator->estimate($data['category'], $data['description']);

        $this->reportService->createReport($data, $image);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Report submitted successfully for review.');
    }

    /**
     * Display a specific report with its comments.
     */
    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load(['user', 'assignedOfficer', 'comments.user']);

        $officers = [];
        if (Auth::user()->isAdmin()) {
            $officers = User::where('role', 'officer')
                ->whereNotNull('phone')
                ->when($report->assigned_to, fn ($query, $assignedTo) => $query->where('id', '!=', $assignedTo))
                ->orderBy('name')
                ->get();
        }

        return view('reports.show', [
            'report' => $report,
            'officers' => $officers,
            'statuses' => Report::STATUSES,
        ]);
    }

    /**
     * Show the form for editing a report.
     */
    public function edit(Report $report): View
    {
        $this->authorize('update', $report);

        return view('reports.edit', [
            'report' => $report,
            'categories' => Report::CATEGORIES,
            'severities' => Report::SEVERITIES,
        ]);
    }

    /**
     * Update the specified report.
     */
    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $this->reportService->updateReport(
            $report,
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Delete the specified report.
     */
    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $this->reportService->deleteReport($report);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    /**
     * Update the status of a report (officer/admin action).
     */
    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('updateStatus', $report);

        $shouldRemoveProof = $request->boolean('remove_completion_proof');
        $hasNewProof = $request->hasFile('completion_proof');
        $requiresProof = $request->boolean('is_completed') && ! $shouldRemoveProof && empty($report->completion_proof) && ! $hasNewProof;

        $completionProofRule = $requiresProof
            ? ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
            : ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'];

        $request->validate([
            'status' => ['required', 'in:' . implode(',', Report::STATUSES)],
            'estimated_days_to_finish' => ['nullable', 'integer', 'between:1,5'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'min:10', 'max:1000'],
            'is_completed' => ['nullable', 'boolean'],
            'completion_proof' => $completionProofRule,
            'remove_completion_proof' => ['nullable', 'boolean'],
        ]);

        $this->reportService->updateStatus(
            $report,
            $request->status,
            $request->input('estimated_days_to_finish'),
            $request->boolean('is_completed'),
            $request->file('completion_proof'),
            $shouldRemoveProof,
            $request->input('rejection_reason')
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report status updated to ' . ucfirst($request->status) . '.');
    }

    /**
     * Assign an officer to a report.
     */
    public function assign(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('assign', $report);

        $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'officer')->whereNotNull('phone')),
                Rule::notIn([$report->assigned_to]),
            ],
        ]);

        $officer = User::findOrFail($request->assigned_to);
        $report = $this->reportService->assignOfficer($report, $officer->id, Auth::id());
        $notified = $this->assignmentNotifier->send($officer, $report);

        return redirect()
            ->route('reports.show', $report)
            ->with(
                $notified ? 'success' : 'warning',
                $notified
                    ? 'Officer assigned and SMS notification sent.'
                    : 'Officer assigned, but SMS notification was not sent. Check the officer phone number and Twilio configuration.'
            );
    }

    /**
     * Store a comment on a report.
     */
    public function comment(StoreCommentRequest $request, Report $report): RedirectResponse
    {
        $this->authorize('comment', $report);

        $report->comments()->create([
            'user_id' => Auth::id(),
            'message' => $request->validated('message'),
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Comment added successfully.');
    }
}
