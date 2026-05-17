<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'due_date',
        'max_score',
        'type',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'type' => 'string',
    ];

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Submission::class, 'assignment_id');
    }

    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class, 'assignment_id');
    }

    public function isAssignment(): bool
    {
        return $this->type === 'assignment';
    }

    public function isQuiz(): bool
    {
        return $this->type === 'quiz';
    }

    public function isExam(): bool
    {
        return $this->type === 'exam';
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'assignment' => 'Assignment',
            'quiz' => 'Quiz',
            'exam' => 'Exam',
            default => 'Assignment',
        };
    }
}
