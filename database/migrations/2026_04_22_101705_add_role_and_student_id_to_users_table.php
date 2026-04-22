<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('password');
            $table->foreignId('student_id')
                ->nullable()
                ->unique()
                ->after('role')
                ->constrained()
                ->cascadeOnDelete();
        });

        $now = now();

        DB::table('users')
            ->whereNull('role')
            ->update([
                'role' => 'admin',
                'updated_at' => $now,
            ]);

        $students = DB::table('students')
            ->select(['id', 'name', 'nis'])
            ->orderBy('id')
            ->get();

        foreach ($students as $student) {
            $existingStudentUser = DB::table('users')
                ->where('student_id', $student->id)
                ->first();

            if ($existingStudentUser !== null) {
                DB::table('users')
                    ->where('id', $existingStudentUser->id)
                    ->update([
                        'name' => $student->name,
                        'role' => 'student',
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $normalizedNis = Str::of((string) $student->nis)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '.')
                ->trim('.')
                ->value();

            if ($normalizedNis === '') {
                $normalizedNis = (string) $student->id;
            }

            $email = 'siswa.'.$normalizedNis.'@absensi.local';

            if (DB::table('users')->where('email', $email)->exists()) {
                $email = 'siswa.'.$student->id.'@absensi.local';
            }

            DB::table('users')->insert([
                'name' => $student->name,
                'email' => $email,
                'email_verified_at' => $now,
                'password' => Hash::make((string) $student->nis),
                'role' => 'student',
                'student_id' => $student->id,
                'remember_token' => Str::random(10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn('role');
        });
    }
};
