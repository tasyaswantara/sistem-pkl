@section('title', 'Perizinan Bimbingan')

@php
    use App\Enums\PerizinanStatus;
@endphp

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Perizinan</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Perizinan Siswa Bimbingan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Pantau pengajuan perizinan untuk siswa bimbingan Anda.
            </p>
        </div>

        <form method="GET" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama atau NIS siswa"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) ($filters['jurusan_id'] ?? '') === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                    <select name="industri_id" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Industri</option>
                        @foreach ($industriOptions as $industri)
                        <option value="{{ $industri->id }}" {{ (string) ($filters['industri_id'] ?? '') === (string) $industri->id ? 'selected' : '' }}>
                            {{ $industri->nama_industri }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Status</option>
                        @foreach ([PerizinanStatus::MENUNGGU->value => 'Menunggu', PerizinanStatus::DISETUJUI->value => 'Disetujui', PerizinanStatus::DITOLAK->value => 'Ditolak'] as $value => $label)
                        <option value="{{ $value }}" {{ (string) ($filters['status'] ?? '') === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                    Terapkan Filter
                </button>
                <a href="{{ route('guru.perizinan') }}" class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jenis Izin</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->kelas ?? '-' }}</div>
                            </td>
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
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="5">
                                Belum ada data perizinan siswa bimbingan.
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
