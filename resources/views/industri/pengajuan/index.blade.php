@section('title', 'Pengajuan Industri')

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
        $status = $industri->status_pengajuan ?? 'menunggu';
        $statusClass = match ($status) {
        'disetujui' => 'bg-green-50 text-green-700 border border-green-200',
        'ditolak' => 'bg-red-50 text-red-700 border border-red-200',
        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
        };
        @endphp

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Status Pengajuan</h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                    {{ ucfirst($status) }}
                </span>
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

            @if ($status === 'menunggu')
            <form method="POST" action="{{ route('industri.pengajuan.konfirmasi') }}" class="flex items-center gap-3">
                @csrf
                <button name="status_pengajuan" value="disetujui"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                    Terima Pengajuan
                </button>
                <button name="status_pengajuan" value="ditolak"
                    class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                    Tolak Pengajuan
                </button>
            </form>
            @else
            <p class="text-sm text-gray-500">Status pengajuan sudah dikonfirmasi.</p>
            @endif
        </div>
    </div>
</x-admin-layout>
