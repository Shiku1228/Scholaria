<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeHourQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_hour_id',
        'student_id',
        'question',
        'answer',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function officeHour(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OfficeHour::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
