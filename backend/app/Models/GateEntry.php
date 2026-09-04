<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'scan_date', 'scanned_at', 'qr_token_used'])]
class GateEntry extends Model
{
    protected function casts(): array
    {
        return [
            'scan_date' => 'date',
            'scanned_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
