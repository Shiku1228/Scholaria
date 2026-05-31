<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentAnswer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'selected_choice_id',
        'essay_answer',
        'score',
        'feedback',
    ];

    public function submission(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssignmentQuestion::class, 'question_id');
    }

    public function selectedChoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssignmentChoice::class, 'selected_choice_id');
    }
}
