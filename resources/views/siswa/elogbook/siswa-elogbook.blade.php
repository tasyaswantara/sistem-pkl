@section('title', 'E-Logbook')

@php
    use App\Enums\PenempatanStatus;
@endphp

<x-admin-layout>
    @php
        $statusLabels = [
            PenempatanStatus::BELUM_MEMILIH->value => 'Belum memilih',
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'Menunggu konfirmasi',
            PenempatanStatus::DITOLAK_SEKOLAH->value => 'Ditolak sekolah',
            PenempatanStatus::PROSES_PENGAJUAN->value => 'Proses pengajuan',
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value => 'Pengajuan ditolak industri',
            PenempatanStatus::PROSES_WAWANCARA->value => 'Proses wawancara',
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
        ];
        $penempatanStatus = $penempatan?->status;
        $penempatanStatusLabel = $statusLabels[$penempatanStatus] ?? 'Belum tersedia';
        $penempatanStatusClass = match ($penempatanStatus) {
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            PenempatanStatus::PROSES_WAWANCARA->value => 'bg-sky-50 text-sky-700 border border-sky-200',
            PenempatanStatus::PROSES_PENGAJUAN->value,
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'bg-amber-50 text-amber-700 border border-amber-200',
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
            PenempatanStatus::DITOLAK_SEKOLAH->value => 'bg-rose-50 text-rose-700 border border-rose-200',
            default => 'bg-slate-50 text-slate-600 border border-slate-200',
        };
    @endphp

    <div x-data="{ editOpen: false, editAction: '', editTanggal: '', editAktivitas: '' }" class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between animate-fade-up">
            <div>
                <div class="text-sm text-slate-500 mb-2">Dashboard → E-Logbook</div>
                <h1 class="text-slate-900 text-3xl font-semibold tracking-tight">E-Logbook Siswa</h1>
                <p class="text-slate-600 text-sm max-w-2xl mt-2">
                    Catat aktivitas harian dan pantau validasi industri dengan rapi.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-medium text-slate-600 border border-slate-200 shadow-sm">
                Industri:
                <span class="text-slate-900">{{ $penempatan?->industri?->nama_industri ?? 'Belum ditetapkan' }}</span>
            </div>
        </div>

        @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 animate-fade-up">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 animate-fade-up">
            <div class="font-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-up">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-widest text-slate-400 mb-2">Ringkas</div>
                <div class="text-2xl font-semibold text-slate-900">{{ $logbooks->total() }}</div>
                <div class="text-xs text-slate-500 mt-1">Total logbook tercatat</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-widest text-slate-400 mb-2">Status penempatan</div>
                <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $penempatanStatusClass }}">
                    {{ $penempatanStatusLabel }}
                </div>
                <div class="text-xs text-slate-500 mt-3">Pantau proses penempatan Anda.</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm animate-fade-up">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Tambah Logbook</h3>
                    <p class="text-sm text-slate-500">Isi aktivitas hari ini sebelum pulang.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs text-slate-500 border border-slate-200">
                    Status:
                    <span class="font-semibold text-slate-700">{{ $penempatanStatusLabel }}</span>
                </div>
            </div>

            @if (!$penempatan || $penempatan?->status !== PenempatanStatus::DITERIMA_INDUSTRI->value)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Logbook hanya bisa diisi setelah penempatan diterima industri.
            </div>
            @endif

            <form method="POST" action="{{ route('siswa.elogbook.store') }}" class="mt-5 grid grid-cols-1 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal') }}"
                        class="w-full px-3 py-3 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Aktivitas</label>
                    <textarea name="aktivitas" rows="4"
                        class="w-full px-3 py-3 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500"
                        placeholder="Tuliskan aktivitas utama dan hasilnya. Contoh: Menyusun laporan produksi, melakukan pengecekan alat, koordinasi dengan pembimbing.">{{ old('aktivitas') }}</textarea>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Industri aktif:
                        <span class="font-semibold text-slate-700">{{ $penempatan?->industri?->nama_industri ?? 'Belum ditetapkan' }}</span>
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-medium shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $penempatan?->status === PenempatanStatus::DITERIMA_INDUSTRI->value ? '' : 'disabled' }}>
                        Simpan Logbook
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4 animate-fade-up">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Riwayat Logbook</h3>
                <div class="text-xs text-slate-500">Total halaman: {{ $logbooks->lastPage() }}</div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Industri</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Aktivitas</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Catatan Industri</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Pesan Guru</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($logbooks as $logbook)
                            @php
                                $statusClass = match ($logbook->status_validasi) {
                                    'disetujui' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                    'ditolak' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                    default => 'bg-amber-50 text-amber-700 border border-amber-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="px-4 py-3 text-sm text-slate-700 w-[130px]">{{ $logbook->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 w-[220px]">
                                    <div class="font-medium text-slate-900">{{ $logbook->industri?->nama_industri ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 w-[360px]">
                                    <div class="whitespace-pre-line">{{ $logbook->aktivitas }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($logbook->status_validasi) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 w-[260px]">
                                    <div class="whitespace-pre-line text-slate-600">{{ $logbook->catatan_industri ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 w-[260px]">
                                    @if ($logbook->komentar->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach ($logbook->komentar as $komentar)
                                        <div class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                                            {{ $komentar->komentar }}
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-slate-400 italic">Belum ada pesan.</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                            class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50"
                                            @click="editOpen = true; editAction = @js(route('siswa.elogbook.update', $logbook->id)); editTanggal = @js($logbook->tanggal?->format('Y-m-d')); editAktivitas = @js($logbook->aktivitas);">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('siswa.elogbook.destroy', $logbook->id) }}"
                                            onsubmit="return confirm('Hapus logbook ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 text-xs border border-rose-200 text-rose-600 rounded-lg hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="7">
                                    Belum ada logbook.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $logbooks->links() }}
        </div>

        <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full border border-slate-200">
                <div class="flex items-center justify-between p-5 border-b border-slate-200">
                    <h4 class="text-base font-semibold text-slate-900">Ubah Logbook</h4>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="editOpen = false">✕</button>
                </div>
                <form method="POST" :action="editAction" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Tanggal</label>
                        <input type="date" name="tanggal" x-model="editTanggal"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Aktivitas</label>
                        <textarea name="aktivitas" rows="3" x-model="editAktivitas"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500"></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 text-sm" @click="editOpen = false">
                            Batal
                        </button>
                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-medium">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
