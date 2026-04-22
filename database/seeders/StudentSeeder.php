<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            ['name' => 'Andi Pratama', 'nis' => '24001', 'fingerprint_id' => 1],
            ['name' => 'Budi Santoso', 'nis' => '24002', 'fingerprint_id' => 2],
            ['name' => 'Citra Lestari', 'nis' => '24003', 'fingerprint_id' => 3],
            ['name' => 'Dina Maharani', 'nis' => '24004', 'fingerprint_id' => 4],
            ['name' => 'Eko Saputra', 'nis' => '24005', 'fingerprint_id' => 5],
        ];

        foreach ($students as $student) {
            $savedStudent = Student::query()->updateOrCreate(
                ['nis' => $student['nis']],
                $student,
            );

            User::query()->updateOrCreate(
                ['student_id' => $savedStudent->id],
                [
                    'name' => $savedStudent->name,
                    'email' => 'siswa.'.$this->normalizedNisSegment($savedStudent->nis, $savedStudent->id).'@absensi.local',
                    'password' => Hash::make($savedStudent->nis),
                    'role' => User::ROLE_STUDENT,
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    private function normalizedNisSegment(string $nis, int $studentId): string
    {
        $normalizedNis = Str::of($nis)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        if ($normalizedNis !== '') {
            return $normalizedNis;
        }

        return (string) $studentId;
    }
}
