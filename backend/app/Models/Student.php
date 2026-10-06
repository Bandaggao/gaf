<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'student_number', 'grade_level', 'section_id', 'student_type', 'parent_email', 'qr_token', 'daily_qr_token', 'daily_qr_date'])]
class Student extends Model
{
    protected function casts(): array
    {
        return [
            'daily_qr_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function gateEntries(): HasMany
    {
        return $this->hasMany(GateEntry::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function teachingAssignments(): BelongsToMany
    {
        return $this->belongsToMany(TeachingAssignment::class, 'student_enrollments')
            ->withPivot('enrollment_type')
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id');
    }
}
