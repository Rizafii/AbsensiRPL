<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = $student?->id;
        $userId = $student?->user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', Rule::unique('students', 'nis')->ignore($studentId)],
            'fingerprint_id' => ['required', 'integer', 'min:1', Rule::unique('students', 'fingerprint_id')->ignore($studentId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:4'],
        ];
    }
}
