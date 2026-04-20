<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceSettingRequest extends FormRequest
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
        return [
            'monday_check_in_time' => ['required', 'date_format:H:i'],
            'monday_check_out_time' => ['required', 'date_format:H:i'],
            'tuesday_check_in_time' => ['required', 'date_format:H:i'],
            'tuesday_check_out_time' => ['required', 'date_format:H:i'],
            'wednesday_check_in_time' => ['required', 'date_format:H:i'],
            'wednesday_check_out_time' => ['required', 'date_format:H:i'],
            'thursday_check_in_time' => ['required', 'date_format:H:i'],
            'thursday_check_out_time' => ['required', 'date_format:H:i'],
            'friday_check_in_time' => ['required', 'date_format:H:i'],
            'friday_check_out_time' => ['required', 'date_format:H:i'],
            'late_tolerance' => ['required', 'integer', 'min:0'],
            'early_leave_tolerance' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $schoolDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

                foreach ($schoolDays as $day) {
                    $checkInTime = $this->input($day.'_check_in_time');
                    $checkOutTime = $this->input($day.'_check_out_time');

                    if (! is_string($checkInTime) || ! is_string($checkOutTime)) {
                        continue;
                    }

                    if ($checkOutTime <= $checkInTime) {
                        $validator->errors()->add(
                            $day.'_check_out_time',
                            'Jam pulang harus setelah jam masuk.'
                        );
                    }
                }
            },
        ];
    }
}
