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
            'course_number'      => ['required', 'string', 'max:50', 'regex:/^\d+$/', Rule::unique('courses', 'course_number')->ignore($courseId)],
            'course_code'        => ['required', 'string', 'max:50'],
            'course_title'       => ['required', 'string', 'max:255'],
            'course_description' => ['nullable', 'string', 'max:1000'],
            'semester'           => ['required', Rule::in(['first', 'second', 'summer'])],
            'school_year'        => ['required', 'string', 'max:20'],
            'start_date'         => ['required', 'date'],
            'end_date'           => ['required', 'date', 'after_or_equal:start_date'],
            'class_time_start'   => ['required', 'date_format:H:i,H:i:s'],
            'class_time_end'     => ['required', 'date_format:H:i,H:i:s'],
            'class_days'         => ['required', 'array', 'min:1'],
            'class_days.*'       => ['string', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'teacher_id'         => [
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
            'course_number.required'       => 'Course Number/CN is required.',
            'course_number.regex'          => 'Course Number/CN must contain digits only. Example: 40190',
            'course_number.unique'         => 'Course Number/CN already exists. Please use a different Course Number/CN.',
            'course_number.max'            => 'Course Number/CN must not exceed 50 characters.',
            'course_code.required'         => 'Course Code is required. Example: ITE101',
            'course_code.max'              => 'Course Code must not exceed 50 characters.',
            'course_title.required'        => 'Course Title is required.',
            'course_description.required'  => 'Course Description is required.',
            'semester.required'            => 'Semester is required.',
            'school_year.required'         => 'School Year is required.',
            'start_date.required'          => 'Start Date is required.',
            'end_date.required'            => 'End Date is required.',
            'end_date.after_or_equal'      => 'End Date must be on or after the Start Date.',
            'class_time_start.required'    => 'Class Start Time is required.',
            'class_time_start.date_format' => 'Class Start Time must be a valid time (HH:MM).',
            'class_time_end.required'      => 'Class End Time is required.',
            'class_time_end.date_format'   => 'Class End Time must be a valid time (HH:MM).',
            'class_time_end.after'         => 'Class End Time must be after Class Start Time.',
            'class_days.required'          => 'At least one class day must be selected.',
            'class_days.min'               => 'At least one class day must be selected.',
            'teacher_id.required'          => 'An assigned teacher is required.',
        ];
    }
}
