@section('title', 'Data Siswa')

@php
    use App\Enums\JadwalWawancaraStatus;
    use App\Enums\LaporanStatus;
    use App\Enums\PenempatanStatus;
@endphp

<x-admin-layout>
    <div x-data="{ openId: null, reportId: null }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Data Siswa</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Data Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Kelola siswa yang melamar dan atur jadwal wawancara.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <form method="GET" class="flex items-center gap-3 mb-6">
            <select name="status" onchange="this.form.submit()"
                class="px-4 py-2 w-[200px] bg-white border border-gray-300 rounded-lg text-sm focus:ring-emerald-500">
                @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" {{ (string) $statusFilter === (string) $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
            <a href="{{ route('industri.siswa') }}" class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg bg-white hover:bg-gray-50">
                Reset
            </a>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1350px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jadwal Wawancara</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Berkas Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Laporan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($penempatanList as $row)
                        @php
                        $jadwal = $jadwalMap->get($row->siswa_id);
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
                        PenempatanStatus::DITERIMA_INDUSTRI->value => 'bg-green-50 text-green-700 border border-green-200',
                        PenempatanStatus::PROSES_WAWANCARA->value => 'bg-blue-50 text-blue-700 border border-blue-200',
                        PenempatanStatus::PROSES_PENGAJUAN->value,
                        PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
                        PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
                        PenempatanStatus::DITOLAK_SEKOLAH->value => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-gray-50 text-gray-700 border border-gray-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabels[$row->status] ?? $row->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @if ($jadwal)
                                <div class="font-medium text-gray-900">{{ $jadwal->tanggal?->format('d/m/Y') ?? '-' }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $jadwal->waktu?->format('H:i') ?? '-' }} · {{ $jadwal->lokasi ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500">Status: {{ $jadwal->status }}</div>
                                @else
                                <span class="text-gray-400 italic">Belum dijadwalkan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
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
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @if ($row->laporan_industri)
                                <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($row->laporan_industri, 80) }}</div>
                                <div class="text-xs text-emerald-600 mt-1">
                                    Status: {{ ucfirst($row->laporan_status ?? LaporanStatus::MENUNGGU->value) }}
                                </div>
                                @else
                                <span class="text-gray-400 italic text-xs">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                        @click="openId = openId === {{ $row->id }} ? null : {{ $row->id }}">
                                        Atur Jadwal
                                    </button>
                                    <button type="button"
                                        class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                        @click="reportId = reportId === {{ $row->id }} ? null : {{ $row->id }}">
                                        Laporan
                                    </button>
                                    <form method="POST" action="{{ route('industri.siswa.status', $row->id) }}">
                                        @csrf
                                        <select name="status" onchange="this.form.submit()"
                                            class="px-3 py-1.5 text-xs border border-emerald-200 bg-emerald-50 text-emerald-700 rounded-lg font-semibold">
                                            @foreach ([
                                            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima',
                                            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak Lolos',
                                            ] as $value => $label)
                                            <option value="{{ $value }}" {{ $row->status === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="openId === {{ $row->id }}" x-cloak class="bg-gray-50">
                            <td colspan="7" class="px-4 py-4">
                                <form method="POST" action="{{ route('industri.siswa.jadwal', $row->id) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                                        <input type="date" name="tanggal" value="{{ $jadwal?->tanggal?->format('Y-m-d') }}"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Waktu</label>
                                        <input type="time" name="waktu" value="{{ $jadwal?->waktu?->format('H:i') }}"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Lokasi</label>
                                        <input name="lokasi" value="{{ $jadwal?->lokasi }}"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Lokasi">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            @foreach ([
                                                JadwalWawancaraStatus::MENUNGGU->value => 'Menunggu',
                                                JadwalWawancaraStatus::DIJADWALKAN->value => 'Dijadwalkan',
                                                JadwalWawancaraStatus::SELESAI->value => 'Selesai',
                                                JadwalWawancaraStatus::DIBATALKAN->value => 'Dibatalkan',
                                            ] as $st => $label)
                                            <option value="{{ $st }}" {{ ($jadwal?->status ?? JadwalWawancaraStatus::MENUNGGU->value) === $st ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Catatan</label>
                                        <input name="catatan" value="{{ $jadwal?->catatan }}"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Catatan">
                                    </div>
                                    <div class="md:col-span-5 flex justify-end">
                                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                                            Simpan Jadwal
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="reportId === {{ $row->id }}" x-cloak class="bg-gray-50">
                            <td colspan="7" class="px-4 py-4">
                                <form method="POST" action="{{ route('industri.siswa.laporan', $row->id) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                    @csrf
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Isi Laporan</label>
                                        <textarea name="laporan" rows="3" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            placeholder="Tuliskan keluhan atau laporan terkait siswa...">{{ old('laporan', $row->laporan_industri) }}</textarea>
                                    </div>
                                    <div class="md:col-span-1 flex items-end justify-end">
                                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                                            Kirim Laporan
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada siswa melamar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
