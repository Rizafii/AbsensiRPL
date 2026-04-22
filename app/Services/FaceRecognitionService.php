<?php

namespace App\Services;

class FaceRecognitionService
{
    private const DESCRIPTOR_LENGTH = 128;

    private const MATCH_MAX_DISTANCE = 0.55;

    /**
     * @return array{verified: bool, message?: string, distance?: float}
     */
    public function verifyDescriptor(string $capturedDescriptorJson, mixed $savedDescriptor): array
    {
        $captured = $this->normalizeDescriptorFromJson($capturedDescriptorJson);

        if ($captured === null) {
            return [
                'verified' => false,
                'message' => 'Data verifikasi wajah tidak valid. Silakan ulangi verifikasi.',
            ];
        }

        $stored = $this->normalizeDescriptor($savedDescriptor);

        if ($stored === null) {
            return [
                'verified' => false,
                'message' => 'Template wajah siswa belum valid. Silakan daftar ulang template wajah.',
            ];
        }

        $distance = $this->computeEuclideanDistance($captured, $stored);

        if ($distance > self::MATCH_MAX_DISTANCE) {
            return [
                'verified' => false,
                'message' => 'Wajah tidak cocok dengan data terdaftar.',
                'distance' => $distance,
            ];
        }

        return [
            'verified' => true,
            'distance' => $distance,
        ];
    }

    /**
     * @return array<int, float>|null
     */
    public function normalizeDescriptorFromJson(string $descriptorJson): ?array
    {
        $decoded = json_decode($descriptorJson, true);

        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizeDescriptor($decoded);
    }

    /**
     * @return array<int, float>|null
     */
    private function normalizeDescriptor(mixed $descriptor): ?array
    {
        if (! is_array($descriptor)) {
            return null;
        }

        if (count($descriptor) !== self::DESCRIPTOR_LENGTH) {
            return null;
        }

        $normalized = [];

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return null;
            }

            $floatValue = (float) $value;

            if (! is_finite($floatValue)) {
                return null;
            }

            $normalized[] = $floatValue;
        }

        return $normalized;
    }

    /**
     * @param  array<int, float>  $left
     * @param  array<int, float>  $right
     */
    private function computeEuclideanDistance(array $left, array $right): float
    {
        $sum = 0.0;

        for ($index = 0; $index < self::DESCRIPTOR_LENGTH; $index++) {
            $difference = $left[$index] - $right[$index];
            $sum += $difference ** 2;
        }

        return sqrt($sum);
    }
}
