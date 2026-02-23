@section('title', 'Data Pengguna')

<x-admin-layout>
    <div x-data="{
        siswaOpen: false,
        siswaGuruName: '',
        siswaList: [],
    }">

        {{-- Toast --}}
        @if (session('success'))
            <template x-teleport="body">
                <div x-data="{ open: true }" x-show="open" x-transition x-init="setTimeout(() => { open = false }, 4500)"
                    class="fixed top-20 right-6 z-[9999] max-w-sm w-full bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="flex">
                        <div class="flex justify-center items-center w-12 bg-green-500">
                            <svg class="h-6 w-6 fill-current text-white" viewBox="0 0 40 40"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 3.33331C10.8 3.33331 3.33337 10.8 3.33337 20C3.33337 29.2 10.8 36.6666 20 36.6666C29.2 36.6666 36.6667 29.2 36.6667 20C36.6667 10.8 29.2 3.33331 20 3.33331ZM16.6667 28.3333L8.33337 20L10.6834 17.65L16.6667 23.6166L29.3167 10.9666L31.6667 13.3333L16.6667 28.3333Z" />
                            </svg>
                        </div>

                        <div class="flex-1 -mx-3 py-2 px-4">
                            <div class="mx-3">
                                <span class="text-green-500 font-semibold">Success</span>
                                <p class="text-gray-600 text-sm">{{ session('success') }}</p>
                            </div>
                        </div>

                        <button type="button" @click="open = false" class="px-4 text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>
                </div>
            </template>
        @endif

        @php
            $roleFilter = request('role', 'Semua Pengguna');
            $search = request('search');

            function roleBadge($role)
            {
                return match ($role) {
                    'siswa' => 'bg-blue-100 text-blue-700',
                    'guru pembimbing' => 'bg-purple-100 text-purple-700',
                    'perwakilan industri' => 'bg-orange-100 text-orange-700',
                    'admin' => 'bg-emerald-100 text-emerald-700',
                    default => 'bg-gray-100 text-gray-700',
                };
            }

            function statusBadge($status)
            {
                return match ($status) {
                    'berjalan' => 'bg-green-50 text-green-700',
                    'selesai' => 'bg-blue-50 text-blue-700',
                    'Sedang PKL' => 'bg-green-50 text-green-700',
                    'Belum PKL' => 'bg-yellow-50 text-yellow-700',
                    'Selesai PKL' => 'bg-blue-50 text-blue-700',
                    'Sedang PKL' => 'bg-green-50 text-green-700',
                    'belum' => 'bg-yellow-50 text-yellow-700',
                    'Selesai PKL' => 'bg-blue-50 text-blue-700',
                    default => 'bg-gray-50 text-gray-700',
                };
            }
        @endphp


        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-gray-900 text-2xl font-semibold mb-1">Data Pengguna</h1>
            <p class="text-gray-500 text-sm">Halaman untuk mengelola data pengguna Sistem PKL</p>
        </div>

        {{-- Filter & Control --}}
        <form method="GET" class="flex items-center justify-between gap-4 mb-6">
            @if (request('role'))
                <input type="hidden" name="role" value="{{ request('role') }}">
            @endif
            <div class="flex flex-wrap items-center gap-3">
                @if ($roleFilter === 'Siswa')
                    <select name="jurusan_id" onchange="this.form.submit()"
                        class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanOptions as $j)
                            <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>
                                {{ $j->nama }}
                            </option>
                        @endforeach
                    </select>

                    <select name="kelas" onchange="this.form.submit()"
                        class="px-4 py-2 w-[180px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasOptions as $kelas)
                            <option value="{{ $kelas }}" {{ request('kelas') == $kelas ? 'selected' : '' }}>
                                {{ $kelas }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @if ($roleFilter === 'Perwakilan Industri')
                    <select name="jurusan_id" onchange="this.form.submit()"
                        class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanOptions as $j)
                            <option value="{{ $j->id }}"
                                {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>
                                {{ $j->nama }}
                            </option>
                        @endforeach
                    </select>

                    <select name="grade" onchange="this.form.submit()"
                        class="px-4 py-2 w-[160px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                        <option value="">Semua Grade</option>
                        @foreach (['A', 'B', 'C'] as $g)
                            <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>
                                Grade {{ $g }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email"
                    class="w-[320px] px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                    oninput="debouncedSubmit(this)">


                <button class="hidden">Cari</button>
            </div>
            <a href="{{ route('admin.users.create', request()->only('role')) }}"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                + Tambah Pengguna
            </a>

        </form>

        {{-- Table --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-xs font-semibold uppercase text-gray-600">
                        <th class="px-6 py-3 text-left">No</th>
                        <th class="px-6 py-3 text-left">Nama</th>
                        @if ($roleFilter === 'Siswa')
                            <th class="px-6 py-3 text-left">NIS</th>
                            <th class="px-6 py-3 text-left">Jurusan</th>
                            <th class="px-6 py-3 text-left">Kelas</th>
                            <th class="px-6 py-3 text-left">Rata-rata Nilai Kejuruan</th>
                            <th class="px-6 py-3 text-left">Status PKL</th>
                            <th class="px-6 py-3 text-left">Email</th>
                        @elseif($roleFilter === 'Guru Pembimbing')
                            <th class="px-6 py-3 text-left">NIP</th>
                            <th class="px-6 py-3 text-left">Jurusan</th>
                            <th class="px-6 py-3 text-left">Siswa</th>
                            <th class="px-6 py-3 text-left">Email</th>
                        @elseif($roleFilter === 'Perwakilan Industri')
                            <th class="px-6 py-3 text-left">Kapasitas</th>
                            <th class="px-6 py-3 text-left">Grade</th>
                            <th class="px-6 py-3 text-left">Jurusan</th>
                            <th class="px-6 py-3 text-left">Pengajuan</th>
                            <th class="px-6 py-3 text-left w-64">Alamat</th>
                            <th class="px-6 py-3 text-left">Email</th>
                        @else
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-left">Role</th>
                        @endif
                        <th class="px-6 py-3 text-left">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $i => $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">{{ $i + 1 }}</td>
                            <td class="px-6 py-4 text-sm font-medium">{{ $u->name }}</td>

                            @if ($roleFilter === 'Siswa')
                                <td class="px-6 py-4 text-sm">{{ $u->siswa->nis ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->siswa->jurusan->nama ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->siswa->kelas ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->siswa->nilai_akademik ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs {{ statusBadge($u->siswa->status_pkl ?? '-') }}">
                                        {{ $u->siswa->status_pkl ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                            @elseif($roleFilter === 'Guru Pembimbing')
                                <td class="px-6 py-4 text-sm">{{ $u->gurupembimbing->nip ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    {{ optional(optional($u->gurupembimbing)->jurusan)->nama ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $siswaBimbingan = $u->gurupembimbing?->penempatanPkl
                                            ? $u->gurupembimbing->penempatanPkl
                                                ->map(function ($item) {
                                                    return [
                                                        'nama' => $item->siswa?->user?->name ?? '-',
                                                        'kelas' => $item->siswa?->kelas ?? '-',
                                                        'jurusan' => $item->siswa?->jurusan?->nama ?? '-',
                                                        'industri' => $item->industri?->nama_industri ?? '-',
                                                    ];
                                                })
                                                ->values()
                                            : collect();
                                    @endphp
                                    @if ($siswaBimbingan->isEmpty())
                                        <span class="text-xs text-gray-400 italic">Belum membimbing</span>
                                    @else
                                        <button type="button"
                                            class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                            @click="siswaOpen = true; siswaGuruName = @js($u->name); siswaList = @js($siswaBimbingan);">
                                            Lihat Detail
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                            @elseif($roleFilter === 'Perwakilan Industri')
                                <td class="px-6 py-4 text-sm">{{ $u->industri->kapasitas ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->industri->grade ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->industri->jurusan->nama ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if ($u->industri?->status_pengajuan)
                                        @php
                                            $pengajuanClass = match ($u->industri->status_pengajuan) {
                                                'disetujui' => 'bg-green-50 text-green-700 border border-green-200',
                                                'ditolak' => 'bg-red-50 text-red-700 border border-red-200',
                                                default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $pengajuanClass }}">
                                            {{ ucfirst($u->industri->status_pengajuan) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm w-64">{{ $u->industri->alamat ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                            @else
                                <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @foreach ($u->roles as $role)
                                        <span
                                            class="px-2.5 py-1 text-sm font-medium rounded-full  {{ roleBadge($role->name) }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </td>
                            @endif

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.users.edit', array_merge(['user' => $u->id], request()->query())) }}"
                                        class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-blue-50 transition"
                                        title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213l-4.5 1.125 1.125-4.5L16.862 3.487z" />
                                        </svg>
                                    </a>

                                    {{-- Delete --}}
                                    <form method="POST"
                                        action="{{ route('admin.users.destroy', array_merge(['user' => $u->id], request()->query())) }}"
                                        onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-red-50 transition"
                                            title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 7h12M9 7v10m6-10v10M4 7h16l-1 12a2 2 0 01-2 2H7a2 2 0 01-2-2L4 7zM9 4h6a1 1 0 011 1v2H8V5a1 1 0 011-1z" />
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-gray-500 text-sm">
                                Tidak ada data pengguna
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>

        {{-- Modal Siswa Bimbingan --}}
        <div x-show="siswaOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Daftar Siswa Bimbingan</h4>
                        <p class="text-xs text-gray-500">
                            Guru: <span x-text="siswaGuruName"></span>
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600"
                        @click="siswaOpen = false">✕</button>
                </div>
                <div class="p-4">
                    <template x-if="siswaList.length === 0">
                        <div class="text-sm text-gray-500 italic">Belum ada siswa bimbingan.</div>
                    </template>
                    <template x-if="siswaList.length > 0">
                        <div class="space-y-2">
                            <template x-for="siswa in siswaList" :key="siswa.nama + siswa.kelas">
                                <div
                                    class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900" x-text="siswa.nama"></div>
                                        <div class="text-xs text-gray-500"
                                            x-text="`${siswa.kelas} · ${siswa.jurusan}`"></div>
                                        <div class="text-xs text-gray-400" x-text="`Industri: ${siswa.industri}`">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
<script>
    let typingTimer;

    function debouncedSubmit(input) {
        clearTimeout(typingTimer);

        typingTimer = setTimeout(() => {
            input.form.submit();
        }, 700);
    }
</script>
