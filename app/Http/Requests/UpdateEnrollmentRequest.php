<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enrollmentId = $this->route('enrollment')?->id;

        return [
            'student_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!\App\Models\User::where('id', $value)
                        ->whereHas('roles', fn ($q) => $q->where('name', 'Student'))
                        ->exists()) {
                        $fail('The selected user must have the Student role.');
                    }
                },
            ],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'status'    => ['required', Rule::in(['active', 'completed', 'dropped', 'unenrolled'])],
            'enrolled_at' => ['nullable', 'date'],
            'course_id_student_unique' => [
                function ($attribute, $value, $fail) use ($enrollmentId) {
                    $studentId = (int) $this->input('student_id');
                    $courseId  = (int) $this->input('course_id');
                    $status    = (string) $this->input('status');

                    if ($studentId > 0 && $courseId > 0 && $status === 'active') {
                        $query = \App\Models\Enrollment::where('student_id', $studentId)
                            ->where('course_id', $courseId)
                            ->where('status', 'active');
                        if ($enrollmentId) {
                            $query->where('id', '!=', $enrollmentId);
                        }
                        if ($query->exists()) {
                            $fail('This student is already actively enrolled in the selected course.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Please select a student.',
            'course_id.required'  => 'Please select a course.',
            'status.required'     => 'Please select a status.',
        ];
    }
}
