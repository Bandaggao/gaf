<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'teacher_id',
    'teaching_assignment_id',
    'subject_id',
    'section_id',
    'session_date',
    'start_time',
    'end_time',
    'session_qr_token',
    'expires_at',
    'absent_processed',
    'closed_at',
])]
class ClassSession extends Model
{
    protected $appends = ['status'];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'absent_processed' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isClosed() && ! $this->isExpired();
    }

    public function getStatusAttribute(): string
    {
        if ($this->isClosed()) {
            return 'closed';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'active';
    }
}
