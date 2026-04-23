<?php

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('creating student from admin page also creates student login account', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $studentEmail = 'siswa.nis.akun.001@absensi.local';

    $response = $this->actingAs($admin)->post(route('students.store'), [
        'name' => 'Siswa Akun',
        'nis' => 'NIS-AKUN-001',
        'fingerprint_id' => 1201,
        'email' => $studentEmail,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('students.index'));

    $student = Student::query()->where('nis', 'NIS-AKUN-001')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'student_id' => $student->id,
        'role' => User::ROLE_STUDENT,
        'email' => $studentEmail,
    ]);
});

test('admin dashboard and student dashboard are protected by role middleware', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    [$student, $studentUser] = createStudentUserForBackupTests();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('student.attendance.dashboard'))
        ->assertForbidden();

    $this->actingAs($studentUser)
        ->get(route('student.attendance.dashboard'))
        ->assertSuccessful()
        ->assertSee($student->name);

    $this->actingAs($studentUser)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('admin can activate backup attendance from dashboard', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)->put(route('dashboard.backup-attendance.update'), [
        'backup_attendance_enabled' => '1',
        'backup_attendance_radius_meters' => 150,
        'school_latitude' => -6.2000000,
        'school_longitude' => 106.8166667,
    ]);

    $response->assertRedirect(route('dashboard'));

    $setting = Setting::current();

    expect($setting->backup_attendance_enabled)->toBeTrue()
        ->and($setting->backup_attendance_radius_meters)->toBe(150)
        ->and($setting->school_latitude)->toBe(-6.2)
        ->and($setting->school_longitude)->toBe(106.8166667);
});

test('student cannot submit backup attendance when feature is disabled', function () {
    [$student, $studentUser] = createStudentUserForBackupTests();

    Setting::current()->update([
        'backup_attendance_enabled' => false,
        'backup_attendance_radius_meters' => 100,
        'school_latitude' => -6.2000000,
        'school_longitude' => 106.8166667,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 50, 0, 'Asia/Jakarta'));

    $response = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.store'), [
            'latitude' => -6.2001000,
            'longitude' => 106.8166000,
            'face_descriptor' => validFaceDescriptorJson(),
        ]);

    $response->assertRedirect(route('student.attendance.dashboard'));
    $response->assertSessionHasErrors('backup_attendance');

    $this->assertDatabaseMissing('attendances', [
        'student_id' => $student->id,
        'date' => '2026-04-20',
    ]);
});

test('student backup attendance requires radius and face verification', function () {
    [$student, $studentUser] = createStudentUserForBackupTests();

    Setting::current()->update([
        'backup_attendance_enabled' => true,
        'backup_attendance_radius_meters' => 25,
        'school_latitude' => -6.2000000,
        'school_longitude' => 106.8166667,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 55, 0, 'Asia/Jakarta'));

    $outsideRadiusResponse = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.store'), [
            'latitude' => -6.2055000,
            'longitude' => 106.8200000,
            'face_descriptor' => validFaceDescriptorJson(),
        ]);

    $outsideRadiusResponse->assertRedirect(route('student.attendance.dashboard'));
    $outsideRadiusResponse->assertSessionHasErrors('backup_attendance');

    $faceRequiredResponse = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.store'), [
            'latitude' => -6.2000100,
            'longitude' => 106.8166700,
        ]);

    $faceRequiredResponse->assertRedirect(route('student.attendance.dashboard'));
    $faceRequiredResponse->assertSessionHasErrors('face_descriptor');

    $this->assertDatabaseMissing('attendances', [
        'student_id' => $student->id,
        'date' => '2026-04-20',
    ]);
});

test('student can submit backup attendance when requirements are met', function () {
    [$student, $studentUser] = createStudentUserForBackupTests();

    Setting::current()->update([
        'backup_attendance_enabled' => true,
        'backup_attendance_radius_meters' => 50,
        'school_latitude' => -6.2000000,
        'school_longitude' => 106.8166667,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 55, 0, 'Asia/Jakarta'));

    $response = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.store'), [
            'latitude' => -6.2000100,
            'longitude' => 106.8166700,
            'face_descriptor' => validFaceDescriptorJson(),
        ]);

    $response->assertRedirect(route('student.attendance.dashboard'));
    $response->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
        'status' => Attendance::STATUS_ARRIVED,
    ]);
});

test('student backup attendance also stores admin notification with integer id', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    [$student, $studentUser] = createStudentUserForBackupTests();

    Setting::current()->update([
        'backup_attendance_enabled' => true,
        'backup_attendance_radius_meters' => 50,
        'school_latitude' => -6.2000000,
        'school_longitude' => 106.8166667,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 55, 0, 'Asia/Jakarta'));

    $response = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.store'), [
            'latitude' => -6.2000100,
            'longitude' => 106.8166700,
            'face_descriptor' => validFaceDescriptorJson(),
        ]);

    $response->assertRedirect(route('student.attendance.dashboard'));
    $response->assertSessionDoesntHaveErrors();

    $notification = DB::table('notifications')
        ->where('notifiable_id', $admin->id)
        ->where('notifiable_type', User::class)
        ->first();

    expect($notification)->not->toBeNull()
        ->and((int) $notification->id)->toBeGreaterThan(0);
});

test('student can register face descriptor from attendance dashboard', function () {
    [$student, $studentUser] = createStudentUserForBackupTests(null);

    $response = $this->actingAs($studentUser)
        ->from(route('student.attendance.dashboard'))
        ->post(route('student.attendance.face.store'), [
            'registration_face_descriptor' => validFaceDescriptorJson(),
        ]);

    $response->assertRedirect(route('student.attendance.dashboard'));
    $response->assertSessionDoesntHaveErrors();

    $student->refresh();

    expect($student->face_descriptor)
        ->toBeArray()
        ->toHaveCount(128);
});

/**
 * @return array{0: Student, 1: User}
 */
function createStudentUserForBackupTests(?array $faceDescriptor = null): array
{
    $descriptor = $faceDescriptor ?? validFaceDescriptor();

    $student = Student::query()->create([
        'name' => 'Siswa Cadangan',
        'nis' => 'NIS-CADANGAN-01',
        'fingerprint_id' => 1301,
        'face_descriptor' => $descriptor,
    ]);

    $user = User::query()->create([
        'name' => $student->name,
        'email' => 'siswa.cadangan.01@absensi.local',
        'password' => 'password',
        'role' => User::ROLE_STUDENT,
        'student_id' => $student->id,
    ]);

    return [$student, $user];
}

/**
 * @return array<int, float>
 */
function validFaceDescriptor(): array
{
    return array_map(
        static fn (int $index): float => (float) ($index / 1000),
        range(1, 128),
    );
}

function validFaceDescriptorJson(): string
{
    return json_encode(validFaceDescriptor(), JSON_THROW_ON_ERROR);
}
