@section('title', 'Penempatan PKL')

<x-admin-layout>
    <div>
        <div class="mb-8 animate-fade-up">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Penempatan PKL</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penempatan PKL</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Lihat rekomendasi industri dari hasil SAW dan pilih penempatan atau ajukan industri sendiri.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 animate-fade-up">
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 animate-fade-up">
            <div class="font-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
        $status = $penempatan?->status ?? 'belum_memilih';
        $statusLabels = $statusLabels ?? [];
        $statusClass = match ($status) {
        'diterima_industri' => 'bg-green-50 text-green-700 border border-green-200',
        'proses_wawancara' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'proses_pengajuan', 'menunggu_konfirmasi' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
        'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri' => 'bg-red-50 text-red-700 border border-red-200',
        default => 'bg-gray-50 text-gray-700 border border-gray-200',
        };
        $pilihan = $penempatan?->pilihan_siswa;
        $pilihanLabel = $pilihan === 'rekomendasi'
            ? 'Rekomendasi'
            : ($pilihan === 'usulan_lain' ? 'Usulan Lain' : ($pilihan === 'langsung' ? 'Penempatan Langsung' : 'Belum dipilih'));
        $pilihanIndustri = $pilihan === 'usulan_lain'
        ? ($penempatan?->usulanIndustri?->nama_industri ?? '-')
        : ($penempatan?->industri?->nama_industri ?? '-');
        $isLocked = !in_array($status, ['belum_memilih', 'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri'], true);
        @endphp

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6 animate-fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Status Penempatan</h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Pilihan Siswa</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $pilihanLabel }}</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Industri Dipilih</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $pilihanIndustri }}</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Jurusan</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $siswa->jurusan?->nama ?? '-' }}</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Guru Pembimbing</div>
                    <div class="text-sm font-semibold text-gray-900">
                        {{ $penempatan?->guruPembimbing?->user?->name ?? 'Belum ditentukan' }}
                    </div>
                    @if ($penempatan?->guruPembimbing?->jurusan?->nama)
                    <div class="text-xs text-gray-500">{{ $penempatan->guruPembimbing->jurusan->nama }}</div>
                    @endif
                </div>
            </div>
            @if ($isLocked)
            <p class="mt-4 text-sm text-gray-500">
                Penempatan sudah diterima industri, pilihan tidak dapat diubah.
            </p>
            @endif
            @if (($penempatan?->jenis_penempatan ?? 'normal') === 'langsung')
            <p class="mt-2 text-sm text-emerald-600">
                Penempatan langsung ditetapkan oleh admin.
            </p>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6 animate-fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Jadwal Wawancara</h3>
                <span class="text-xs text-gray-500">Pantau status penerimaan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Lokasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($jadwalWawancara as $jadwal)
                        @php
                        $jadwalStatusClass = match ($jadwal->status) {
                        'dijadwalkan' => 'bg-blue-50 text-blue-700 border border-blue-200',
                        'selesai' => 'bg-green-50 text-green-700 border border-green-200',
                        'dibatalkan' => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $jadwal->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $jadwal->tanggal?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $jadwal->waktu?->format('H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $jadwal->lokasi ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $jadwalStatusClass }}">
                                    {{ $statusWawancaraLabels[$jadwal->status] ?? $jadwal->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $jadwal->catatan ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="6">
                                Belum ada jadwal wawancara.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6 animate-fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Rekomendasi Industri</h3>
                <span class="text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                    Hasil SAW terbaru
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Peringkat</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nilai Preferensi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($rekomendasi as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold">
                                    {{ $row->peringkat }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ number_format(($row->nilai_preferensi ?? 0) * 100, 2) }}%
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('siswa.penempatan.pilih') }}">
                                    @csrf
                                    <input type="hidden" name="industri_id" value="{{ $row->industri_id }}">
                                    <button type="submit"
                                        class="px-3 py-2 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                        {{ $isLocked ? 'disabled' : '' }}>
                                        Pilih Rekomendasi
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="4">
                                Belum ada rekomendasi SAW untuk Anda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 animate-fade-up">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Usulkan Industri Lain</h3>
            <form method="POST" action="{{ route('siswa.penempatan.usulan') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Nama Industri</label>
                    <input name="nama_industri" value="{{ old('nama_industri') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Nama industri">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Industri</label>
                    <input name="email" type="email" value="{{ old('email') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="email@industri.com" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Kapasitas</label>
                    <input name="kapasitas" type="number" min="1" value="{{ old('kapasitas') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Jumlah siswa yang diterima" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Kontak (opsional)</label>
                    <input name="kontak" value="{{ old('kontak') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Telepon/Email">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Alamat</label>
                    <textarea name="alamat" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Alamat industri">{{ old('alamat') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Keterangan (opsional)</label>
                    <textarea name="keterangan" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Catatan tambahan">{{ old('keterangan') }}</textarea>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3">
                    <button type="submit"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $isLocked ? 'disabled' : '' }}>
                        Kirim Usulan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
