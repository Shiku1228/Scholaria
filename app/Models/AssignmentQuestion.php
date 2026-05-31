<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentQuestion extends Model
{
    protected $fillable = [
        'assignment_id',
        'question_text',
        'points',
        'order',
    ];

    public function assignment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function choices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssignmentChoice::class, 'question_id')->orderBy('order');
    }

    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssignmentAnswer::class, 'question_id');
    }

    public function correctChoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssignmentChoice::class, 'question_id')->where('is_correct', true);
    }
}
