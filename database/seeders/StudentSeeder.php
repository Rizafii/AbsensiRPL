<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

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
            Student::query()->updateOrCreate(
                ['nis' => $student['nis']],
                $student,
            );
        }
    }
}
