<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('attendance report shows human friendly status labels', function () {
    $user = User::factory()->create();
    $selectedDate = Carbon::create(2026, 4, 21, 8, 0, 0, 'Asia/Jakarta');

    Carbon::setTestNow($selectedDate);

    $attendanceCases = [
        [
            'student' => Student::query()->create([
                'name' => 'Siswa Hadir',
                'nis' => 'NIS-2001',
                'fingerprint_id' => 2001,
            ]),
            'status' => Attendance::STATUS_ARRIVED,
            'check_in' => '07:00:00',
            'check_out' => '14:00:00',
            'label' => 'Hadir Tepat Waktu',
            'color' => 'green',
        ],
        [
            'student' => Student::query()->create([
                'name' => 'Siswa Telat',
                'nis' => 'NIS-2002',
                'fingerprint_id' => 2002,
            ]),
            'status' => Attendance::STATUS_LATE,
            'check_in' => '07:15:00',
            'check_out' => '14:00:00',
            'label' => 'Terlambat',
            'color' => 'orange',
        ],
        [
            'student' => Student::query()->create([
                'name' => 'Siswa Pulang',
                'nis' => 'NIS-2003',
                'fingerprint_id' => 2003,
            ]),
            'status' => Attendance::STATUS_DEPARTED,
            'check_in' => '07:00:00',
            'check_out' => '14:05:00',
            'label' => 'Pulang',
            'color' => 'blue',
        ],
        [
            'student' => Student::query()->create([
                'name' => 'Siswa Cepat',
                'nis' => 'NIS-2004',
                'fingerprint_id' => 2004,
            ]),
            'status' => Attendance::STATUS_EARLY_LEAVE,
            'check_in' => '07:00:00',
            'check_out' => '13:15:00',
            'label' => 'Pulang Cepat',
            'color' => 'purple',
        ],
    ];

    foreach ($attendanceCases as $attendanceCase) {
        $attendance = Attendance::query()->create([
            'student_id' => $attendanceCase['student']->id,
            'date' => $selectedDate->toDateString(),
            'check_in' => Carbon::parse($selectedDate->toDateString().' '.$attendanceCase['check_in'], 'Asia/Jakarta'),
            'check_out' => Carbon::parse($selectedDate->toDateString().' '.$attendanceCase['check_out'], 'Asia/Jakarta'),
            'status' => $attendanceCase['status'],
        ]);

        expect($attendance->statusLabel())->toBe($attendanceCase['label'])
            ->and($attendance->statusColor())->toBe($attendanceCase['color']);
    }

    $response = $this->actingAs($user)->get(route('reports.attendance'));

    $response->assertSuccessful()
        ->assertSee('Hadir Tepat Waktu')
        ->assertSee('Terlambat')
        ->assertSee('Pulang')
        ->assertSee('Pulang Cepat');
});
