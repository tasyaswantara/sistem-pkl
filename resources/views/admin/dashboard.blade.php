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

            <input name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau email"
                class="w-[360px] px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm" />

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
                    <th class="px-6 py-3 text-left">Industri</th>
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
                    <td class="px-6 py-4 text-sm">{{ $u->siswa->jurusan ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ statusBadge($u->siswa->status_pkl ?? '-') }}">
                            {{ $u->siswa->status_pkl ?? '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @elseif($roleFilter === 'Guru Pembimbing')
                    <td colspan="3" class="px-6 py-4 text-sm text-gray-500">
                        Data guru belum tersedia
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @elseif($roleFilter === 'Perwakilan Industri')
                    <td colspan="3" class="px-6 py-4 text-sm text-gray-500">
                        Data industri belum tersedia
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>

                    @else
                    <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                    <td class="px-6 py-4">
                        @foreach($u->roles as $role)
                        <span class="px-2 py-1 rounded-full text-xs {{ roleBadge($role->name) }}">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </td>
                    @endif

                    <td class="px-6 py-4 flex gap-2">
                        <button class="p-1.5 hover:bg-blue-50 rounded">✏️</button>
                        <button class="p-1.5 hover:bg-red-50 rounded">🗑️</button>
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