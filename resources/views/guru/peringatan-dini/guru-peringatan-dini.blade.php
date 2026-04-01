@section('title', 'Peringatan Dini Siswa')

<x-admin-layout>
    <div x-data="{
            detailOpen: false,
            detailNama: '',
            detailJurusan: '',
            detailData: {},
        }">
        <div class="mb-8 animate-fade-up">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Peringatan Dini Siswa</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Peringatan Dini Siswa Bimbingan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Fitur ini digunakan untuk mengidentifikasi siswa bimbingan yang sudah diterima industri namun berpotensi mengalami masalah selama PKL, berdasarkan parameter tertentu yang dihitung menjadi skor peringatan.
            </p>
        </div>

        <div id="guru-peringatan-dini-filter-target">
        <form id="guru-peringatan-dini-filter-form" method="GET" action="{{ route('guru.peringatan-dini') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6 animate-fade-up">
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
                    <a href="{{ route('guru.peringatan-dini') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden animate-fade-up">
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
                                @if ($row->siswa?->user?->email)
                                <a
                                    href="mailto:{{ $row->siswa->user->email }}"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium">
                                    Hubungi via Email
                                </a>
                                @else
                                <span class="text-xs text-gray-400 italic">Email kosong</span>
                                @endif
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
        <template x-teleport="body">
            <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-teal-600">Detail Risiko</p>
                                <h4 class="mt-1 text-lg font-semibold text-gray-900">Perhitungan Peringatan Dini</h4>
                                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                    <span class="font-medium text-gray-700" x-text="detailNama"></span>
                                    <span class="text-gray-300">•</span>
                                    <span x-text="detailJurusan"></span>
                                </p>
                            </div>
                            <button type="button" class="rounded-full border border-gray-200 p-2 text-gray-400 transition hover:bg-white hover:text-gray-600" @click="detailOpen = false">✕</button>
                        </div>
                    </div>

                    <div class="space-y-6 px-6 py-6">
                        <div class="rounded-2xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-sm text-teal-900">
                            Detail ini menampilkan indikator utama yang dipakai sistem untuk membaca potensi risiko siswa selama periode penilaian.
                        </div>

                        <section class="space-y-3">
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900">Aktivitas Mingguan</h5>
                                <p class="mt-1 text-xs text-gray-500">Ringkasan target kegiatan dan realisasi logbook selama periode berjalan.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Target Mingguan</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900" x-text="detailData.target_logs ?? '-'"></div>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Total Logbook</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900" x-text="detailData.total_logs ?? '-'"></div>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Presensi Valid</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900" x-text="detailData.valid_absensi ?? '-'"></div>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-3">
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900">Status Kehadiran</h5>
                                <p class="mt-1 text-xs text-gray-500">Hari izin dan alpha digunakan untuk membaca konsistensi kehadiran siswa.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Hari Izin</div>
                                    <div class="mt-2 text-2xl font-semibold text-amber-900" x-text="detailData.izin_days ?? '-'"></div>
                                </div>
                                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Hari Alpha</div>
                                    <div class="mt-2 text-2xl font-semibold text-rose-900" x-text="detailData.alpha_days ?? '-'"></div>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-3">
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900">Status Laporan Industri</h5>
                                <p class="mt-1 text-xs text-gray-500">Status laporan industri tetap menjadi salah satu indikator pendukung risiko.</p>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Laporan Industri</div>
                                        <div class="mt-1 text-base font-semibold text-gray-900" x-text="detailData.laporan_status ?? '-'"></div>
                                    </div>
                                    <div class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                        Skor <span class="ml-1 font-semibold" x-text="detailData.laporan_score !== undefined ? Number(detailData.laporan_score).toFixed(2) : '-'"></span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
@include('partials.ajax-filter-script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.setupAjaxFilter({
            formId: 'guru-peringatan-dini-filter-form',
            targetId: 'guru-peringatan-dini-filter-target',
            debounce: 500,
        });
    });
</script>
