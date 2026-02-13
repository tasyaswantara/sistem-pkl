@section('title', 'Perizinan')

@php
    use App\Enums\PerizinanStatus;
@endphp

<x-admin-layout>
    <div x-data="{ editId: null }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard -> Perizinan</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Perizinan Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Monitoring pengajuan izin siswa PKL dan status persetujuan industri.
            </p>
        </div>

        <div id="tambah-perizinan" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Tambah Perizinan</h3>
                <span class="text-xs text-gray-500">Dibuat oleh admin</span>
            </div>
            <form method="POST" action="{{ route('admin.perizinan.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @php
                $siswaSelectData = $siswaPenempatanOptions->map(function ($item) {
                    $nama = $item->siswa?->user?->name ?? '-';
                    $nis = $item->siswa?->nis ?? '-';
                    $jurusan = $item->siswa?->jurusan?->nama ?? '-';
                    $kelas = $item->siswa?->kelas ?? '-';
                    $industri = $item->industri?->nama_industri ?? '-';
                    return [
                        'id' => $item->siswa_id,
                        'label' => trim("{$nama} · {$nis} · {$jurusan} · {$kelas} · {$industri}"),
                    ];
                })->values();
                @endphp
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Target Siswa</label>
                    <select name="scope" id="scope-select"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                        onchange="toggleScope()">
                        <option value="all" {{ old('scope', 'selected') === 'all' ? 'selected' : '' }}>Semua siswa yang sudah ditempatkan</option>
                        <option value="selected" {{ old('scope', 'selected') === 'selected' ? 'selected' : '' }}>Siswa pilihan</option>
                    </select>
                </div>
                <div id="scope-selected" class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Pilih Siswa</label>
                    <div class="relative">
                        <input type="text" id="siswa-search" placeholder="Cari nama/NIS siswa"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                            oninput="filterSiswaSearch()">
                        <div id="siswa-results" class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg hidden"></div>
                    </div>
                    <div id="siswa-selected-list" class="mt-3 flex flex-wrap gap-2"></div>
                    <div id="siswa-hidden-inputs"></div>
                    <p class="text-xs text-gray-500 mt-2">
                        Ketik nama/NIS, lalu klik untuk menambahkan.
                    </p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jenis Izin</label>
                    <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                        Izin Kegiatan Sekolah
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                        Simpan Perizinan
                    </button>
                </div>
            </form>
        </div>

        <form method="GET" action="{{ route('admin.perizinan') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Filter Perizinan</h3>
                <div class="flex items-center gap-3 text-sm">
                    <div class="px-3 py-1.5 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <span class="text-yellow-600 font-semibold">{{ $statusCounts[PerizinanStatus::MENUNGGU->value] ?? 0 }}</span>
                        <span class="text-yellow-700 ml-1">Menunggu</span>
                    </div>
                    <div class="px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                        <span class="text-green-600 font-semibold">{{ $statusCounts[PerizinanStatus::DISETUJUI->value] ?? 0 }}</span>
                        <span class="text-green-700 ml-1">Disetujui</span>
                    </div>
                    <div class="px-3 py-1.5 bg-red-50 border border-red-200 rounded-lg">
                        <span class="text-red-600 font-semibold">{{ $statusCounts[PerizinanStatus::DITOLAK->value] ?? 0 }}</span>
                        <span class="text-red-700 ml-1">Ditolak</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-4 mb-4">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select name="tahun_ajaran" onchange="this.form.submit()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua</option>
                        @foreach ($tahunAjaranList as $tahun)
                        <option value="{{ $tahun }}" {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" onchange="this.form.submit()" class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) $filters['jurusan_id'] === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                    <select name="industri_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua Industri</option>
                        @foreach ($industriOptions as $industri)
                        <option value="{{ $industri->id }}" {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                            {{ $industri->nama_industri }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" onchange="this.form.submit()" class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px] flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Nama siswa"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                            oninput="debouncedSubmit(this)">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.perizinan') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                    Reset Filter
                </a>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jurusan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jenis Izin</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catatan Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($perizinanList as $row)
                        @php
                        $statusClass = match ($row->status) {
                        PerizinanStatus::DISETUJUI->value => 'bg-green-50 text-green-700 border border-green-200',
                        PerizinanStatus::DITOLAK->value => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->jenis_izin }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->tanggal_mulai?->format('d/m/Y') ?? '-' }}
                                @if ($row->tanggal_selesai)
                                - {{ $row->tanggal_selesai->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->catatan_industri ? \Illuminate\Support\Str::limit($row->catatan_industri, 80) : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                        @click="editId = editId === {{ $row->id }} ? null : {{ $row->id }}">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.perizinan.destroy', $row->id) }}"
                                        onsubmit="return confirm('Hapus perizinan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 text-xs border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="editId === {{ $row->id }}" x-cloak class="bg-gray-50">
                            <td colspan="8" class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.perizinan.update', $row->id) }}"
                                    class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Mulai</label>
                                        <input type="date" name="tanggal_mulai" value="{{ $row->tanggal_mulai?->format('Y-m-d') }}"
                                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" value="{{ $row->tanggal_selesai?->format('Y-m-d') }}"
                                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="8">
                                Belum ada data perizinan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $perizinanList->links() }}
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

    const siswaData = @js($siswaSelectData);
    let selectedSiswa = new Map();

    function toggleScope() {
        const scope = document.getElementById('scope-select')?.value;
        const selectedBlock = document.getElementById('scope-selected');
        if (selectedBlock) {
            selectedBlock.style.display = scope === 'selected' ? 'block' : 'none';
        }
    }

    function renderSiswaResults(list) {
        const results = document.getElementById('siswa-results');
        if (!results) return;
        results.innerHTML = '';
        if (list.length === 0) {
            results.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500">Tidak ditemukan.</div>';
            return;
        }
        list.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-50';
            button.textContent = item.label;
            button.onclick = () => addSiswa(item);
            results.appendChild(button);
        });
    }

    function filterSiswaSearch() {
        const input = document.getElementById('siswa-search');
        const results = document.getElementById('siswa-results');
        if (!input || !results) return;
        const term = input.value.toLowerCase().trim();
        const list = term
            ? siswaData.filter((item) => item.label.toLowerCase().includes(term)).slice(0, 10)
            : siswaData.slice(0, 10);
        results.classList.remove('hidden');
        renderSiswaResults(list);
    }

    function addSiswa(item) {
        if (selectedSiswa.has(String(item.id))) {
            return;
        }
        selectedSiswa.set(String(item.id), item);
        renderSelectedSiswa();
        const results = document.getElementById('siswa-results');
        if (results) {
            results.classList.add('hidden');
        }
    }

    function removeSiswa(id) {
        selectedSiswa.delete(String(id));
        renderSelectedSiswa();
    }

    function renderSelectedSiswa() {
        const list = document.getElementById('siswa-selected-list');
        const inputs = document.getElementById('siswa-hidden-inputs');
        if (!list || !inputs) return;
        list.innerHTML = '';
        inputs.innerHTML = '';
        selectedSiswa.forEach((item) => {
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs';
            badge.innerHTML = `<span>${item.label}</span>`;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'text-emerald-700 hover:text-emerald-900';
            button.textContent = '✕';
            button.onclick = () => removeSiswa(item.id);
            badge.appendChild(button);
            list.appendChild(badge);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'siswa_ids[]';
            hidden.value = item.id;
            inputs.appendChild(hidden);
        });
    }

    document.addEventListener('click', (event) => {
        const results = document.getElementById('siswa-results');
        const input = document.getElementById('siswa-search');
        if (!results || !input) return;
        if (results.contains(event.target) || input.contains(event.target)) {
            return;
        }
        results.classList.add('hidden');
    });

    document.addEventListener('DOMContentLoaded', () => {
        toggleScope();
        const oldSelected = @js(old('siswa_ids', []));
        oldSelected.forEach((id) => {
            const found = siswaData.find((item) => String(item.id) === String(id));
            if (found) {
                selectedSiswa.set(String(id), found);
            }
        });
        renderSelectedSiswa();
    });
</script>
