<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Report model for environmental issue submissions.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string $category
 * @property string $severity
 * @property float $latitude
 * @property float $longitude
 * @property string|null $image_path
 * @property string $status
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property int|null $estimated_days_to_finish
 * @property \Illuminate\Support\Carbon|null $estimated_due_at
 * @property string|null $rejection_reason
 * @property bool $is_completed
 * @property string|null $completion_proof
 */
class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'severity',
        'latitude',
        'longitude',
        'image_path',
        'status',
        'assigned_to',
        'assigned_at',
        'estimated_days_to_finish',
        'estimated_due_at',
        'rejection_reason',
        'is_completed',
        'completion_proof',
    ];

    /**
     * Available report categories.
     */
    public const CATEGORIES = [
        'illegal_dumping' => 'Illegal Dumping',
        'flooding' => 'Flooding',
        'soil_erosion' => 'Soil Erosion',
        'other' => 'Other',
    ];

    /**
     * Available severity levels.
     */
    public const SEVERITIES = ['high', 'medium', 'low'];

    /**
     * Available report statuses.
     */
    public const STATUSES = ['pending', 'investigating', 'resolved', 'rejected'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'estimated_due_at' => 'datetime',
            'is_completed' => 'boolean',
        ];
    }

    /**
     * The user who submitted this report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The officer assigned to this report.
     */
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Comments on this report.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(ReportAssignmentHistory::class)->latest();
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get the human-readable category name.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get the status badge color for UI rendering.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'investigating' => 'blue',
            'resolved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the severity badge color for UI rendering.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray',
        };
    }
}
