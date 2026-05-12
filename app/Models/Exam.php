<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'exam_type',
        'title',
        'description',
        'exam_date',
        'duration',
        'max_score',
        'location',
        'instructions',
        'is_published',
    ];

    protected $casts = [
        'exam_date' => 'datetime',
        'duration' => 'integer',
        'max_score' => 'integer',
        'is_published' => 'boolean',
    ];

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'exam_id')->orderBy('order');
    }

    public function attempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentExamAttempt::class, 'exam_id');
    }

    public function isScheduled(): bool
    {
        return $this->exam_type === 'scheduled';
    }

    public function isOnline(): bool
    {
        return $this->exam_type === 'online';
    }

    public function isPublished(): bool
    {
        return $this->is_published;
    }

    public function hasQuestions(): bool
    {
        return $this->questions()->count() > 0;
    }

    public function getTotalPoints(): int
    {
        return $this->questions()->sum('points');
    }

    public function getSubmissionCount(): int
    {
        return $this->attempts()->whereIn('status', ['submitted', 'graded'])->count();
    }
}
