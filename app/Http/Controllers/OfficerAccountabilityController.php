<?php

namespace App\Http\Controllers;

use App\Models\ReportAssignmentHistory;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\OfficerAssignmentNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfficerAccountabilityController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly OfficerAssignmentNotifier $notifier
    ) {}

    public function history(User $officer): View
    {
        abort_unless($officer->isOfficer(), 404);

        $assignments = ReportAssignmentHistory::with(['report', 'previousOfficer', 'assignedOfficer', 'assignedBy'])
            ->where('assigned_to', $officer->id)
            ->orWhere('previous_officer_id', $officer->id)
            ->latest()
            ->paginate(20);

        return view('officers.assignment-history', [
            'officer' => $officer,
            'assignments' => $assignments,
        ]);
    }

    public function notify(User $officer): RedirectResponse
    {
        abort_unless($officer->isOfficer(), 404);

        $summary = $this->summaryFor($officer);
        $message = "KALIKASAN WATCH: You have {$summary->assigned_count} assigned reports, {$summary->in_progress_count} in progress, and {$summary->overdue_count} overdue. Please review your dashboard.";

        if ($this->notifier->sendMessage($officer, $message)) {
            return redirect()->route('dashboard')->with('success', "Notification sent to {$officer->name}.");
        }

        return redirect()->route('dashboard')->with('warning', "Could not notify {$officer->name}. Check their phone number and Twilio configuration.");
    }

    public function escalate(User $officer): RedirectResponse
    {
        abort_unless($officer->isOfficer(), 404);

        $supervisorPhone = config('services.twilio.supervisor_phone');
        if (! $supervisorPhone) {
            return redirect()->route('dashboard')->with('warning', 'Supervisor SMS is not configured. Set SUPERVISOR_PHONE to enable escalation.');
        }

        $summary = $this->summaryFor($officer);
        $message = "KALIKASAN WATCH escalation: {$officer->name} has {$summary->overdue_count} overdue reports out of {$summary->assigned_count} assigned.";

        if ($this->notifier->sendToPhone($supervisorPhone, $message)) {
            return redirect()->route('dashboard')->with('success', "Escalation sent for {$officer->name}.");
        }

        return redirect()->route('dashboard')->with('warning', 'Escalation SMS was not sent. Check Twilio configuration.');
    }

    private function summaryFor(User $officer): object
    {
        return $this->dashboardService->getOfficerAccountability()
            ->first(fn ($summary) => $summary->officer->is($officer));
    }
}