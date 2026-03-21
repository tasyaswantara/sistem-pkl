@section('title', 'Penilaian Industri')

<x-admin-layout>
    <div x-data="{
        open: false,
        action: '',
        siswaName: '',
        aspekList: @js($aspekList->map(fn($a) => ['id' => $a->id, 'nama' => $a->nama_aspek, 'bobot' => $a->bobot])),
        nilai: {},
    }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Penilaian</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penilaian Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Berikan penilaian sesuai rubrik untuk siswa yang magang di industri Anda.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Nilai</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($penempatanList as $row)
                        @php
                        $penilaian = $penilaianMap->get($row->siswa_id);
                        $detailMap = $penilaian?->detailPenilaian?->pluck('nilai', 'aspek_penilaian_id') ?? collect();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $penilaian?->total_nilai !== null ? number_format($penilaian->total_nilai, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                    @click="
                                        action = @js(route('industri.penilaian.store', $row->id));
                                        siswaName = @js($row->siswa?->user?->name ?? '-');
                                        nilai = @js($detailMap);
                                        open = true;
                                    ">
                                    Beri Nilai
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="4">
                                Belum ada siswa magang.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                    <div class="flex items-center justify-between p-4 border-b">
                        <div>
                            <h4 class="text-base font-semibold text-gray-900">Form Penilaian</h4>
                            <p class="text-xs text-gray-500">Siswa: <span x-text="siswaName"></span></p>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="open = false">✕</button>
                    </div>
                    <form method="POST" :action="action" class="p-4 space-y-3">
                        @csrf
                        <template x-for="aspek in aspekList" :key="aspek.id">
                            <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="aspek.nama"></div>
                                    <div class="text-xs text-gray-500">
                                        Bobot: <span x-text="(aspek.bobot * 100).toFixed(0) + '%'"></span>
                                    </div>
                                </div>
                                <input type="number" min="0" max="100" step="1"
                                    :name="`nilai[${aspek.id}]`"
                                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm text-right"
                                    :value="nilai[aspek.id] ?? ''"
                                    placeholder="0-100">
                            </div>
                        </template>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm" @click="open = false">
                                Batal
                            </button>
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                                Simpan Penilaian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
