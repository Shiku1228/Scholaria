<?php

namespace App\Models;

use App\Traits\LogsTransactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, LogsTransactions;

    protected $fillable = [
        'course_number',
        'course_code',
        'title',
        'description',
        'semester',
        'school_year',
        'start_date',
        'end_date',
        'days_pattern',
        'start_time',
        'end_time',
        'teacher_id',
        'cover_image',
        'overview',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assignment::class, 'course_id');
    }

    public function quizzes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quiz::class, 'course_id');
    }

    public function exams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Exam::class, 'course_id');
    }

    public function announcements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Announcement::class, 'course_id');
    }

    public function resources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseResource::class, 'course_id');
    }

    public function discussions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseDiscussion::class, 'course_id');
    }

    public function officeHours(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OfficeHour::class, 'course_id');
    }

    public function chatGroup(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChatGroup::class, 'course_id');
    }

    public function setCourseNumberAttribute($value): void
    {
        $this->attributes['course_number'] = strtoupper((string) $value);
    }
}
