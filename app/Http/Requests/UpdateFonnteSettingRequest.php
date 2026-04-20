<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFonnteSettingRequest extends FormRequest
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
        $checkInActive = $this->boolean('check_in_is_active');
        $checkOutActive = $this->boolean('check_out_is_active');

        return [
            'check_in_account_name' => ['nullable', 'string', 'max:100'],
            'check_in_base_url' => ['required', 'url', 'max:255'],
            'check_in_token' => [
                Rule::requiredIf($checkInActive),
                'nullable',
                'string',
                'max:2000',
            ],
            'check_in_parent_group_target' => [
                Rule::requiredIf($checkInActive),
                'nullable',
                'string',
                'max:255',
            ],
            'check_in_timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'check_in_is_active' => ['nullable', 'boolean'],

            'check_out_account_name' => ['nullable', 'string', 'max:100'],
            'check_out_base_url' => ['required', 'url', 'max:255'],
            'check_out_token' => [
                Rule::requiredIf($checkOutActive),
                'nullable',
                'string',
                'max:2000',
            ],
            'check_out_parent_group_target' => [
                Rule::requiredIf($checkOutActive),
                'nullable',
                'string',
                'max:255',
            ],
            'check_out_timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'check_out_is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'check_in_account_name' => 'nama akun Fonnte masuk',
            'check_in_base_url' => 'base URL Fonnte masuk',
            'check_in_token' => 'token Fonnte masuk',
            'check_in_parent_group_target' => 'target grup orang tua untuk absensi masuk',
            'check_in_timeout' => 'timeout Fonnte masuk',
            'check_out_account_name' => 'nama akun Fonnte pulang',
            'check_out_base_url' => 'base URL Fonnte pulang',
            'check_out_token' => 'token Fonnte pulang',
            'check_out_parent_group_target' => 'target grup orang tua untuk absensi pulang',
            'check_out_timeout' => 'timeout Fonnte pulang',
        ];
    }
}
