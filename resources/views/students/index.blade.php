<x-app-layout>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Siswa</h1>
                <p class="text-sm text-slate-500">Kelola data siswa dan pendaftaran sidik jari.</p>
            </div>
            <a href="{{ route('students.create') }}">
                <x-bladewind::button size="small" icon="plus"
                    class="!rounded-xl shadow-sm hover:shadow-md transition-all">
                    Tambah Siswa
                </x-bladewind::button>
            </a>
        </div>

        @if (session('status'))
            <x-bladewind::alert type="success">
                {{ session('status') }}
            </x-bladewind::alert>
        @endif

        {{-- Main Content Card --}}
        <x-bladewind::card reduce_padding="true" class="!rounded-2xl border-slate-100 shadow-sm overflow-hidden p-0">
            <div class="overflow-x-auto w-full">
                <x-bladewind::table striped="true" hover="true" divider="thin" class="w-full !min-w-[800px]">
                    <x-slot name="header">
                        <th class="!text-xs !font-bold !uppercase !tracking-wider text-slate-400 whitespace-nowrap">Siswa</th>
                        <th class="!text-xs !font-bold !uppercase !tracking-wider text-slate-400 whitespace-nowrap">NIS</th>
                        <th class="!text-xs !font-bold !uppercase !tracking-wider text-slate-400 whitespace-nowrap">Fingerprint</th>
                        <th class="!text-xs !font-bold !uppercase !tracking-wider text-slate-400 whitespace-nowrap">Akun Login</th>
                        <th class="!text-xs !font-bold !uppercase !tracking-wider text-slate-400 text-right pr-12 whitespace-nowrap">Aksi</th>
                    </x-slot>

                    @forelse ($students as $student)
                        <tr class="group transition-all duration-200 hover:bg-slate-50/50">
                            <td class="py-5 pl-6 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-700 tracking-tight">{{ $student->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium tracking-wide">Siswa Rekayasa Perangkat
                                        Lunak</span>
                                </div>
                            </td>
                            <td class="py-5 whitespace-nowrap">
                                <span class="text-sm font-medium text-slate-500 tabular-nums">{{ $student->nis }}</span>
                            </td>
                            <td class="py-5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]">
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-400 tracking-tighter">ID:
                                        {{ $student->fingerprint_id }}</span>
                                </div>
                            </td>
                            <td class="py-5 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold text-slate-600">{{ $student->user?->email ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="py-4 pr-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-start gap-2">
                                    {{-- Edit Action --}}
                                    <a href="{{ route('students.edit', $student) }}">
                                        <x-bladewind::button size="tiny" color="yellow"
                                            class="!h-9 !w-9 !p-0 !flex !items-center !justify-center !rounded-xl shadow-sm"
                                            title="Edit">
                                            <x-bladewind::icon name="pencil-square" class="!h-5 !w-5 !m-0" />
                                        </x-bladewind::button>
                                    </a>

                                    {{-- Fingerprint Action (Show) --}}
                                    <a href="{{ route('enroll.index', ['fingerprint_id' => $student->fingerprint_id]) }}">
                                        <x-bladewind::button size="tiny" color="blue"
                                            class="!h-9 !w-9 !p-0 !flex !items-center !justify-center !rounded-xl shadow-sm"
                                            title="Pindai">
                                            <x-bladewind::icon name="finger-print" class="!h-5 !w-5 !m-0" />
                                        </x-bladewind::button>
                                    </a>

                                    {{-- Delete Action --}}
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-bladewind::button can_submit="true" size="tiny" color="red"
                                            class="!h-9 !w-9 !p-0 !flex !items-center !justify-center !rounded-xl shadow-sm"
                                            onclick="return confirm('Hapus siswa ini?');" title="Hapus">
                                            <x-bladewind::icon name="trash" class="!h-5 !w-5 !m-0" />
                                        </x-bladewind::button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-400 py-16">
                                <div class="flex flex-col items-center">
                                    <x-bladewind::icon name="user-group" class="h-12 w-12 text-slate-200 mb-2" />
                                    <span class="text-sm">Belum ada data siswa.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-bladewind::table>
            </div>

            @if ($students->hasPages())
                <div class="border-t border-slate-50 px-6 py-4 bg-slate-50/30">
                    {{ $students->links() }}
                </div>
            @endif
        </x-bladewind::card>
    </div>
</x-app-layout>