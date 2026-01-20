@section('title', 'Penempatan PKL')

<x-admin-layout>
    <div x-data="{
            detailOpen: false,
            detailNama: '',
            detailJurusan: '',
            detailList: [],
            infoOpen: false,
            guruOpen: false,
            guruTargetId: null,
            guruTargetName: '',
            guruList: [],
        }">

        {{-- Header --}}
        <div id="konfigurasi-saw" class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Penempatan PKL</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penempatan Siswa PKL</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Kelola penempatan otomatis siswa PKL menggunakan metode SAW, konfirmasi pengajuan ke industri, serta penentuan
                guru pembimbing.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Pilih Jurusan & Tahun Ajaran --}}
        <form method="GET" action="{{ route('admin.penempatan') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Pilih Jurusan & Tahun Ajaran</h3>
                <span class="text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                    Untuk SAW & hasil penempatan
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) $selectedJurusan === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select name="tahun_ajaran" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        @foreach ($tahunAjaranList as $tahun)
                        <option value="{{ $tahun }}" {{ (string) $selectedTahun === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <input type="hidden" name="status" value="{{ $selectedStatus }}">
            <input type="hidden" name="q" value="{{ $search }}">
        </form>

        {{-- Konfigurasi SAW --}}
        <form method="POST" action="{{ route('admin.penempatan.bobot') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            @csrf
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-semibold text-gray-900">Konfigurasi Pembobotan SAW</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-blue-600 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-full">
                        Bobot per jurusan
                    </span>
                    <button type="button"
                        class="w-7 h-7 inline-flex items-center justify-center rounded-full border border-blue-200 text-blue-600 hover:bg-blue-50"
                        @click="infoOpen = true"
                        title="Info bobot SAW">
                        i
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-4">Atur bobot berdasarkan jurusan. Total bobot harus sama dengan 100%.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                        {{ $selectedJurusan ? ($jurusanOptions->firstWhere('id', $selectedJurusan)->nama ?? '-') : 'Pilih jurusan di filter' }}
                    </div>
                    <input type="hidden" name="jurusan_id" value="{{ $selectedJurusan }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                        {{ $selectedTahun ?? 'Pilih tahun ajaran di filter' }}
                    </div>
                    <input type="hidden" name="tahun_ajaran" value="{{ $selectedTahun }}">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-medium">
                        Simpan Bobot
                    </button>
                    <a href="{{ route('admin.penempatan', ['jurusan_id' => $selectedJurusan, 'tahun_ajaran' => $selectedTahun, 'status' => $selectedStatus, 'q' => $search]) }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                        Reset Bobot
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kriteria</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bobot</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($bobotKriteria as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row['kriteria'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 uppercase">
                                    {{ ucfirst($row['tipe']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative w-28">
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        name="bobot[{{ $row['id'] }}]"
                                        value="{{ $row['bobot'] * 100 }}"
                                        class="w-full pr-7 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-500">%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-4 py-3 text-sm text-gray-900" colspan="2">Total Bobot</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 text-green-700">
                                    <span class="text-sm">{{ number_format($totalBobot * 100, 2) }}%</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $isBobotValid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $isBobotValid ? 'Valid' : 'Belum valid' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>

        {{-- Info Modal --}}
        <div x-show="infoOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <h4 class="text-base font-semibold text-gray-900">Info Bobot SAW</h4>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="infoOpen = false">✕</button>
                </div>
                <div class="p-4 text-sm text-gray-700 space-y-3">
                    <p>
                        Bobot SAW adalah tingkat kepentingan tiap kriteria. Contoh: nilai akademik bobot 0,30 berarti kontribusinya
                        30% terhadap skor total.
                    </p>
                    <p>Alur perhitungan sederhana:</p>
                    <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                        <li>Ambil nilai siswa dan industri.</li>
                        <li>Normalisasi tiap kriteria.</li>
                        <li>Kalikan dengan bobot.</li>
                        <li>Jumlahkan menjadi skor akhir.</li>
                        <li>Urutkan industri berdasarkan skor tertinggi.</li>
                    </ol>
                    <p>
                        Karena bobot diset per jurusan, tiap jurusan bisa punya prioritas yang berbeda.
                    </p>
                </div>
                <div class="flex justify-end p-4 border-t bg-gray-50">
                    <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                        @click="infoOpen = false">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        {{-- Jalankan SAW --}}
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Menjalankan Penempatan Otomatis (SAW)</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-xs text-gray-500 mb-1">Jurusan Aktif</div>
                    <div class="text-sm font-semibold text-gray-900">
                        {{ $selectedJurusan ? ($jurusanOptions->firstWhere('id', $selectedJurusan)->nama ?? '-') : 'Pilih jurusan terlebih dahulu' }}
                    </div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="text-xs text-gray-500 mb-1">Status Bobot</div>
                    <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs {{ $isBobotValid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $isBobotValid ? 'Valid' : 'Belum valid' }}
                        </span>
                        <span class="{{ $isBobotValid ? 'text-green-700' : 'text-red-700' }}">
                            {{ $isBobotValid ? 'Bobot siap digunakan' : 'Total bobot harus 100%' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.penempatan.run-saw') }}">
                    @csrf
                    <input type="hidden" name="jurusan_id" value="{{ $selectedJurusan }}">
                    <input type="hidden" name="tahun_ajaran" value="{{ $selectedTahun }}">
                    <button
                        type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all text-sm font-medium shadow-lg shadow-purple-500/30 disabled:opacity-50"
                        {{ $selectedJurusan && $selectedTahun && $isBobotValid ? '' : 'disabled' }}>
                        Jalankan Penempatan Otomatis (SAW)
                    </button>
                </form>
                @if ($latestSawRun)
                <div class="px-4 py-2 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
                    <span class="text-sm text-green-700 font-medium">
                        Hasil SAW Tersedia ({{ $latestSawRun->run_at->format('d/m/Y H:i') }})
                    </span>
                </div>
                @else
                <div class="px-4 py-2 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-2">
                    <span class="text-sm text-yellow-700 font-medium">Belum ada hasil SAW</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Usulan Industri Siswa --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Usulan Industri Siswa</h3>
                <span class="text-xs text-gray-500">Menunggu konfirmasi admin</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri Usulan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kontak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" x-data="{ openId: null }">
                        @forelse ($usulanList as $usulan)
                        @php
                        $usulanStatusClass = match ($usulan->status) {
                        'disetujui' => 'bg-green-50 text-green-700 border border-green-200',
                        'ditolak' => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $usulan->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $usulan->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $usulan->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $usulan->nama_industri }}</div>
                                <div class="text-xs text-gray-500">{{ $usulan->alamat }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $usulan->email ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $usulan->kontak ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $usulanStatusClass }}">
                                    {{ ucfirst($usulan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($usulan->status === 'menunggu')
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1.5 text-xs border border-emerald-200 text-emerald-700 rounded-lg hover:bg-emerald-50"
                                        @click="openId = openId === {{ $usulan->id }} ? null : {{ $usulan->id }}">
                                        Setujui
                                    </button>
                                    <form method="POST" action="{{ route('admin.penempatan.usulan.reject', $usulan->id) }}"
                                        onsubmit="return confirm('Tolak usulan industri ini?')">
                                        @csrf
                                        <button class="px-3 py-1.5 text-xs border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                                @else
                                <span class="text-xs text-gray-500 italic">Sudah diproses</span>
                                @endif
                            </td>
                        </tr>
                        <tr x-show="openId === {{ $usulan->id }}" x-cloak>
                            <td colspan="7" class="px-4 pb-4">
                                <form method="POST" action="{{ route('admin.penempatan.usulan.approve', $usulan->id) }}"
                                    class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Akun *</label>
                                        <input name="email" type="email" value="{{ $usulan->email ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            placeholder="email@industri.com" required>
                                        <p class="mt-1 text-xs text-gray-500">Wajib diisi untuk membuat akun industri.</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Password</label>
                                        <input name="password" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            placeholder="Minimal 8 karakter" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Kapasitas</label>
                                        <input name="kapasitas" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            placeholder="Kapasitas" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Grade</label>
                                        <select name="grade" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                                            <option value="">Pilih Grade</option>
                                            @foreach (['A', 'B', 'C'] as $grade)
                                            <option value="{{ $grade }}">Grade {{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-4 flex justify-end">
                                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                                            Konfirmasi & Buat Akun
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada usulan industri dari siswa.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Filter --}}
        <form id="filter-penempatan" method="GET" action="{{ route('admin.penempatan') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Filter Data Penempatan</h3>
                <span class="text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                    Status data terbaru
                </span>
            </div>
            <p class="text-xs text-gray-500 mb-4">
                Filter ini hanya memengaruhi data tabel penempatan.
            </p>
            <input type="hidden" name="jurusan_id" value="{{ $selectedJurusan }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status Penempatan</label>
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        @foreach ($statusList as $value => $label)
                        <option value="{{ $value }}" {{ (string) $selectedStatus === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Nama siswa"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                            oninput="debouncedSubmit(this)">
                    </div>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('admin.penempatan') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                        Reset Filter
                    </a>
                </div>
            </div>
        </form>

        {{-- Hasil Penempatan --}}
        <div id="hasil-penempatan" class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Hasil Penempatan Siswa</h3>
                <div class="flex items-center gap-3 text-sm">
                    <div class="px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                        <span class="text-green-600 font-semibold">{{ $statusCounts['diterima_industri'] ?? 0 }}</span>
                        <span class="text-green-700 ml-1">Diterima</span>
                    </div>
                    <div class="px-3 py-1.5 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <span class="text-yellow-600 font-semibold">{{ $statusCounts['proses_pengajuan'] ?? 0 }}</span>
                        <span class="text-yellow-700 ml-1">Proses</span>
                    </div>
                    <div class="px-3 py-1.5 bg-red-50 border border-red-200 rounded-lg">
                        <span class="text-red-600 font-semibold">{{ $statusCounts['ditolak_industri'] ?? 0 }}</span>
                        <span class="text-red-700 ml-1">Ditolak</span>
                    </div>
                    <button class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium">
                        Refresh
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jurusan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Industri Rekomendasi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nilai Preferensi (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Peringkat</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pilihan Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Penempatan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Guru Pembimbing</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($penempatanData as $row)
                            @php
                            $namaSiswa = $row->siswa?->user?->name ?? '-';
                            $jurusanNama = $row->siswa?->jurusan?->nama ?? '-';
                            $industriNama = $row->industri?->nama_industri ?? null;
                            $usulanNama = $row->usulanIndustri?->nama_industri ?? null;
                            $guruNama = $row->guruPembimbing?->user?->name ?? null;
                            $rekomList = $rekomendasiBySiswa->get($row->siswa_id, collect());
                            $topRekom = $rekomList->first();
                            $rekomIndustriNama = $topRekom?->industri?->nama_industri ?? null;
                            $nilaiPreferensi = $topRekom?->nilai_preferensi;
                            $peringkat = $topRekom?->peringkat;
                            $detailItems = $rekomList->map(function ($item) {
                                return [
                                    'industri' => $item->industri?->nama_industri ?? '-',
                                    'skor' => $item->nilai_preferensi ?? 0,
                                    'rank' => $item->peringkat,
                                ];
                            })->values();
                            $status = $row->status;
                            $displayStatus = $statusLabels[$status] ?? $status;
                            $pilihan = $row->pilihan_siswa;
                            $displayPilihan = $pilihanLabels[$pilihan] ?? null;
                            $pilihanIndustri = $pilihan === 'usulan_lain'
                                ? $usulanNama
                                : $industriNama;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 text-sm">{{ $namaSiswa }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $jurusanNama }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($rekomIndustriNama)
                                    {{ $rekomIndustriNama }}
                                    @elseif ($industriNama)
                                    {{ $industriNama }}
                                    @else
                                    <span class="text-gray-400 italic">Belum ada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($nilaiPreferensi !== null)
                                    <span class="font-semibold text-purple-600">{{ number_format($nilaiPreferensi * 100, 2) }}%</span>
                                    @else
                                    <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($peringkat)
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-purple-100 text-purple-700 rounded-full font-semibold text-xs">
                                        {{ $peringkat }}
                                    </span>
                                    @else
                                    <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($displayPilihan)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $pilihan === 'rekomendasi' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700' }}">
                                        {{ $displayPilihan }}{{ $pilihanIndustri ? ' - ' . $pilihanIndustri : '' }}
                                    </span>
                                    @else
                                    <span class="text-gray-400 italic text-sm">Belum dipilih</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                    $statusClass = match ($status) {
                                    'diterima_industri' => 'bg-green-50 text-green-700 border border-green-200',
                                    'proses_pengajuan' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                                    'ditolak_industri' => 'bg-red-50 text-red-700 border border-red-200',
                                    default => 'bg-gray-50 text-gray-700 border border-gray-200',
                                    };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $displayStatus }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($status === 'diterima_industri')
                                    <div class="flex items-center gap-2">
                                        @if ($guruNama)
                                        <span>{{ $guruNama }}</span>
                                        @endif
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                        @click="
                                            guruOpen = true;
                                            guruTargetId = {{ $row->id }};
                                            guruTargetName = @js($row->siswa?->user?->name ?? '-');
                                            guruList = @js(
                                                ($guruOptions[$row->siswa?->jurusan_id] ?? collect())->map(function ($guru) {
                                                    return [
                                                        'id' => $guru->id,
                                                        'name' => $guru->user?->name ?? '-',
                                                        'jurusan' => $guru->jurusan?->nama ?? '-',
                                                    ];
                                                })->values()
                                            );
                                        ">
                                        {{ $guruNama ? 'Ubah Guru' : 'Pilih Guru' }}
                                    </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400 italic">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                        @click="detailOpen = true; detailNama = @js($namaSiswa); detailJurusan = @js($jurusanNama); detailList = @js($detailItems);">
                                        Lihat Detail
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($status === 'belum_diproses')
                                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all text-xs font-medium">
                                        Konfirmasi Pilihan
                                    </button>
                                    @elseif ($status === 'proses_pengajuan')
                                    <div class="flex items-center gap-2 text-xs text-orange-600">
                                        <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
                                        <span class="italic">Menunggu konfirmasi industri</span>
                                    </div>
                                    @elseif ($status === 'diterima_industri')
                                    <span class="text-xs text-green-600 font-medium">Penempatan selesai</span>
                                    @elseif ($status === 'ditolak_industri')
                                    <span class="text-xs text-red-600 italic">Perlu penempatan ulang</span>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Menunggu hasil SAW</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 text-sm text-gray-500">
                Menampilkan {{ count($penempatanData) }} data penempatan
            </div>
        </div>

        {{-- Modal Detail --}}
        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Detail Rekomendasi SAW</h4>
                        <p class="text-xs text-gray-500">
                            <span x-text="detailNama"></span> · <span x-text="detailJurusan"></span>
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="detailOpen = false">✕</button>
                </div>
                <div class="p-4">
                    <template x-if="detailList.length === 0">
                        <div class="text-sm text-gray-500 italic">Belum ada hasil rekomendasi.</div>
                    </template>
                    <template x-if="detailList.length > 0">
                        <div class="space-y-3">
                            <template x-for="item in detailList" :key="item.rank">
                                <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="item.industri"></div>
                                        <div class="text-xs text-gray-500">Skor: <span x-text="(item.skor * 100).toFixed(2) + '%'"></span></div>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-50 text-purple-700">
                                        Rank <span x-text="item.rank"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Modal Guru Pembimbing --}}
        <div x-show="guruOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Pilih Guru Pembimbing</h4>
                        <p class="text-xs text-gray-500">
                            Siswa: <span x-text="guruTargetName"></span>
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="guruOpen = false">✕</button>
                </div>
                <div class="p-4">
                    <template x-if="guruList.length === 0">
                        <div class="text-sm text-gray-500 italic">Belum ada guru pembimbing untuk jurusan ini.</div>
                    </template>
                    <template x-if="guruList.length > 0">
                        <div class="space-y-2">
                            <template x-for="guru in guruList" :key="guru.id">
                                <form method="POST" :action="`{{ url('/penempatan') }}/${guruTargetId}/guru`">
                                    @csrf
                                    <input type="hidden" name="guru_pembimbing_id" :value="guru.id">
                                    <button type="submit" class="w-full flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2 text-sm hover:bg-gray-50">
                                        <div class="text-left">
                                            <div class="font-medium text-gray-900" x-text="guru.name"></div>
                                            <div class="text-xs text-gray-500" x-text="guru.jurusan"></div>
                                        </div>
                                        <span class="text-xs text-emerald-600 font-semibold">Pilih</span>
                                    </button>
                                </form>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
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
