<?php

use App\Models\Student;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('student users are redirected to student attendance dashboard after login', function () {
    $student = Student::query()->create([
        'name' => 'Siswa Login',
        'nis' => 'SISWA-LOGIN-01',
        'fingerprint_id' => 901,
    ]);

    $user = User::query()->create([
        'name' => $student->name,
        'email' => 'siswa.login@absensi.local',
        'password' => 'password',
        'role' => User::ROLE_STUDENT,
        'student_id' => $student->id,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.attendance.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
