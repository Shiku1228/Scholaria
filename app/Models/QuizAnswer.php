<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $table = 'quiz_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'is_correct',
        'points_earned',
        'auto_score',
        'is_overridden',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'auto_score' => 'integer',
        'is_overridden' => 'boolean',
    ];

    public function attempt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    public function isCorrect(): bool
    {
        return $this->is_correct === true;
    }
}
