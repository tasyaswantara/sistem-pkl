@section('title', 'Pengajuan Industri')

@php
    use App\Enums\PenempatanStatus;
    use App\Enums\PengajuanStatus;
@endphp

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Pengajuan</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Pengajuan Industri</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Konfirmasi apakah industri sedang menerima siswa magang atau tidak.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        @if (session('warning'))
        <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
            {{ session('warning') }}
        </div>
        @endif

        @php
        $hasPengajuan = $penempatanList->isNotEmpty();
        $status = $hasPengajuan
            ? ($industri->status_pengajuan ?? PengajuanStatus::MENUNGGU->value)
            : null;
        $statusClass = match ($status) {
        PengajuanStatus::DISETUJUI->value => 'bg-green-50 text-green-700 border border-green-200',
        PengajuanStatus::DITOLAK->value => 'bg-red-50 text-red-700 border border-red-200',
        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
        };
        @endphp

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Status Pengajuan</h3>
                @if ($status)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                    {{ ucfirst($status) }}
                </span>
                @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">
                    Belum ada pengajuan
                </span>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Nama Industri</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $industri->nama_industri }}</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Jurusan</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $industri->jurusan?->nama ?? '-' }}</div>
                </div>
            </div>

            @if ($status === PengajuanStatus::MENUNGGU->value)
            <form method="POST" action="{{ route('industri.pengajuan.konfirmasi') }}" class="flex items-center gap-3">
                @csrf
                <button name="status_pengajuan" value="{{ PengajuanStatus::DISETUJUI->value }}"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                    Terima Pengajuan
                </button>
                <button name="status_pengajuan" value="{{ PengajuanStatus::DITOLAK->value }}"
                    class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                    Tolak Pengajuan
                </button>
            </form>
            @elseif ($status)
            <p class="text-sm text-gray-500">Status pengajuan sudah dikonfirmasi.</p>
            @else
            <p class="text-sm text-gray-500">Belum ada pengajuan yang perlu dikonfirmasi.</p>
            @endif
        </div>

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
        @endphp

        <div class="mt-6 bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Siswa yang Memilih</h3>
                <span class="text-xs text-gray-500">{{ $penempatanList->count() }} siswa</span>
            </div>

            @if ($penempatanList->isEmpty())
            <p class="text-sm text-gray-500">Belum ada siswa yang memilih atau mengusulkan industri ini.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status Penempatan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Berkas Siswa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($penempatanList as $row)
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
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $statusLabels[$row->status] ?? $row->status }}
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
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>
