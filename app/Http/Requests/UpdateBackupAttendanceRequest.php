<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'backup_attendance_enabled' => ['nullable', 'boolean'],
            'backup_attendance_radius_meters' => ['required', 'integer', 'min:1', 'max:5000'],
            'school_latitude' => [
                Rule::requiredIf($this->boolean('backup_attendance_enabled')),
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'school_longitude' => [
                Rule::requiredIf($this->boolean('backup_attendance_enabled')),
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_latitude.required' => 'Latitude sekolah wajib diisi saat absensi cadangan diaktifkan.',
            'school_longitude.required' => 'Longitude sekolah wajib diisi saat absensi cadangan diaktifkan.',
        ];
    }
}
