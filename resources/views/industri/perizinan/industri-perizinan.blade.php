@section('title', 'Perizinan Industri')

@php
    use App\Enums\PerizinanStatus;
@endphp

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Perizinan</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Perizinan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Konfirmasi perizinan siswa dan berikan catatan jika diperlukan.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <form method="GET" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                <a href="{{ route('industri.perizinan') }}" class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jenis Izin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($perizinanList as $row)
                        @php
                        $statusClass = match ($row->status) {
                        PerizinanStatus::DISETUJUI->value => 'bg-green-50 text-green-700 border border-green-200',
                        PerizinanStatus::DITOLAK->value => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->kelas ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->jenis_izin }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $row->tanggal_mulai?->format('d/m/Y') ?? '-' }} - {{ $row->tanggal_selesai?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('industri.perizinan.update', $row->id) }}" class="space-y-2">
                                    @csrf
                                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        @foreach ([
                                            PerizinanStatus::MENUNGGU->value => 'Menunggu',
                                            PerizinanStatus::DISETUJUI->value => 'Disetujui',
                                            PerizinanStatus::DITOLAK->value => 'Ditolak',
                                        ] as $value => $label)
                                        <option value="{{ $value }}" {{ $row->status === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <textarea name="catatan_industri" rows="2"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs"
                                        placeholder="Catatan">{{ $row->catatan_industri }}</textarea>
                                    <button class="w-full px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="5">
                                Belum ada perizinan.
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
