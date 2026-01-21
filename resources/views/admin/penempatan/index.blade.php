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
            usulanOpen: false,
            usulanDetail: {},
        }">

        {{-- Header --}}
        <div class="mb-8">
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

        @php
        $tab = request('tab', 'konfigurasi');
        @endphp

        @if ($tab === 'konfigurasi')
        <div id="konfigurasi-bobot">
        {{-- Pilih Jurusan & Tahun Ajaran --}}
        <form method="GET" action="{{ route('admin.penempatan') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <input type="hidden" name="tab" value="konfigurasi">
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
                    <a href="{{ route('admin.penempatan', ['tab' => 'konfigurasi', 'jurusan_id' => $selectedJurusan, 'tahun_ajaran' => $selectedTahun, 'status' => $selectedStatus, 'q' => $search]) }}"
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
        </div>
        @endif

        {{-- Usulan Industri Siswa --}}
        @if ($tab === 'usulan')
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kapasitas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kontak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $usulan->kapasitas ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $usulan->kontak ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $usulanStatusClass }}">
                                    {{ ucfirst($usulan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($usulan->status === 'menunggu')
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.penempatan.usulan.approve', $usulan->id) }}">
                                        @csrf
                                        <button class="px-3 py-1.5 text-xs border border-emerald-200 text-emerald-700 rounded-lg hover:bg-emerald-50">
                                            Setujui
                                        </button>
                                    </form>
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
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="8">
                                Belum ada usulan industri dari siswa.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Penempatan Langsung --}}
        @if ($tab === 'langsung')
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Penempatan Langsung</h3>
                <span class="text-xs text-gray-500">Ditetapkan oleh admin</span>
            </div>
            <p class="text-xs text-gray-500 mb-4">
                Gunakan fitur ini untuk menempatkan siswa langsung ke industri atau magang di sekolah.
            </p>
            @php
            $siswaSelectData = $siswaOptions->map(function ($siswa) {
                return [
                    'id' => $siswa->id,
                    'label' => trim(($siswa->user?->name ?? '-') . ' · ' . ($siswa->nis ?? '-') . ' · ' . ($siswa->jurusan?->nama ?? '-')),
                ];
            })->values();
            $industriSelectData = $industriOptions->map(function ($industri) {
                return [
                    'id' => $industri->id,
                    'label' => $industri->nama_industri,
                ];
            })->values();
            @endphp
            <form method="POST" action="{{ route('admin.penempatan.langsung') }}"
                x-data="penempatanLangsungForm({
                    siswa: @js($siswaSelectData),
                    industri: @js($industriSelectData),
                    initialSiswaId: @js(old('siswa_id')),
                    initialIndustriId: @js(old('industri_id'))
                })"
                x-init="init()"
                class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Siswa</label>
                    <input type="hidden" name="siswa_id" :value="siswaId">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="siswaQuery"
                            @focus="openSiswa = true"
                            @input="openSiswa = true"
                            placeholder="Cari nama/NIS siswa"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <div x-show="openSiswa" x-cloak @click.outside="openSiswa = false"
                            class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-if="filteredSiswa.length === 0">
                                <div class="px-3 py-2 text-xs text-gray-500">Tidak ditemukan.</div>
                            </template>
                            <template x-for="item in filteredSiswa" :key="item.id">
                                <button type="button"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                    @click="selectSiswa(item)">
                                    <span x-text="item.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                    <input type="hidden" name="industri_id" :value="industriId">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="industriQuery"
                            @focus="openIndustri = true"
                            @input="openIndustri = true"
                            placeholder="Cari nama industri"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <div x-show="openIndustri" x-cloak @click.outside="openIndustri = false"
                            class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-if="filteredIndustri.length === 0">
                                <div class="px-3 py-2 text-xs text-gray-500">Tidak ditemukan.</div>
                            </template>
                            <template x-for="item in filteredIndustri" :key="item.id">
                                <button type="button"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                    @click="selectIndustri(item)">
                                    <span x-text="item.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Mode Penempatan</label>
                    <select name="mode" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm">
                        <option value="industri">Penempatan ke Industri (Ikuti prosedur)</option>
                        <option value="sekolah">Magang di Sekolah (Langsung diterima)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Alasan Penempatan Langsung</label>
                    <textarea name="alasan" rows="3" required
                        class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                        placeholder="Contoh: sudah berkali-kali melamar, ada keluhan industri, atau kebutuhan khusus."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                        Tetapkan Penempatan Langsung
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900">Riwayat Penempatan Langsung</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($penempatanData->where('jenis_penempatan', 'langsung') as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                                    {{ $statusLabels[$row->status] ?? $row->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->alasan_penempatan_langsung ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="4">
                                Belum ada penempatan langsung.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Filter --}}
        @if ($tab === 'hasil')
        <form id="filter-penempatan" method="GET" action="{{ route('admin.penempatan') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <input type="hidden" name="tab" value="hasil">
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
                    <a href="{{ route('admin.penempatan', ['tab' => 'hasil']) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                        Reset Filter
                    </a>
                </div>
            </div>
        </form>

        {{-- Hasil Penempatan --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Hasil Penempatan Siswa</h3>
                <div class="flex items-center gap-3 text-sm">
                    <div class="px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
                        <span class="text-blue-600 font-semibold">{{ $statusCounts['menunggu_konfirmasi'] ?? 0 }}</span>
                        <span class="text-blue-700 ml-1">Menunggu Konfirmasi</span>
                    </div>
                    <div class="px-3 py-1.5 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <span class="text-yellow-600 font-semibold">{{ $statusCounts['proses_pengajuan'] ?? 0 }}</span>
                        <span class="text-yellow-700 ml-1">Proses Pengajuan</span>
                    </div>
                    <div class="px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                        <span class="text-green-600 font-semibold">{{ $statusCounts['diterima_industri'] ?? 0 }}</span>
                        <span class="text-green-700 ml-1">Diterima</span>
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
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pilihan Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Penempatan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Berkas Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Guru Pembimbing</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail Rekomendasi</th>
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
                            $bpjsUrl = $row->siswa?->bpjs_link
                                ? (\Illuminate\Support\Str::startsWith($row->siswa->bpjs_link, ['http://', 'https://'])
                                    ? $row->siswa->bpjs_link
                                    : Storage::url($row->siswa->bpjs_link))
                                : null;
                            $kartuUrl = $row->siswa?->kartu_pelajar_link
                                ? (\Illuminate\Support\Str::startsWith($row->siswa->kartu_pelajar_link, ['http://', 'https://'])
                                    ? $row->siswa->kartu_pelajar_link
                                    : Storage::url($row->siswa->kartu_pelajar_link))
                                : null;
                            $cvUrl = $row->siswa?->cv_link;
                            $portofolioLinks = collect($row->siswa?->portofolio_links ?? []);
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 text-sm">{{ $namaSiswa }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $jurusanNama }}</td>
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
                                    'proses_wawancara' => 'bg-blue-50 text-blue-700 border border-blue-200',
                                    'proses_pengajuan', 'menunggu_konfirmasi' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                                    'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri' => 'bg-red-50 text-red-700 border border-red-200',
                                    default => 'bg-gray-50 text-gray-700 border border-gray-200',
                                    };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $displayStatus }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="flex flex-col gap-1">
                                        @if ($bpjsUrl)
                                        <a href="{{ $bpjsUrl }}" target="_blank" class="text-emerald-700 hover:underline text-xs">BPJS</a>
                                        @endif
                                        @if ($kartuUrl)
                                        <a href="{{ $kartuUrl }}" target="_blank" class="text-emerald-700 hover:underline text-xs">Kartu Pelajar</a>
                                        @endif
                                        @if ($cvUrl)
                                        <a href="{{ $cvUrl }}" target="_blank" class="text-emerald-700 hover:underline text-xs">CV</a>
                                        @endif
                                        @if ($portofolioLinks->isNotEmpty())
                                        <div class="text-xs text-gray-500">
                                            @foreach ($portofolioLinks as $idx => $link)
                                            <a href="{{ $link }}" target="_blank" class="text-emerald-700 hover:underline">Portfolio-{{ $idx + 1 }}</a>@if (!$loop->last),@endif
                                            @endforeach
                                        </div>
                                        @endif
                                        @if (!$bpjsUrl && !$kartuUrl && !$cvUrl && $portofolioLinks->isEmpty())
                                        <span class="text-gray-400 italic text-xs">Belum ada berkas</span>
                                        @endif
                                    </div>
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
                                    @if ($status === 'menunggu_konfirmasi')
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.penempatan.confirm', $row->id) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all text-xs font-medium">
                                                Konfirmasi
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.penempatan.reject', $row->id) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-all text-xs font-medium">
                                                Tolak
                                            </button>
                                        </form>
                                        @if ($pilihan === 'usulan_lain' && $row->usulanIndustri)
                                        <button
                                            type="button"
                                            class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                            @click="usulanOpen = true; usulanDetail = @js([
                                                'nama' => $row->usulanIndustri->nama_industri,
                                                'email' => $row->usulanIndustri->email ?? '-',
                                                'kapasitas' => $row->usulanIndustri->kapasitas ?? '-',
                                                'alamat' => $row->usulanIndustri->alamat,
                                                'kontak' => $row->usulanIndustri->kontak ?? '-',
                                                'keterangan' => $row->usulanIndustri->keterangan ?? '-',
                                            ]);">
                                            Usulan
                                        </button>
                                        @endif
                                    </div>
                                    @elseif ($status === 'proses_pengajuan')
                                    <div class="flex items-center gap-2 text-xs text-orange-600">
                                        <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
                                        <span class="italic">Menunggu konfirmasi industri</span>
                                    </div>
                                    @elseif ($status === 'proses_wawancara')
                                    <span class="text-xs text-blue-600 italic">Proses wawancara</span>
                                    @elseif ($status === 'diterima_industri')
                                    <span class="text-xs text-green-600 font-medium">Penempatan selesai</span>
                                    @elseif (in_array($status, ['pengajuan_ditolak_industri', 'tidak_lolos_industri', 'ditolak_sekolah'], true))
                                    <span class="text-xs text-red-600 italic">Perlu penempatan ulang</span>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Menunggu pilihan siswa</span>
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
        @endif

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

        {{-- Modal Usulan --}}
        <div x-show="usulanOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <h4 class="text-base font-semibold text-gray-900">Detail Usulan Industri</h4>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="usulanOpen = false">✕</button>
                </div>
                <div class="p-4 text-sm text-gray-700 space-y-2">
                    <div><span class="text-gray-500">Nama:</span> <span class="font-medium" x-text="usulanDetail.nama"></span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="font-medium" x-text="usulanDetail.email"></span></div>
                    <div><span class="text-gray-500">Kapasitas:</span> <span class="font-medium" x-text="usulanDetail.kapasitas"></span></div>
                    <div><span class="text-gray-500">Alamat:</span> <span class="font-medium" x-text="usulanDetail.alamat"></span></div>
                    <div><span class="text-gray-500">Kontak:</span> <span class="font-medium" x-text="usulanDetail.kontak"></span></div>
                    <div><span class="text-gray-500">Keterangan:</span> <span class="font-medium" x-text="usulanDetail.keterangan"></span></div>
                </div>
                <div class="flex justify-end p-4 border-t bg-gray-50">
                    <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                        @click="usulanOpen = false">
                        Tutup
                    </button>
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

    function penempatanLangsungForm({ siswa, industri, initialSiswaId, initialIndustriId }) {
        return {
            siswaList: siswa || [],
            industriList: industri || [],
            siswaQuery: '',
            industriQuery: '',
            siswaId: initialSiswaId || '',
            industriId: initialIndustriId || '',
            openSiswa: false,
            openIndustri: false,
            get filteredSiswa() {
                const term = this.siswaQuery.trim().toLowerCase();
                if (!term) {
                    return this.siswaList.slice(0, 10);
                }
                return this.siswaList.filter((item) => item.label.toLowerCase().includes(term)).slice(0, 10);
            },
            get filteredIndustri() {
                const term = this.industriQuery.trim().toLowerCase();
                if (!term) {
                    return this.industriList.slice(0, 10);
                }
                return this.industriList.filter((item) => item.label.toLowerCase().includes(term)).slice(0, 10);
            },
            selectSiswa(item) {
                this.siswaId = item.id;
                this.siswaQuery = item.label;
                this.openSiswa = false;
            },
            selectIndustri(item) {
                this.industriId = item.id;
                this.industriQuery = item.label;
                this.openIndustri = false;
            },
            init() {
                if (this.siswaId) {
                    const found = this.siswaList.find((item) => String(item.id) === String(this.siswaId));
                    if (found) {
                        this.siswaQuery = found.label;
                    }
                }
                if (this.industriId) {
                    const found = this.industriList.find((item) => String(item.id) === String(this.industriId));
                    if (found) {
                        this.industriQuery = found.label;
                    }
                }
            },
        };
    }
</script>
