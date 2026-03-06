@section('title', 'Dashboard Siswa')
@php
    use App\Enums\PilihanSiswa;
    use App\Enums\PenempatanStatus;

    $status = $penempatan?->status ?? PenempatanStatus::BELUM_MEMILIH->value;
    $statusClass = match ($status) {
        PenempatanStatus::DITERIMA_INDUSTRI->value => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        PenempatanStatus::PROSES_WAWANCARA->value => 'bg-sky-100 text-sky-700 border-sky-200',
        PenempatanStatus::MENUNGGU_KONFIRMASI->value,
        PenempatanStatus::PROSES_PENGAJUAN->value
            => 'bg-amber-100 text-amber-700 border-amber-200',
        PenempatanStatus::DITOLAK_SEKOLAH->value,
        PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
        PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value
            => 'bg-rose-100 text-rose-700 border-rose-200',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $pilihan = $penempatan?->pilihan_siswa;
    $industriAktif =
        $pilihan === PilihanSiswa::USULAN_LAIN->value
            ? $penempatan?->usulanIndustri?->nama_industri
            : $penempatan?->industri?->nama_industri;

    $timelineMeta = [
        'done' => [
            'dot' => 'bg-emerald-500',
            'line' => 'bg-emerald-500',
            'card' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        ],
        'current' => ['dot' => 'bg-sky-500', 'line' => 'bg-sky-300', 'card' => 'border-sky-200 bg-sky-50 text-sky-800'],
        'failed' => [
            'dot' => 'bg-rose-500',
            'line' => 'bg-rose-300',
            'card' => 'border-rose-200 bg-rose-50 text-rose-800',
        ],
        'pending' => [
            'dot' => 'bg-gray-300',
            'line' => 'bg-gray-200',
            'card' => 'border-gray-200 bg-gray-50 text-gray-700',
        ],
    ];

    $usulanErrorKeys = ['nama_industri', 'email', 'kapasitas', 'alamat', 'kontak', 'keterangan'];
    $hasUsulanErrors = collect($usulanErrorKeys)->contains(fn($key) => $errors->has($key));
    $berkasErrorKeys = [
        'bpjs_file',
        'kartu_pelajar_file',
        'foto_profil_file',
        'cv_link',
        'portofolio_links',
        'portofolio_links.*',
    ];
    $hasBerkasErrors = collect($berkasErrorKeys)->contains(fn($key) => $errors->has($key));
    $initialPortofolioLinks = old('portofolio_links', $siswa->portofolio_links ?? []);
    if (!is_array($initialPortofolioLinks)) {
        $initialPortofolioLinks = [];
    }
@endphp

<x-siswa-layout>
    <div x-data="siswaDashboard(@js($calendarEventMap), @js($hasUsulanErrors), @js($hasBerkasErrors), @js($initialPortofolioLinks))" class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any() && !$hasUsulanErrors)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="mb-1 font-semibold">Terjadi kesalahan:</div>
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section
            class="rounded-2xl bg-gradient-to-b from-[#1a4d3e] to-[#0f2e24] p-5 shadow-[0_14px_30px_rgba(8,24,19,0.28)] sm:p-6">
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1.7fr)_minmax(340px,1fr)] lg:items-center lg:gap-6">
                <div class="flex items-center gap-5">
                    <div
                        class="relative h-24 w-24 overflow-hidden rounded-full border border-white/20 bg-white/10 sm:h-36 sm:w-36">
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Foto siswa" class="h-full w-full object-cover">
                        @else
                            <img src="{{ asset('assets/images/avatar-placeholder.svg') }}" alt="Avatar default"
                                class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div>
                        <div class="text-sm uppercase tracking-[0.12em] text-emerald-100">Dashboard Harian Siswa</div>
                        <h1 class="text-2xl font-semibold text-white sm:text-3xl">{{ $siswa->user?->name }}</h1>
                        <p class="text-base text-emerald-100">Kelas {{ $siswa->kelas ?? '-' }} ·
                            {{ $siswa->jurusan?->nama ?? '-' }}</p>
                        <div
                            class="mt-3 inline-flex rounded-full border px-3 py-1.5 text-sm font-medium {{ $statusClass }}">
                            {{ $statusLabels[$status] ?? 'Belum memilih industri' }}
                        </div>
                    </div>
                </div>

                <div class="w-full rounded-xl bg-white/10 p-5 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.12)]">
                    <p class="text-base font-medium text-white">Aksi Hari Ini</p>
                    <p class="mt-1 text-base text-emerald-100">{{ $primaryAction['description'] }}</p>
                    @if ($industriAktif)
                        <p class="mt-2 text-sm text-emerald-200">Industri aktif: {{ $industriAktif }}</p>
                    @endif
                    @if (($primaryAction['route'] ?? '') === 'siswa.berkas')
                        <button type="button" @click="berkasOpen = true"
                            class="mt-4 inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-[#0f2e24] transition hover:bg-emerald-50">
                            {{ $primaryAction['label'] }}
                        </button>
                    @else
                        <a href="{{ route($primaryAction['route']) }}"
                            class="mt-4 inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-[#0f2e24] transition hover:bg-emerald-50">
                            {{ $primaryAction['label'] }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-5">
            <div class="rounded-2xl bg-white p-5 shadow-[0_10px_24px_rgba(15,46,36,0.12)] xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Timeline Penempatan</h2>
                    <a href="{{ route('siswa.penempatan') }}"
                        class="text-xs text-emerald-600 hover:text-emerald-700">Detail</a>
                </div>

                <div class="space-y-3">
                    @foreach ($timelineSteps as $index => $step)
                        @php
                            $meta = $timelineMeta[$step['state'] ?? 'pending'];
                        @endphp
                        <div class="flex items-stretch gap-3">
                            <div class="flex w-4 shrink-0 flex-col items-center">
                                <div class="mt-1 h-3 w-3 rounded-full {{ $meta['dot'] }}"></div>
                                @if (!$loop->last)
                                    <div class="-mb-3 mt-1 w-0.5 flex-1 min-h-[56px] {{ $meta['line'] }}"></div>
                                @endif
                            </div>
                            <div class="flex-1 rounded-xl border px-3 py-4 {{ $meta['card'] }}">
                                <div class="text-sm font-semibold">{{ $step['label'] }}</div>
                                <div class="text-xs">{{ $step['hint'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-[0_10px_24px_rgba(15,46,36,0.12)] xl:col-span-3">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Kalender {{ $monthLabel }}</h2>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('siswa.dashboard', ['month' => $prevMonth]) }}"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Sebelumnya</a>
                        <a href="{{ route('siswa.dashboard') }}"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Bulan
                            Ini</a>
                        <a href="{{ route('siswa.dashboard', ['month' => $nextMonth]) }}"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Berikutnya</a>
                    </div>
                </div>

                <div
                    class="mb-2 grid grid-cols-7 gap-2 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <div>Min</div>
                    <div>Sen</div>
                    <div>Sel</div>
                    <div>Rab</div>
                    <div>Kam</div>
                    <div>Jum</div>
                    <div>Sab</div>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    @foreach ($calendarCells as $cell)
                        @php
                            $inCurrentMonth = $cell['in_current_month'];
                            $hasEvents = ($cell['event_count'] ?? 0) > 0;
                            $baseClass = $inCurrentMonth
                                ? 'border-gray-200 bg-white text-gray-900'
                                : 'border-gray-100 bg-gray-50 text-gray-400';
                            $activeClass = $hasEvents ? 'hover:border-emerald-300 hover:bg-emerald-50/60' : '';
                        @endphp
                        <button type="button"
                            @if ($hasEvents) @click="openDay('{{ $cell['date'] }}')" @endif
                            class="min-h-[72px] rounded-xl border p-2 text-left transition {{ $baseClass }} {{ $activeClass }} {{ !$hasEvents ? 'cursor-default' : '' }}">
                            <div class="flex items-start justify-between">
                                <span class="text-sm font-medium">{{ $cell['day'] }}</span>
                                @if ($hasEvents)
                                    <span
                                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-100 px-1 text-[10px] font-semibold text-emerald-700">
                                        {{ $cell['event_count'] }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 flex items-center gap-1.5">
                                @if ($cell['has_wawancara'])
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-500"
                                        title="Wawancara"></span>
                                @endif
                                @if ($cell['has_perizinan'])
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"
                                        title="Perizinan"></span>
                                @endif
                                @if ($hasEvents)
                                    <span class="text-[10px] text-gray-500">Lihat detail</span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-3 text-xs text-gray-500">
                    <div class="inline-flex items-center gap-1.5"><span
                            class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Wawancara</div>
                    <div class="inline-flex items-center gap-1.5"><span
                            class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Perizinan</div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-[0_10px_24px_rgba(15,46,36,0.12)]">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Rekomendasi Industri</h2>
                    <p class="text-sm text-gray-500">Pilih industri rekomendasi SAW atau usulkan industri baru.</p>
                </div>
                <button type="button" @click="usulanOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                    <span
                        class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">+</span>
                    Usulkan Industri
                </button>
            </div>

            @if (!$berkasComplete)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Lengkapi berkas terlebih dahulu sebelum memilih rekomendasi atau mengusulkan industri.
                </div>
            @endif

            @if (!$canUpdatePilihan)
                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Pilihan industri saat ini sudah terkunci karena proses penempatan sedang berjalan atau sudah
                    selesai.
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-[680px] w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Peringkat</th>
                            <th class="px-4 py-3">Industri</th>
                            <th class="px-4 py-3">Nilai</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($rekomendasi as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                        {{ $row->peringkat }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $row->industri?->nama_industri ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $row->industri?->alamat ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ number_format(($row->nilai_preferensi ?? 0) * 100, 2) }}%</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('siswa.penempatan.pilih') }}">
                                        @csrf
                                        <input type="hidden" name="industri_id" value="{{ $row->industri_id }}">
                                        <button type="submit"
                                            class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            {{ !$berkasComplete || !$canUpdatePilihan ? 'disabled' : '' }}>
                                            Pilih
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada rekomendasi
                                    industri untuk Anda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-[0_10px_24px_rgba(15,46,36,0.12)]">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Penilaian Siswa</h2>
                    <p class="text-sm text-gray-500">Riwayat penilaian dari industri.</p>
                </div>
                <a href="{{ route('siswa.penilaian') }}"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Lihat Halaman Penilaian
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[680px] w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Industri</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Total Nilai</th>
                            <th class="px-4 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($penilaianList as $row)
                            @php
                                $detailItems = $row->detailPenilaian
                                    ->map(function ($detail) {
                                        return [
                                            'aspek' => $detail->aspekPenilaian?->nama_aspek ?? '-',
                                            'bobot' => $detail->aspekPenilaian?->bobot ?? null,
                                            'nilai' => $detail->nilai,
                                        ];
                                    })
                                    ->values();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $row->tanggal_penilaian?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        @click="openPenilaianDetail(
                                            {{ \Illuminate\Support\Js::from($row->industri?->nama_industri ?? '-') }},
                                            {{ \Illuminate\Support\Js::from($detailItems) }},
                                            {{ \Illuminate\Support\Js::from($row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-') }}
                                        )">
                                        Lihat Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data
                                    penilaian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $penilaianList->links() }}
            </div>
        </section>

        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="closeModal()"></div>
                <div
                    class="relative z-10 w-full max-w-xl rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Agenda <span
                                x-text="selectedDateLabel"></span></h3>
                        <button type="button"
                            class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600"
                            @click="closeModal()">Tutup</button>
                    </div>

                    <div class="max-h-[360px] space-y-3 overflow-y-auto pr-1">
                        <template x-for="(event, idx) in selectedEvents" :key="idx">
                            <div class="rounded-lg bg-white p-3 shadow-[0_8px_20px_rgba(15,46,36,0.10)]">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900" x-text="event.title"></p>
                                    <span class="rounded-full px-2 py-0.5 text-[11px]"
                                        :class="event.type === 'wawancara' ? 'bg-sky-100 text-sky-700' :
                                            'bg-amber-100 text-amber-700'"
                                        x-text="event.type === 'wawancara' ? 'Wawancara' : 'Perizinan'"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-600" x-text="event.subtitle"></p>
                                <div class="mt-2 flex gap-3 text-xs text-gray-500">
                                    <span>Waktu: <span x-text="event.time"></span></span>
                                    <span>Status: <span x-text="event.status"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="usulanOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="usulanOpen = false"></div>
                <div
                    class="relative z-10 w-full max-w-2xl rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Usulkan Industri Baru</h3>
                        <button type="button" aria-label="Tutup popup usulan"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200"
                            @click="usulanOpen = false">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('siswa.penempatan.usulan') }}"
                        class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @csrf

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Nama Industri</label>
                            <input type="text" name="nama_industri" value="{{ old('nama_industri') }}"
                                placeholder="Contoh: PT Maju Jaya Digital"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                required>
                            @error('nama_industri')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Email Industri</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                placeholder="Contoh: hrd@industri.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                required>
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Kapasitas</label>
                            <input type="number" min="1" name="kapasitas" value="{{ old('kapasitas') }}"
                                placeholder="Contoh: 20"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                required>
                            @error('kapasitas')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Kontak (Opsional)</label>
                            <input type="text" name="kontak" value="{{ old('kontak') }}"
                                placeholder="Contoh: 0812xxxxxxx"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            @error('kontak')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Alamat</label>
                            <textarea name="alamat" rows="3" placeholder="Contoh: Jl. Soekarno-Hatta No. 10, Malang"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                required>{{ old('alamat') }}</textarea>
                            @error('alamat')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Keterangan (Opsional)</label>
                            <textarea name="keterangan" rows="2" placeholder="Tambahkan catatan tambahan jika perlu"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('keterangan') }}</textarea>
                            @error('keterangan')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Kirim Usulan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="berkasOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="berkasOpen = false"></div>
                <div
                    class="relative z-10 w-full max-w-3xl rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-3">
                        <h3 class="text-base font-semibold text-gray-900">Pengajuan Berkas Siswa</h3>
                        <button type="button" aria-label="Tutup popup berkas"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200"
                            @click="berkasOpen = false">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>

                    @php
                        $bpjsUrl = $siswa->bpjs_link
                            ? (\Illuminate\Support\Str::startsWith($siswa->bpjs_link, ['http://', 'https://'])
                                ? $siswa->bpjs_link
                                : \Illuminate\Support\Facades\Storage::url($siswa->bpjs_link))
                            : null;
                        $kartuUrl = $siswa->kartu_pelajar_link
                            ? (\Illuminate\Support\Str::startsWith($siswa->kartu_pelajar_link, ['http://', 'https://'])
                                ? $siswa->kartu_pelajar_link
                                : \Illuminate\Support\Facades\Storage::url($siswa->kartu_pelajar_link))
                            : null;
                        $fotoProfilUrl = $siswa->foto_profil_link
                            ? (\Illuminate\Support\Str::startsWith($siswa->foto_profil_link, ['http://', 'https://'])
                                ? $siswa->foto_profil_link
                                : \Illuminate\Support\Facades\Storage::url($siswa->foto_profil_link))
                            : asset('assets/images/avatar-placeholder.svg');
                    @endphp

                    <form method="POST" action="{{ route('siswa.berkas.update') }}" enctype="multipart/form-data"
                        class="max-h-[72vh] space-y-4 overflow-y-auto pr-1">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Upload BPJS (JPG/PNG, max
                                    10MB)</label>
                                <input type="file" name="bpjs_file" accept="image/png,image/jpeg"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                @if ($bpjsUrl)
                                    <a href="{{ $bpjsUrl }}" target="_blank"
                                        class="mt-2 inline-flex text-xs text-emerald-700 hover:underline">Lihat BPJS
                                        saat ini</a>
                                @endif
                                @error('bpjs_file')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Upload Kartu Pelajar
                                    (JPG/PNG, max 10MB)</label>
                                <input type="file" name="kartu_pelajar_file" accept="image/png,image/jpeg"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                @if ($kartuUrl)
                                    <a href="{{ $kartuUrl }}" target="_blank"
                                        class="mt-2 inline-flex text-xs text-emerald-700 hover:underline">Lihat kartu
                                        pelajar saat ini</a>
                                @endif
                                @error('kartu_pelajar_file')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Upload Foto Profil
                                    (JPG/PNG, max 10MB, opsional)</label>
                                <div class="flex items-start gap-4">
                                    <img src="{{ $fotoProfilUrl }}" alt="Foto profil saat ini"
                                        class="h-16 w-16 rounded-full border border-gray-200 object-cover">
                                    <div class="flex-1">
                                        <input type="file" name="foto_profil_file" accept="image/png,image/jpeg"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                        @if ($siswa->foto_profil_link)
                                            <a href="{{ $fotoProfilUrl }}" target="_blank"
                                                class="mt-2 inline-flex text-xs text-emerald-700 hover:underline">Lihat
                                                foto profil saat ini</a>
                                        @endif
                                        @error('foto_profil_file')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Link CV</label>
                            <input type="url" name="cv_link" value="{{ old('cv_link', $siswa->cv_link) }}"
                                placeholder="https://drive.google.com/..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            @error('cv_link')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="block text-xs font-medium text-gray-700">Link Portofolio (boleh lebih
                                    dari satu)</label>
                                <button type="button"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                                    @click="addPortofolio()">
                                    Tambah Link
                                </button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(link, index) in portofolioLinks" :key="`portofolio-${index}`">
                                    <div class="flex items-center gap-2">
                                        <input type="url" name="portofolio_links[]"
                                            x-model="portofolioLinks[index]"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                            placeholder="https://drive.google.com/...">
                                        <button type="button"
                                            class="rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-600 hover:bg-gray-50"
                                            @click="removePortofolio(index)">
                                            Hapus
                                        </button>
                                    </div>
                                </template>
                                <div x-show="portofolioLinks.length === 0" class="text-xs italic text-gray-400">
                                    Belum ada link portofolio.
                                </div>
                            </div>
                            @error('portofolio_links')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('portofolio_links.*')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Simpan Berkas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="penilaianDetailOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="closePenilaianDetail()"></div>
                <div
                    class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Detail Penilaian</h3>
                            <p class="text-xs text-gray-500" x-text="penilaianDetailIndustri"></p>
                        </div>
                        <button type="button" aria-label="Tutup detail penilaian"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200"
                            @click="closePenilaianDetail()">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>

                    <div class="max-h-[380px] overflow-y-auto pr-1">
                        <template x-if="penilaianDetailList.length === 0">
                            <div class="text-sm text-gray-500 italic">Belum ada detail penilaian.</div>
                        </template>
                        <template x-if="penilaianDetailList.length > 0">
                            <div class="space-y-3">
                                <template x-for="(item, idx) in penilaianDetailList" :key="idx">
                                    <div
                                        class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="item.aspek"></div>
                                            <div class="text-xs text-gray-500" x-show="item.bobot !== null">
                                                Bobot: <span
                                                    x-text="(Number(item.bobot) * 100).toFixed(0) + '%'"></span>
                                            </div>
                                        </div>
                                        <span
                                            class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                            Nilai <span x-text="Number(item.nilai).toFixed(2)"></span>
                                        </span>
                                    </div>
                                </template>
                                <div
                                    class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                    <span class="text-sm font-semibold text-emerald-700">Total Nilai</span>
                                    <span class="text-sm font-semibold text-emerald-700"
                                        x-text="penilaianDetailTotal"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            function siswaDashboard(eventMap, openUsulanOnLoad, openBerkasOnLoad, initialPortofolioLinks) {
                return {
                    eventMap,
                    modalOpen: false,
                    usulanOpen: !!openUsulanOnLoad,
                    berkasOpen: !!openBerkasOnLoad,
                    penilaianDetailOpen: false,
                    selectedDate: '',
                    selectedDateLabel: '',
                    selectedEvents: [],
                    penilaianDetailIndustri: '',
                    penilaianDetailList: [],
                    penilaianDetailTotal: '',
                    portofolioLinks: Array.isArray(initialPortofolioLinks) ? initialPortofolioLinks : [],
                    openDay(dateKey) {
                        const events = this.eventMap[dateKey] || [];
                        if (!events.length) {
                            return;
                        }
                        this.selectedDate = dateKey;
                        this.selectedDateLabel = this.formatDate(dateKey);
                        this.selectedEvents = events;
                        this.modalOpen = true;
                    },
                    closeModal() {
                        this.modalOpen = false;
                    },
                    openPenilaianDetail(industri, detailList, totalNilai) {
                        this.penilaianDetailIndustri = industri;
                        this.penilaianDetailList = detailList || [];
                        this.penilaianDetailTotal = totalNilai;
                        this.penilaianDetailOpen = true;
                    },
                    closePenilaianDetail() {
                        this.penilaianDetailOpen = false;
                    },
                    addPortofolio() {
                        this.portofolioLinks.push('');
                    },
                    removePortofolio(index) {
                        this.portofolioLinks.splice(index, 1);
                    },
                    formatDate(dateKey) {
                        const date = new Date(`${dateKey}T00:00:00`);
                        return new Intl.DateTimeFormat('id-ID', {
                            weekday: 'long',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                        }).format(date);
                    },
                }
            }
        </script>
    @endpush
</x-siswa-layout>
