<?php

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('attendance settings can be updated for each school day', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('settings.attendance.update'), attendanceSchedulePayload([
        'monday_check_in_time' => '07:10',
        'monday_check_out_time' => '14:10',
        'friday_check_in_time' => '07:30',
        'friday_check_out_time' => '13:30',
    ]));

    $response->assertRedirect(route('settings.attendance.edit'));

    $setting = Setting::current();

    expect($setting->monday_check_in_time)->toBe('07:10:00')
        ->and($setting->monday_check_out_time)->toBe('14:10:00')
        ->and($setting->friday_check_in_time)->toBe('07:30:00')
        ->and($setting->friday_check_out_time)->toBe('13:30:00')
        ->and($setting->check_in_time)->toBe('07:10:00')
        ->and($setting->check_out_time)->toBe('14:10:00');
});

test('attendance settings reject check out time before check in time', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('settings.attendance.edit'))
        ->put(route('settings.attendance.update'), attendanceSchedulePayload([
            'wednesday_check_in_time' => '08:00',
            'wednesday_check_out_time' => '07:59',
        ]));

    $response->assertRedirect(route('settings.attendance.edit'));
    $response->assertSessionHasErrors('wednesday_check_out_time');
});

test('attendance api uses weekday schedule to determine late status', function () {
    Setting::current()->update([
        'monday_check_in_time' => '08:00:00',
        'monday_check_out_time' => '15:00:00',
        'late_tolerance' => 5,
    ]);

    $student = Student::query()->create([
        'name' => 'Siswa Senin',
        'nis' => 'NIS-1001',
        'fingerprint_id' => 1001,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 8, 6, 0, 'Asia/Jakarta'));

    $response = $this->postJson('/api/attendance', [
        'user_id' => $student->fingerprint_id,
    ], [
        'Authorization' => 'Bearer jgk0advefk90gj4ngin4290',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.status', Attendance::STATUS_LATE);

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
        'status' => Attendance::STATUS_LATE,
    ]);
});

test('attendance api rejects attendance on weekend', function () {
    $student = Student::query()->create([
        'name' => 'Siswa Weekend',
        'nis' => 'NIS-1002',
        'fingerprint_id' => 1002,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 18, 7, 30, 0, 'Asia/Jakarta'));

    $response = $this->postJson('/api/attendance', [
        'user_id' => $student->fingerprint_id,
    ], [
        'Authorization' => 'Bearer jgk0advefk90gj4ngin4290',
    ]);

    $response->assertUnprocessable()
        ->assertJson([
            'status' => 'error',
            'message' => 'Absensi hanya tersedia pada hari Senin sampai Jumat.',
        ]);

    $this->assertDatabaseCount('attendances', 0);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attendanceSchedulePayload(array $overrides = []): array
{
    return array_merge([
        'monday_check_in_time' => '07:00',
        'monday_check_out_time' => '14:00',
        'tuesday_check_in_time' => '07:00',
        'tuesday_check_out_time' => '14:00',
        'wednesday_check_in_time' => '07:00',
        'wednesday_check_out_time' => '14:00',
        'thursday_check_in_time' => '07:00',
        'thursday_check_out_time' => '14:00',
        'friday_check_in_time' => '07:00',
        'friday_check_out_time' => '14:00',
        'late_tolerance' => 0,
        'early_leave_tolerance' => 0,
    ], $overrides);
}
