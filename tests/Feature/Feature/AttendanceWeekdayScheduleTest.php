<?php

use App\Models\Attendance;
use App\Models\FonnteAccount;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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

test('fonnte settings page can be accessed and initializes default accounts', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.fonnte.edit'));

    $response->assertSuccessful()
        ->assertSee('Pengaturan Akun Fonnte');

    $this->assertDatabaseHas('fonnte_accounts', [
        'event_type' => FonnteAccount::EVENT_CHECK_IN,
        'account_name' => 'Fonnte Masuk',
        'base_url' => 'https://api.fonnte.com',
        'timeout' => 10,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('fonnte_accounts', [
        'event_type' => FonnteAccount::EVENT_CHECK_OUT,
        'account_name' => 'Fonnte Pulang',
        'base_url' => 'https://api.fonnte.com',
        'timeout' => 10,
        'is_active' => true,
    ]);
});

test('fonnte settings can be updated for check in and check out accounts', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('settings.fonnte.update'), fonnteSettingPayload([
        'check_in_account_name' => 'Gateway Masuk',
        'check_in_base_url' => 'https://api.fonnte.com/',
        'check_in_token' => 'token-masuk-baru',
        'check_in_parent_group_target' => '120363111111111111@g.us',
        'check_in_timeout' => 12,
        'check_in_is_active' => '1',
        'check_out_account_name' => 'Gateway Pulang',
        'check_out_base_url' => 'https://api.fonnte.com',
        'check_out_token' => 'token-pulang-baru',
        'check_out_parent_group_target' => '120363222222222222@g.us',
        'check_out_timeout' => 15,
        'check_out_is_active' => '0',
    ]));

    $response->assertRedirect(route('settings.fonnte.edit'));

    $checkInAccount = FonnteAccount::query()
        ->where('event_type', FonnteAccount::EVENT_CHECK_IN)
        ->firstOrFail();
    $checkOutAccount = FonnteAccount::query()
        ->where('event_type', FonnteAccount::EVENT_CHECK_OUT)
        ->firstOrFail();

    expect($checkInAccount->account_name)->toBe('Gateway Masuk')
        ->and($checkInAccount->base_url)->toBe('https://api.fonnte.com')
        ->and($checkInAccount->token)->toBe('token-masuk-baru')
        ->and($checkInAccount->parent_group_target)->toBe('120363111111111111@g.us')
        ->and($checkInAccount->timeout)->toBe(12)
        ->and($checkInAccount->is_active)->toBeTrue()
        ->and($checkOutAccount->account_name)->toBe('Gateway Pulang')
        ->and($checkOutAccount->base_url)->toBe('https://api.fonnte.com')
        ->and($checkOutAccount->token)->toBe('token-pulang-baru')
        ->and($checkOutAccount->parent_group_target)->toBe('120363222222222222@g.us')
        ->and($checkOutAccount->timeout)->toBe(15)
        ->and($checkOutAccount->is_active)->toBeFalse();
});

test('fonnte settings require token and target when account is active', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('settings.fonnte.edit'))
        ->put(route('settings.fonnte.update'), fonnteSettingPayload([
            'check_in_is_active' => '1',
            'check_in_token' => '',
            'check_in_parent_group_target' => '',
            'check_out_is_active' => '0',
        ]));

    $response->assertRedirect(route('settings.fonnte.edit'));
    $response->assertSessionHasErrors([
        'check_in_token',
        'check_in_parent_group_target',
    ]);
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

test('attendance api sends parent group whatsapp message on successful check in', function () {
    FonnteAccount::query()->create([
        'event_type' => FonnteAccount::EVENT_CHECK_IN,
        'account_name' => 'Akun Masuk',
        'base_url' => 'https://api.fonnte.com',
        'token' => 'token-masuk',
        'parent_group_target' => '120363000000000000@g.us',
        'timeout' => 10,
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
    ]);

    $student = Student::query()->create([
        'name' => 'Siswa Fonnte',
        'nis' => 'NIS-1003',
        'fingerprint_id' => 1003,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 55, 0, 'Asia/Jakarta'));

    $response = $this->postJson('/api/attendance', [
        'user_id' => $student->fingerprint_id,
    ], [
        'Authorization' => 'Bearer jgk0advefk90gj4ngin4290',
    ]);

    $response->assertSuccessful();

    Http::assertSent(function (Request $request) use ($student): bool {
        $message = (string) ($request['message'] ?? '');

        return $request->url() === 'https://api.fonnte.com/send'
            && $request->hasHeader('Authorization', 'token-masuk')
            && ($request['target'] ?? null) === '120363000000000000@g.us'
            && str_contains($message, 'Nama: ' . $student->name)
            && str_contains($message, 'Pukul masuk: 06:55 WIB')
            && str_contains($message, 'Status: Hadir Tepat Waktu');
    });
    Http::assertSentCount(1);
});

test('attendance api sends parent group whatsapp message on successful check out', function () {
    FonnteAccount::query()->create([
        'event_type' => FonnteAccount::EVENT_CHECK_IN,
        'account_name' => 'Akun Masuk',
        'base_url' => 'https://api.fonnte.com',
        'token' => 'token-masuk',
        'parent_group_target' => '120363000000000000@g.us',
        'timeout' => 10,
        'is_active' => true,
    ]);

    FonnteAccount::query()->create([
        'event_type' => FonnteAccount::EVENT_CHECK_OUT,
        'account_name' => 'Akun Pulang',
        'base_url' => 'https://api.fonnte.com',
        'token' => 'token-pulang',
        'parent_group_target' => '120363000000000000@g.us',
        'timeout' => 10,
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
    ]);

    $student = Student::query()->create([
        'name' => 'Siswa Pulang',
        'nis' => 'NIS-1004',
        'fingerprint_id' => 1004,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 6, 50, 0, 'Asia/Jakarta'));

    $this->postJson('/api/attendance', [
        'user_id' => $student->fingerprint_id,
    ], [
        'Authorization' => 'Bearer jgk0advefk90gj4ngin4290',
    ])->assertSuccessful();

    Carbon::setTestNow(Carbon::create(2026, 4, 20, 15, 10, 0, 'Asia/Jakarta'));

    $response = $this->postJson('/api/attendance', [
        'user_id' => $student->fingerprint_id,
    ], [
        'Authorization' => 'Bearer jgk0advefk90gj4ngin4290',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.status', Attendance::STATUS_DEPARTED);

    Http::assertSent(function (Request $request): bool {
        $message = (string) ($request['message'] ?? '');

        return $request->url() === 'https://api.fonnte.com/send'
            && $request->hasHeader('Authorization', 'token-masuk')
            && str_contains($message, '*Notifikasi Absensi Masuk*');
    });

    Http::assertSent(function (Request $request) use ($student): bool {
        $message = (string) ($request['message'] ?? '');

        return $request->url() === 'https://api.fonnte.com/send'
            && $request->hasHeader('Authorization', 'token-pulang')
            && ($request['target'] ?? null) === '120363000000000000@g.us'
            && str_contains($message, '*Notifikasi Absensi Pulang*')
            && str_contains($message, 'Nama: ' . $student->name)
            && str_contains($message, 'Pukul pulang: 15:10 WIB')
            && str_contains($message, 'Status: Pulang');
    });
    Http::assertSentCount(2);
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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fonnteSettingPayload(array $overrides = []): array
{
    return array_merge([
        'check_in_account_name' => 'Fonnte Masuk',
        'check_in_base_url' => 'https://api.fonnte.com',
        'check_in_token' => 'token-masuk-default',
        'check_in_parent_group_target' => '120363000000000001@g.us',
        'check_in_timeout' => 10,
        'check_in_is_active' => '1',
        'check_out_account_name' => 'Fonnte Pulang',
        'check_out_base_url' => 'https://api.fonnte.com',
        'check_out_token' => 'token-pulang-default',
        'check_out_parent_group_target' => '120363000000000002@g.us',
        'check_out_timeout' => 10,
        'check_out_is_active' => '1',
    ], $overrides);
}
