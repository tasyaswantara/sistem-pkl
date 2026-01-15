@section('title', 'Data Pengguna')

<x-admin-layout>

    @php
    $roleFilter = request('role', 'Semua Pengguna');
    $search = request('search');

    function roleBadge($role){
    return match($role){
    'siswa'=>'bg-blue-100 text-blue-700',
    'guru pembimbing'=>'bg-purple-100 text-purple-700',
    'perwakilan industri'=>'bg-orange-100 text-orange-700',
    'admin'=>'bg-emerald-100 text-emerald-700',
    default=>'bg-gray-100 text-gray-700'
    };
    }

    function statusBadge($status){
    return match($status){
    'Sedang PKL'=>'bg-green-50 text-green-700',
    'Belum PKL'=>'bg-yellow-50 text-yellow-700',
    'Selesai PKL'=>'bg-blue-50 text-blue-700',
    'Sedang PKL'=>'bg-green-50 text-green-700',
    'belum'=>'bg-yellow-50 text-yellow-700',
    'Selesai PKL'=>'bg-blue-50 text-blue-700',
    default=>'bg-gray-50 text-gray-700'
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
        <div class="flex items-center gap-3">
            <select name="role"
                onchange="this.form.submit()"
                class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                @foreach (['Semua Pengguna','Admin','Siswa','Guru Pembimbing','Perwakilan Industri'] as $r)
                <option value="{{ $r }}" {{ request('role')==$r?'selected':'' }}>
                    {{ $r }}
                </option>
                @endforeach
            </select>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau email"
                class="w-[360px] px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                oninput="debouncedSubmit(this)">


            <button class="hidden">Cari</button>
        </div>

        <button type="button"
            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
            + Tambah Pengguna
        </button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-xs font-semibold uppercase text-gray-600">
                    <th class="px-6 py-3 text-left">No</th>
                    <th class="px-6 py-3 text-left">Nama</th>
                    @if($roleFilter==='Siswa')
                    <th class="px-6 py-3 text-left">NIS</th>
                    <th class="px-6 py-3 text-left">Jurusan</th>
                    <th class="px-6 py-3 text-left">Status PKL</th>
                    <th class="px-6 py-3 text-left">Email</th>
                    @elseif($roleFilter==='Guru Pembimbing')
                    <th class="px-6 py-3 text-left">NIP</th>
                    <th class="px-6 py-3 text-left">Jurusan</th>
                    <th class="px-6 py-3 text-left">Email</th>
                    @elseif($roleFilter==='Perwakilan Industri')
                    <th class="px-6 py-3 text-left">Kapasitas</th>
                    <th class="px-6 py-3 text-left">Alamat</th>
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

                    @if($roleFilter === 'Siswa')
                    <td class="px-6 py-4 text-sm">{{ $u->siswa->nis ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u->siswa->jurusan->nama ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ statusBadge($u->siswa->status_pkl ?? '-') }}">
                            {{ $u->siswa->status_pkl ?? '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @elseif($roleFilter === 'Guru Pembimbing')
                    <td class="px-6 py-4 text-sm">{{ $u->gurupembimbing->nip ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u->gurupembimbing->jurusan->nama }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @elseif($roleFilter === 'Perwakilan Industri')
                    <td class="px-6 py-4 text-sm">{{ $u->industri->kapasitas ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u->industri->alamat ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @else
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                    <td class="px-6 py-4">
                        @foreach($u->roles as $role)
                        <span class="px-2.5 py-1 text-sm font-medium rounded-full  {{ roleBadge($role->name) }}">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </td>
                    @endif

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">

                            {{-- Edit --}}
                            <button
                                class="p-2 rounded-md text-gray-500 hover:text-blue-50 hover:bg-gray-100 transition"
                                title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213l-4.5 1.125 1.125-4.5L16.862 3.487z" />
                                </svg>
                            </button>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}"
                                onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-red-50 transition"
                                    title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 7h12M9 7v10m6-10v10M4 7h16l-1 12a2 2 0 01-2 2H7a2 2 0 01-2-2L4 7zM9 4h6a1 1 0 011 1v2H8V5a1 1 0 011-1z" />
                                    </svg>
                                </button>
                            </form>
                            <!-- <button
                                class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-red-50 transition"
                                title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 7h12M9 7v10m6-10v10M4 7h16l-1 12a2 2 0 01-2 2H7a2 2 0 01-2-2L4 7zM9 4h6a1 1 0 011 1v2H8V5a1 1 0 011-1z" />
                                </svg>
                            </button> -->

                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-gray-500 text-sm">
                        Tidak ada data pengguna
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Menampilkan {{ $users->count() }} pengguna
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