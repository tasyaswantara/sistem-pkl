@section('title', 'E-Logbook Industri')

@php
    use App\Enums\LogbookStatus;
@endphp

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → E-Logbook</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Validasi E-Logbook</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Validasi logbook siswa dan berikan catatan industri.
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
                        @foreach ([LogbookStatus::PENDING->value => 'Pending', LogbookStatus::DISETUJUI->value => 'Disetujui', LogbookStatus::DITOLAK->value => 'Ditolak'] as $value => $label)
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
                <a href="{{ route('industri.elogbook') }}" class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aktivitas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logbooks as $row)
                        @php
                        $statusClass = match ($row->status_validasi) {
                        LogbookStatus::DISETUJUI->value => 'bg-green-50 text-green-700 border border-green-200',
                        LogbookStatus::DITOLAK->value => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->kelas ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[130px]">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[360px]">{{ $row->aktivitas }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($row->status_validasi) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[260px]">{{ $row->catatan_industri ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('industri.elogbook.update', $row->id) }}" class="space-y-2">
                                    @csrf
                                    <select name="status_validasi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        @foreach ([
                                            LogbookStatus::PENDING->value => 'Pending',
                                            LogbookStatus::DISETUJUI->value => 'Disetujui',
                                            LogbookStatus::DITOLAK->value => 'Ditolak',
                                        ] as $value => $label)
                                        <option value="{{ $value }}" {{ $row->status_validasi === $value ? 'selected' : '' }}>
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
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="6">
                                Belum ada logbook.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $logbooks->links() }}
        </div>
    </div>
</x-admin-layout>
