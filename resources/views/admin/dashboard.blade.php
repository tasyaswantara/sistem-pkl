<x-admin-layout>

    @php
    $roleFilter = request('role', 'Semua Pengguna');
    $search = strtolower(request('search', ''));

    $users = [
    [
    'id'=>1,'nama'=>'Budi Santoso','email'=>'budi@student.sch.id',
    'role'=>'Siswa','nis'=>'12345678','jurusan'=>'Teknik Informatika','statusPkl'=>'Sedang PKL'
    ],
    [
    'id'=>2,'nama'=>'Siti Nurhaliza','email'=>'siti@student.sch.id',
    'role'=>'Siswa','nis'=>'12345679','jurusan'=>'RPL','statusPkl'=>'Belum PKL'
    ],
    [
    'id'=>3,'nama'=>'Dr. Ahmad Hidayat','email'=>'ahmad@teacher.sch.id',
    'role'=>'Guru Pembimbing','nip'=>'198501012010','jurusan'=>'Teknik Informatika'
    ],
    [
    'id'=>4,'nama'=>'Rina Wijaya','email'=>'rina@industry.com',
    'role'=>'Perwakilan Industri','namaIndustri'=>'PT Maju Jaya','kapasitas'=>10,'alamat'=>'Jakarta'
    ],
    [
    'id'=>5,'nama'=>'Humas PKL','email'=>'humas@school.sch.id','role'=>'Admin'
    ],
    ];

    $filteredUsers = array_filter($users, function($u) use ($roleFilter, $search) {
    $matchRole = $roleFilter === 'Semua Pengguna' || $u['role'] === $roleFilter;
    $matchSearch = $search === '' ||
    str_contains(strtolower($u['nama']), $search) ||
    str_contains(strtolower($u['email']), $search) ||
    collect($u)->contains(fn($v)=> is_string($v) && str_contains(strtolower($v), $search));
    return $matchRole && $matchSearch;
    });

    function roleBadge($role){
    return match($role){
    'Siswa'=>'bg-blue-100 text-blue-700',
    'Guru Pembimbing'=>'bg-purple-100 text-purple-700',
    'Perwakilan Industri'=>'bg-orange-100 text-orange-700',
    'Admin'=>'bg-emerald-100 text-emerald-700',
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
                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                @foreach (['Semua Pengguna','Siswa','Guru Pembimbing','Perwakilan Industri','Admin'] as $r)
                <option value="{{ $r }}" {{ request('role')==$r?'selected':'' }}>
                    {{ $r }}
                </option>
                @endforeach
            </select>

            <input name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama, email, NIS/NIP"
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
                @forelse($filteredUsers as $i=>$u)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $i+1 }}</td>
                    <td class="px-6 py-4 text-sm font-medium">{{ $u['nama'] }}</td>

                    @if($roleFilter==='Siswa')
                    <td class="px-6 py-4 text-sm">{{ $u['nis'] }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u['jurusan'] }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ statusBadge($u['statusPkl']) }}">
                            {{ $u['statusPkl'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $u['email'] }}</td>
                    @elseif($roleFilter==='Guru Pembimbing')
                    <td class="px-6 py-4 text-sm">{{ $u['nip'] }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u['jurusan'] }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u['email'] }}</td>
                    @elseif($roleFilter==='Perwakilan Industri')
                    <td class="px-6 py-4 text-sm">{{ $u['namaIndustri'] }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u['kapasitas'] }} siswa</td>
                    <td class="px-6 py-4 text-sm">{{ $u['alamat'] }}</td>
                    <td class="px-6 py-4 text-sm">{{ $u['email'] }}</td>
                    @else
                    <td class="px-6 py-4 text-sm">{{ $u['email'] }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ roleBadge($u['role']) }}">
                            {{ $u['role'] }}
                        </span>
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
        Menampilkan {{ count($filteredUsers) }} dari {{ count($users) }} pengguna
    </div>

</x-admin-layout>