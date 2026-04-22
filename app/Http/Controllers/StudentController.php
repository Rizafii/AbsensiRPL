<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $students = Student::query()
            ->with('user:id,email,student_id')
            ->orderBy('name')
            ->paginate(10);

        return view('students.index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $student = Student::query()->create($validated);

            $this->createStudentUserAccount($student, $validated['email'], $validated['password']);
        });

        return redirect()
            ->route('students.index')
            ->with('status', 'Siswa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): void
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): View
    {
        return view('students.edit', [
            'student' => $student,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $student): void {
            $student->update($validated);

            $this->syncStudentUserAccount($student, $validated['email'], $validated['password'] ?? null);
        });

        return redirect()
            ->route('students.index')
            ->with('status', 'Siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        DB::transaction(function () use ($student): void {
            User::query()->where('student_id', $student->id)->delete();
            $student->delete();
        });

        return redirect()
            ->route('students.index')
            ->with('status', 'Siswa berhasil dihapus.');
    }

    private function createStudentUserAccount(Student $student, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $student->name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_STUDENT,
            'student_id' => $student->id,
            'email_verified_at' => now(),
        ]);
    }

    private function syncStudentUserAccount(Student $student, string $email, ?string $password = null): void
    {
        $user = User::query()->where('student_id', $student->id)->first();

        if ($user === null) {
            $this->createStudentUserAccount($student, $email, $password ?? $student->nis);

            return;
        }

        $data = [
            'name' => $student->name,
            'email' => $email,
            'role' => User::ROLE_STUDENT,
        ];

        if ($password !== null && $password !== '') {
            $data['password'] = Hash::make($password);
        }

        $user->update($data);
    }
}
