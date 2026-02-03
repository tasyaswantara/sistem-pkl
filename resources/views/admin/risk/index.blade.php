@section('title', 'Resiko PKL')

<x-admin-layout>
    <div x-data="{
            detailOpen: false,
            detailNama: '',
            detailJurusan: '',
            detailData: {},
        }">
    <div class="mb-8">
        <div class="text-sm text-gray-500 mb-2">Dashboard → Resiko PKL</div>
        <h1 class="text-gray-900 text-2xl font-semibold mb-2">Deteksi Resiko PKL</h1>
        <p class="text-gray-500 text-sm max-w-2xl">
            Lihat hasil perhitungan resiko mingguan dan jalankan perhitungan manual bila diperlukan.
        </p>
    </div>

    @if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Menjalankan Deteksi Resiko PKL</h3>

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

        <form method="POST" action="{{ route('admin.risk.calculate') }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all text-sm font-medium">
                Hitung Resiko Mingguan
            </button>
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
                            Belum ada hasil resiko untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Detail --}}
    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="flex items-start justify-between p-6 border-b">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">Detail Perhitungan Resiko</h4>
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

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs text-gray-500">Skor Frekuensi</div>
                        <div class="text-base font-semibold text-gray-900" x-text="detailData.freq_score !== undefined ? Number(detailData.freq_score).toFixed(3) : '-'"></div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs text-gray-500">Skor Ketepatan</div>
                        <div class="text-base font-semibold text-gray-900" x-text="detailData.late_score !== undefined ? Number(detailData.late_score).toFixed(3) : '-'"></div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-3 text-sm">
                    <div class="text-xs text-gray-500">Status Penempatan</div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="font-semibold text-gray-900" x-text="detailData.status ?? '-'"></span>
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700" x-text="detailData.status_score !== undefined ? Number(detailData.status_score).toFixed(2) : '-'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
