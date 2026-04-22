<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBackupAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'face_descriptor' => ['required', 'json'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Lokasi Anda belum didapatkan. Silakan aktifkan geolocation terlebih dahulu.',
            'longitude.required' => 'Lokasi Anda belum didapatkan. Silakan aktifkan geolocation terlebih dahulu.',
            'face_descriptor.required' => 'Verifikasi wajah harus berhasil sebelum absensi dikirim.',
            'face_descriptor.json' => 'Data verifikasi wajah tidak valid. Silakan ulangi verifikasi.',
        ];
    }
}
