<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'course_number' => ['required', 'string', 'max:50', Rule::unique('courses', 'course_number')->ignore($courseId)],
            'course_title' => ['required', 'string', 'max:255'],
            'course_description' => ['nullable', 'string', 'max:1000'],
            'semester' => ['required', Rule::in(['first', 'second', 'summer'])],
            'school_year' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'class_time_start' => ['nullable', 'date_format:H:i'],
            'class_time_end' => ['nullable', 'date_format:H:i', 'after:class_time_start'],
            'class_days' => ['nullable', 'array'],
            'class_days.*' => ['string', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'teacher_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!\App\Models\User::where('id', $value)->whereHas('roles', fn ($q) => $q->where('name', 'Teacher'))->exists()) {
                        $fail('The selected teacher must have the Teacher role.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'course_number.unique' => 'Course number already exists. Please use a different course number.',
        ];
    }
}
