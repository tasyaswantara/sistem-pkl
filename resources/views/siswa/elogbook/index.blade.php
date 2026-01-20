@section('title', 'E-Logbook')

<x-admin-layout>
    <div x-data="{ editOpen: false, editAction: '', editTanggal: '', editAktivitas: '' }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → E-Logbook</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">E-Logbook</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Catat aktivitas harian Anda dan pantau status validasi industri.
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

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Tambah Logbook</h3>
            <form method="POST" action="{{ route('siswa.elogbook.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Aktivitas</label>
                    <textarea name="aktivitas" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Aktivitas harian">{{ old('aktivitas') }}</textarea>
                </div>
                <div class="md:col-span-3 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Industri: <span class="font-semibold">{{ $penempatan?->industri?->nama_industri ?? 'Belum ditetapkan' }}</span>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $penempatan?->industri_id ? '' : 'disabled' }}>
                        Simpan Logbook
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aktivitas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Pesan Guru</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logbooks as $logbook)
                        @php
                        $statusClass = match ($logbook->status_validasi) {
                        'disetujui' => 'bg-green-50 text-green-700 border border-green-200',
                        'ditolak' => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 text-sm text-gray-700 w-[130px]">{{ $logbook->tanggal?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[220px]">
                                <div class="font-medium text-gray-900">{{ $logbook->industri?->nama_industri ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[360px]">
                                <div class="whitespace-pre-line">{{ $logbook->aktivitas }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($logbook->status_validasi) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[260px]">
                                <div class="whitespace-pre-line text-gray-600">{{ $logbook->catatan_industri ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 w-[260px]">
                                @if ($logbook->komentar->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach ($logbook->komentar as $komentar)
                                    <div class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                                        {{ $komentar->komentar }}
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <span class="text-gray-400 italic">Belum ada pesan.</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                        @click="editOpen = true; editAction = @js(route('siswa.elogbook.update', $logbook->id)); editTanggal = @js($logbook->tanggal?->format('Y-m-d')); editAktivitas = @js($logbook->aktivitas);">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('siswa.elogbook.destroy', $logbook->id) }}"
                                        onsubmit="return confirm('Hapus logbook ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 text-xs border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada logbook.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $logbooks->links() }}
        </div>

        <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-xl w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <h4 class="text-base font-semibold text-gray-900">Ubah Logbook</h4>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="editOpen = false">✕</button>
                </div>
                <form method="POST" :action="editAction" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                        <input type="date" name="tanggal" x-model="editTanggal"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Aktivitas</label>
                        <textarea name="aktivitas" rows="3" x-model="editAktivitas"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm" @click="editOpen = false">
                            Batal
                        </button>
                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
