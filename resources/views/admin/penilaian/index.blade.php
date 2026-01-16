@section('title', 'Penilaian')

<x-admin-layout>
    <div x-data="{ detailOpen: false, detailNama: '', detailIndustri: '', detailList: [], detailTotal: '' }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard -> Penilaian</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penilaian Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Monitoring penilaian PKL dari industri, termasuk detail aspek penilaian.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.penilaian') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Filter Penilaian</h3>
            </div>

            <div class="flex flex-wrap items-end gap-4 mb-4">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select name="tahun_ajaran" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua</option>
                        @foreach ($tahunAjaranList as $tahun)
                        <option value="{{ $tahun }}" {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
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
                    <select name="industri_id" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua Industri</option>
                        @foreach ($industriOptions as $industri)
                        <option value="{{ $industri->id }}" {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                            {{ $industri->nama_industri }}
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
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-medium">
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.penilaian') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
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
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Nilai</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catatan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($penilaianList as $row)
                        @php
                        $detailItems = $row->detailPenilaian->map(function ($detail) {
                        return [
                        'aspek' => $detail->aspekPenilaian?->nama_aspek ?? '-',
                        'bobot' => $detail->aspekPenilaian?->bobot ?? null,
                        'nilai' => $detail->nilai,
                        ];
                        })->values();
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->tanggal_penilaian?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->catatan ? \Illuminate\Support\Str::limit($row->catatan, 80) : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                    @click="detailOpen = true; detailNama = @js($row->siswa?->user?->name ?? '-'); detailIndustri = @js($row->industri?->nama_industri ?? '-'); detailList = @js($detailItems); detailTotal = @js($row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-');">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada data penilaian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 text-sm text-gray-500">
            Menampilkan {{ $penilaianList->count() }} data penilaian
        </div>

        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Detail Penilaian</h4>
                        <p class="text-xs text-gray-500">
                            <span x-text="detailNama"></span> · <span x-text="detailIndustri"></span>
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="detailOpen = false">✕</button>
                </div>
                <div class="p-4">
                    <template x-if="detailList.length === 0">
                        <div class="text-sm text-gray-500 italic">Belum ada detail penilaian.</div>
                    </template>
                    <template x-if="detailList.length > 0">
                        <div class="space-y-3">
                            <template x-for="item in detailList" :key="item.aspek">
                                <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="item.aspek"></div>
                                        <div class="text-xs text-gray-500" x-show="item.bobot !== null">
                                            Bobot: <span x-text="(item.bobot * 100).toFixed(0) + '%'"></span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700">
                                        Nilai <span x-text="Number(item.nilai).toFixed(2)"></span>
                                    </span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between border border-emerald-200 rounded-lg px-3 py-2 bg-emerald-50">
                                <span class="text-sm font-semibold text-emerald-700">Total Nilai</span>
                                <span class="text-sm font-semibold text-emerald-700" x-text="detailTotal"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>