<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service class encapsulating all report-related business logic.
 * Keeps controllers thin by handling creation, updates, filtering, and file management.
 */
class ReportService
{
    /**
     * Create a new environmental report.
     *
     * @param  array  $data  Validated report data
     * @param  UploadedFile|null  $image  Optional image upload
     * @return Report
     */
    public function createReport(array $data, ?UploadedFile $image = null): Report
    {
        if ($image) {
            $data['image_path'] = $this->storeImage($image);
        }

        return Report::create($data);
    }

    /**
     * Update an existing report.
     *
     * @param  Report  $report  The report to update
     * @param  array  $data  Validated update data
     * @param  UploadedFile|null  $image  Optional new image
     * @return Report
     */
    public function updateReport(Report $report, array $data, ?UploadedFile $image = null): Report
    {
        if ($image) {
            // Delete old image if exists
            $this->deleteImage($report->image_path);
            $data['image_path'] = $this->storeImage($image);
        }

        $report->update($data);

        return $report->fresh();
    }

    /**
     * Delete a report and its associated image.
     */
    public function deleteReport(Report $report): bool
    {
        $this->deleteImage($report->image_path);

        return $report->delete();
    }

    /**
     * Get paginated and filtered reports.
     *
     * @param  array  $filters  Associative array of filters (status, category, severity, search)
     * @param  int  $perPage  Number of results per page
     * @return LengthAwarePaginator
     */
    public function getFilteredReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Report::with(['user', 'assignedOfficer']);

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->category($filters['category']);
        }

        if (! empty($filters['severity'])) {
            $query->severity($filters['severity']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query
            ->orderByRaw("CASE severity WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Update the status of a report (officer/admin action).
     */
    public function updateStatus(Report $report, string $status, ?int $estimatedDaysToFinish = null, bool $isCompleted = false, ?UploadedFile $completionProof = null, bool $removeCompletionProof = false, ?string $rejectionReason = null): Report
    {
        $data = [
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? $rejectionReason : null,
        ];

        if ($estimatedDaysToFinish !== null) {
            $data['estimated_days_to_finish'] = $estimatedDaysToFinish;
            $data['estimated_due_at'] = now()->addDays($estimatedDaysToFinish);
        }

        if ($completionProof) {
            $this->deleteImage($report->completion_proof);
            $data['completion_proof'] = $this->storeImage($completionProof, 'completion_proofs');
        } elseif ($removeCompletionProof) {
            $this->deleteImage($report->completion_proof);
            $data['completion_proof'] = null;
        }

        $data['is_completed'] = $isCompleted;

        $report->update($data);

        return $report->fresh();
    }

    /**
     * Assign an officer to a report.
     */
    public function assignOfficer(Report $report, int $officerId, ?int $assignedBy = null): Report
    {
        return DB::transaction(function () use ($report, $officerId, $assignedBy) {
            $previousOfficerId = $report->assigned_to;

            $report->update([
                'assigned_to' => $officerId,
                'assigned_at' => now(),
                'estimated_days_to_finish' => null,
                'estimated_due_at' => null,
                'status' => 'investigating',
                'rejection_reason' => null,
            ]);

            $report->assignmentHistory()->create([
                'previous_officer_id' => $previousOfficerId,
                'assigned_to' => $officerId,
                'assigned_by' => $assignedBy,
            ]);

            return $report->fresh();
        });
    }

    /**
     * Store an uploaded image with a unique filename.
     */
    private function storeImage(UploadedFile $image, string $directory = 'reports'): string
    {
        $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs($directory, $filename, 'public');
    }

    /**
     * Delete an image from storage.
     */
    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
