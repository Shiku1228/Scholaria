<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
            'course_id'   => ['required', 'integer', 'exists:courses,id'],
            'enrolled_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Please select a student.',
            'course_id.required'  => 'Please select a course.',
        ];
    }
}
