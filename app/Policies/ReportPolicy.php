<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Policy class for Report authorization.
 * Controls who can view, create, update, delete, and manage reports.
 */
class ReportPolicy
{
    /**
     * Anyone authenticated can view reports list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone authenticated can view a specific report.
     */
    public function view(User $user, Report $report): bool
    {
        return true;
    }

    /**
     * Only citizens can create reports.
     */
    public function create(User $user): bool
    {
        return $user->isCitizen();
    }

    /**
     * Citizens can update only their own pending reports.
     * Officers/admins can update any report.
     */
    public function update(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOfficer()) {
            return $report->assigned_to === $user->id;
        }

        return $user->id === $report->user_id && $report->status === 'pending';
    }

    /**
     * Citizens can delete only their own pending reports.
     * Admins can delete any report.
     */
    public function delete(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $report->user_id && $report->status === 'pending';
    }

    /**
     * Only officers and admins can update report status.
     */
    public function updateStatus(User $user, Report $report): bool
    {
        return $user->isAdmin() || ($user->isOfficer() && (! $report->assigned_to || $report->assigned_to === $user->id));
    }

    /**
     * Only admins can assign officers to reports.
     */
    public function assign(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }

    /**
     * Citizens and admins can post comments on reports.
     */
    public function comment(User $user, Report $report): bool
    {
        return $user->isCitizen() || $user->isAdmin();
    }
}
