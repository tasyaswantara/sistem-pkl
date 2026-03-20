@section('title', 'Peringatan Dini Siswa')

<x-admin-layout>
    <div x-data="{
            detailOpen: false,
            detailNama: '',
            detailJurusan: '',
            detailData: {},
        }">
    <div class="mb-8">
        <div class="text-sm text-gray-500 mb-2">Dashboard → Peringatan Dini Siswa</div>
        <h1 class="text-gray-900 text-2xl font-semibold mb-2">Peringatan Dini Siswa</h1>
        <p class="text-gray-500 text-sm max-w-2xl">
            Fitur ini digunakan untuk mengidentifikasi siswa yang sudah diterima industri namun berpotensi mengalami masalah selama PKL, berdasarkan parameter tertentu yang dihitung menjadi skor peringatan.
        </p>
    </div>

    @if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
    @endif

    @if ($errors->has('week_end'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        {{ $errors->first('week_end') }}
    </div>
    @endif

    <div id="admin-peringatan-dini-filter-target">
    <form id="admin-peringatan-dini-filter-form" method="GET" action="{{ route('admin.peringatan-dini') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Nama siswa"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Kategori Peringatan</label>
                    <select
                        name="category"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="all" {{ ($filters['category'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="rendah" {{ ($filters['category'] ?? '') === 'rendah' ? 'selected' : '' }}>Rendah</option>
                        <option value="sedang" {{ ($filters['category'] ?? '') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="tinggi" {{ ($filters['category'] ?? '') === 'tinggi' ? 'selected' : '' }}>Tinggi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select
                        name="jurusan_id"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua</option>
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) ($filters['jurusan_id'] ?? '') === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select
                        name="tahun_ajaran"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua</option>
                        @foreach ($tahunAjaranOptions as $tahun)
                        <option value="{{ $tahun }}" {{ (string) ($filters['tahun_ajaran'] ?? '') === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.peringatan-dini') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Jalankan Perhitungan Peringatan Dini</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="text-xs text-gray-500 mb-1">Periode Terakhir</div>
                <div class="text-sm font-semibold text-gray-900">
                    @if ($weekStart && $weekEnd)
                    {{ $weekStart->format('d M Y') }} - {{ $weekEnd->format('d M Y') }}
                    @else
                    Belum ada perhitungan
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="text-xs text-gray-500 mb-1">Status Perhitungan</div>
                <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs {{ $weekStart ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $weekStart ? 'Tersedia' : 'Belum ada' }}
                    </span>
                    <span class="{{ $weekStart ? 'text-green-700' : 'text-red-700' }}">
                        {{ $weekStart ? 'Data periode terakhir siap' : 'Silakan jalankan perhitungan' }}
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.peringatan-dini.run') }}">
            @csrf
            <input type="hidden" name="tahun_ajaran" value="{{ $filters['tahun_ajaran'] ?? '' }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Mulai</label>
                    <input
                        type="date"
                        name="week_start"
                        value="{{ old('week_start', $weekStart?->toDateString() ?? now()->subDays(6)->toDateString()) }}"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal Akhir</label>
                    <input
                        type="date"
                        name="week_end"
                        value="{{ old('week_end', $weekEnd?->toDateString() ?? now()->toDateString()) }}"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:bg-purple-700 transition-all text-sm font-medium">
                        Hitung Peringatan Mingguan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jurusan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Skor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($riskScores as $row)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format((float) $row->score, 4) }}</td>
                        <td class="px-6 py-4 text-sm">
                            @php
                            $catClass = match ($row->category) {
                            'tinggi' => 'bg-red-50 text-red-700 border border-red-200',
                            'sedang' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                            default => 'bg-green-50 text-green-700 border border-green-200',
                            };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $catClass }}">
                                {{ ucfirst($row->category) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                type="button"
                                class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                @click="
                                    detailOpen = true;
                                    detailNama = @js($row->siswa?->user?->name ?? '-');
                                    detailJurusan = @js($row->siswa?->jurusan?->nama ?? '-');
                                    detailData = @js($detailByRiskId[$row->id] ?? []);
                                ">
                                Lihat Detail
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if ($row->siswa?->user?->email)
                                <a
                                    href="mailto:{{ $row->siswa->user->email }}"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium">
                                    Hubungi
                                </a>
                                @else
                                <span class="text-xs text-gray-400 italic">Email kosong</span>
                                @endif
                                <a
                                    href="{{ route('admin.penempatan', ['tab' => 'langsung']) }}"
                                    class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-xs font-medium">
                                    Penempatan Langsung
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            Belum ada hasil peringatan dini untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($riskScores, 'total'))
    <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
        <div>
            Menampilkan {{ $riskScores->count() }} dari {{ $riskScores->total() }} data peringatan
        </div>
        <div>
            {{ $riskScores->links() }}
        </div>
    </div>
    @endif
    </div>

    {{-- Modal Detail --}}
    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="flex items-start justify-between p-6 border-b">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">Detail Perhitungan Peringatan Dini</h4>
                    <p class="text-xs text-gray-500 mt-1">
                        <span x-text="detailNama"></span>
                        <span class="mx-1 text-gray-300">•</span>
                        <span x-text="detailJurusan"></span>
                    </p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="detailOpen = false">✕</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Target Logbook</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.target_logs ?? '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Total Logbook</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.total_logs ?? '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Terlambat</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.late_logs ?? '-'"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs text-gray-500">Skor Frekuensi</div>
                        <div class="text-base font-semibold text-gray-900" x-text="detailData.freq_score !== undefined ? Number(detailData.freq_score).toFixed(3) : '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs text-gray-500">Skor Ketepatan</div>
                        <div class="text-base font-semibold text-gray-900" x-text="detailData.late_score !== undefined ? Number(detailData.late_score).toFixed(3) : '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs text-gray-500">Skor Presensi</div>
                        <div class="text-base font-semibold text-gray-900" x-text="detailData.absensi_score !== undefined ? Number(detailData.absensi_score).toFixed(3) : '-'"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Target Presensi</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.target_absensi ?? '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Presensi Valid</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.valid_absensi ?? '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Total Presensi</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="detailData.total_absensi ?? '-'"></div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-3 text-sm">
                    <div class="text-xs text-gray-500">Laporan Industri</div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="font-semibold text-gray-900" x-text="detailData.laporan_status ?? '-'"></span>
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700" x-text="detailData.laporan_score !== undefined ? Number(detailData.laporan_score).toFixed(2) : '-'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
@include('partials.ajax-filter-script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.setupAjaxFilter({
            formId: 'admin-peringatan-dini-filter-form',
            targetId: 'admin-peringatan-dini-filter-target',
            debounce: 500,
        });
    });
</script>
