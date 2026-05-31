<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $table = 'quiz_attempts';

    protected $fillable = [
        'quiz_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'graded_at',
        'graded_by',
        'score',
        'status',
        'question_ids',
    ];

    protected $casts = [
        'quiz_id' => 'integer',
        'student_id' => 'integer',
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'graded_by' => 'integer',
        'score' => 'integer',
        'question_ids' => 'array',
    ];

    public function quiz(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded'], true);
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getTimeSpentMinutes(): int
    {
        if (!$this->started_at) {
            return 0;
        }
        if (!$this->submitted_at) {
            return now()->diffInMinutes($this->started_at);
        }
        return $this->submitted_at->diffInMinutes($this->started_at);
    }

    public function getPercentageScore(): float
    {
        $maxScore = $this->quiz->points ?? $this->quiz->max_score ?? 100;
        if (!$this->score || !$maxScore) {
            return 0;
        }
        return round(($this->score / $maxScore) * 100, 2);
    }
}
