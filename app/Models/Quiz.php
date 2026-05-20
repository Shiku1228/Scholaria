<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'start_date',
        'due_date',
        'max_score',
        'time_limit',
        'attempts_allowed',
        'shuffle_questions',
        'random_subset_count',
        'show_results',
        'feedback_type',
        'results_released',
        'is_published',
        'points',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'time_limit' => 'integer',
        'attempts_allowed' => 'integer',
        'shuffle_questions' => 'boolean',
        'random_subset_count' => 'integer',
        'show_results' => 'boolean',
        'results_released' => 'boolean',
        'is_published' => 'boolean',
        'points' => 'integer',
    ];

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id');
    }

    public function attempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }

    public function getDurationAttribute(): ?int
    {
        return $this->time_limit;
    }
}

