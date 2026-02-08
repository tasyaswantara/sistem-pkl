@section('title', 'Data Siswa Bimbingan')

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Data Siswa</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Siswa Bimbingan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Daftar siswa yang sudah ditetapkan sebagai bimbingan Anda.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
            <select name="jurusan_id" onchange="this.form.submit()"
                class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                <option value="">Semua Jurusan</option>
                @foreach ($jurusanOptions as $jurusan)
                <option value="{{ $jurusan->id }}" {{ (string) $filters['jurusan_id'] === (string) $jurusan->id ? 'selected' : '' }}>
                    {{ $jurusan->nama }}
                </option>
                @endforeach
            </select>

            <select name="kelas" onchange="this.form.submit()"
                class="px-4 py-2 w-[170px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                <option value="">Semua Kelas</option>
                @foreach ($kelasOptions as $kelas)
                <option value="{{ $kelas }}" {{ (string) $filters['kelas'] === (string) $kelas ? 'selected' : '' }}>
                    {{ $kelas }}
                </option>
                @endforeach
            </select>

            <select name="tahun_ajaran" onchange="this.form.submit()"
                class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                <option value="">Semua Tahun Ajaran</option>
                @foreach ($tahunAjaranOptions as $tahun)
                <option value="{{ $tahun }}" {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                    {{ $tahun }}
                </option>
                @endforeach
            </select>

            <input
                type="text"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="Cari nama atau NIS"
                class="w-[260px] px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm"
                oninput="debouncedSubmit(this)">

            <a href="{{ route('guru.siswa') }}"
                class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                Reset
            </a>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1350px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kelas</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Rata Rata Nilai Kejuruan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tahun Ajaran</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Laporan Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Berkas Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($penempatanList as $row)
                        @php
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
                        $statusClass = match ($row->status) {
                        'diterima_industri' => 'bg-green-50 text-green-700 border border-green-200',
                        'proses_wawancara' => 'bg-blue-50 text-blue-700 border border-blue-200',
                        'proses_pengajuan', 'menunggu_konfirmasi' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri' => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-gray-50 text-gray-700 border border-gray-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->kelas ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->nilai_akademik ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->tahun_ajaran ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if ($row->laporan_industri)
                                <div class="text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($row->laporan_industri, 80) }}</div>
                                <div class="text-xs text-emerald-600 mt-1">Status: {{ ucfirst($row->laporan_status ?? 'menunggu') }}</div>
                                @if ($row->laporan_at)
                                <div class="text-xs text-gray-500">{{ $row->laporan_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @else
                                <span class="text-gray-400 italic text-xs">Tidak ada laporan</span>
                                @endif
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
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabels[$row->status] ?? $row->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="9">
                                Belum ada siswa bimbingan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;

        function debouncedSubmit(input) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                input.form.submit();
            }, 700);
        }
    </script>
</x-admin-layout>
