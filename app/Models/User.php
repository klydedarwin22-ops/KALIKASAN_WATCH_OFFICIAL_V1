<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model for KALIKASAN WATCH.
 * Supports role-based access: citizen, officer, admin.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $role
 * @property string|null $barangay
 * @property bool $barangay_verified
 * @property string|null $barangay_id_path
 * @property string|null $selfie_path
 * @property array<int, float>|null $face_template
 * @property string|null $verification_notes
 * @property \Carbon\Carbon|null $verified_at
 * @property int|null $verified_by
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'barangay',
        'barangay_verified',
        'barangay_id_path',
        'selfie_path',
        'face_template',
        'verification_notes',
        'verified_at',
        'verified_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'face_template',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'barangay_verified' => 'boolean',
            'face_template' => 'encrypted:array',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Reports submitted by the user.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Reports assigned to this user (officer).
     */
    public function assignedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    /**
     * Comments posted by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is an officer.
     */
    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    /**
     * Check if user is a citizen.
     */
    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    /**
     * Check if user's barangay residency is verified.
     */
    public function isBarangayVerified(): bool
    {
        return $this->barangay_verified;
    }

    /**
     * The admin/officer who verified this user's barangay.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope: unverified citizens who need barangay verification.
     */
    public function scopeUnverified($query)
    {
        return $query->where('role', 'citizen')
                     ->where('barangay_verified', false);
    }
}
