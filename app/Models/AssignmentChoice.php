<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentChoice extends Model
{
    protected $fillable = [
        'question_id',
        'choice_text',
        'is_correct',
        'order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssignmentQuestion::class, 'question_id');
    }
}
