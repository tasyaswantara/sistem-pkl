@section('title', 'E-Logbook Bimbingan')

@php
    use App\Enums\LogbookStatus;
@endphp

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → E-Logbook</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">E-Logbook Siswa Bimbingan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Pantau logbook dan berikan komentar untuk siswa bimbingan Anda.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <form method="GET" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Filter E-Logbook</h3>
            </div>

            <div class="flex flex-wrap items-end gap-4 mb-4">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select name="tahun_ajaran" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($tahunAjaranOptions as $tahun)
                    <option value="{{ $tahun }}" {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                        {{ $tahun }}
                    </option>
                    @endforeach
                    </select>
                </div>

                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" onchange="this.form.submit()"
                    class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
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
                    <select name="industri_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                    <option value="">Semua Industri</option>
                    @foreach ($industriOptions as $industri)
                    <option value="{{ $industri->id }}" {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                        {{ $industri->nama_industri }}
                    </option>
                    @endforeach
                    </select>
                </div>

                <div class="min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" onchange="this.form.submit()"
                    class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                    @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                    </select>
                </div>

                <div class="min-w-[170px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                    <input
                        type="date"
                        name="tanggal"
                        value="{{ $filters['tanggal'] }}"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                        onchange="this.form.submit()">
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
                <a href="{{ route('guru.elogbook') }}" class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aktivitas</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status Validasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Komentar Guru</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($logbooks as $logbook)
                        @php
                        $statusClass = match ($logbook->status_validasi) {
                        LogbookStatus::DISETUJUI->value => 'bg-green-50 text-green-700 border border-green-200',
                        LogbookStatus::DITOLAK->value => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $logbook->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $logbook->siswa?->kelas ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $logbook->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $logbook->tanggal?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div>{{ $logbook->aktivitas }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $logbook->catatan_industri ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($logbook->status_validasi ?? LogbookStatus::PENDING->value) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="space-y-2 mb-3">
                                    @forelse ($logbook->komentar as $komentar)
                                    <div class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                                        {{ $komentar->komentar }}
                                    </div>
                                    @empty
                                    <span class="text-xs text-gray-400 italic">Belum ada komentar.</span>
                                    @endforelse
                                </div>
                                <form method="POST" action="{{ route('guru.elogbook.komentar', $logbook->id) }}" class="flex items-start gap-2">
                                    @csrf
                                    <textarea name="komentar" rows="2" class="w-full min-w-[190px] px-2.5 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500" placeholder="Tulis komentar"></textarea>
                                    <button class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                                        Kirim
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada logbook siswa bimbingan.
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
<script>
    let typingTimer;

    function debouncedSubmit(input) {
        clearTimeout(typingTimer);

        typingTimer = setTimeout(() => {
            input.form.submit();
        }, 700);
    }
</script>
