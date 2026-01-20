@section('title', 'Penilaian')

<x-admin-layout>
    <div x-data="{ detailOpen: false, detailIndustri: '', detailList: [], detailTotal: '' }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Penilaian</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penilaian</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Lihat rubrik dan hasil penilaian dari industri.
            </p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Rubrik Penilaian (Global)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach ($aspekList as $aspek)
                <div class="border border-gray-200 rounded-lg px-3 py-2">
                    <div class="text-sm font-medium text-gray-900">{{ $aspek->nama_aspek }}</div>
                    <div class="text-xs text-gray-500">Bobot: {{ number_format($aspek->bobot * 100, 0) }}%</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Nilai</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Detail</th>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->tanggal_penilaian?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                    @click="detailOpen = true; detailIndustri = @js($row->industri?->nama_industri ?? '-'); detailList = @js($detailItems); detailTotal = @js($row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-');">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="4">
                                Belum ada data penilaian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $penilaianList->links() }}
        </div>

        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Detail Penilaian</h4>
                        <p class="text-xs text-gray-500">
                            <span x-text="detailIndustri"></span>
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
