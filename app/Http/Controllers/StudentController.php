<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

            $this->createStudentUserAccount($student);
        });

        return redirect()
            ->route('students.index')
            ->with('status', 'Siswa berhasil ditambahkan. Akun login siswa otomatis dibuat dengan password awal sesuai NIS.');
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

            $this->syncStudentUserAccount($student);
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

    private function createStudentUserAccount(Student $student): User
    {
        return User::query()->create([
            'name' => $student->name,
            'email' => $this->resolveStudentEmail($student),
            'password' => Hash::make($student->nis),
            'role' => User::ROLE_STUDENT,
            'student_id' => $student->id,
            'email_verified_at' => now(),
        ]);
    }

    private function syncStudentUserAccount(Student $student): void
    {
        $user = User::query()->where('student_id', $student->id)->first();

        if ($user === null) {
            $this->createStudentUserAccount($student);

            return;
        }

        $user->update([
            'name' => $student->name,
            'email' => $this->resolveStudentEmail($student),
            'role' => User::ROLE_STUDENT,
        ]);
    }

    private function resolveStudentEmail(Student $student): string
    {
        $preferredEmail = 'siswa.'.$this->normalizedNisSegment($student).'@absensi.local';

        $isPreferredTaken = User::query()
            ->where('email', $preferredEmail)
            ->where(function (Builder $query) use ($student): void {
                $query->whereNull('student_id')
                    ->orWhere('student_id', '!=', $student->id);
            })
            ->exists();

        if (! $isPreferredTaken) {
            return $preferredEmail;
        }

        return 'siswa.'.$student->id.'@absensi.local';
    }

    private function normalizedNisSegment(Student $student): string
    {
        $normalizedNis = Str::of($student->nis)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        if ($normalizedNis !== '') {
            return $normalizedNis;
        }

        return (string) $student->id;
    }
}
