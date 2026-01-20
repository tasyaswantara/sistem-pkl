@section('title', 'E-Logbook Bimbingan')

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → E-Logbook</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">E-Logbook Siswa Bimbingan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Pantau logbook dan berikan komentar untuk siswa bimbingan Anda.
            </p>
        </div>

        @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aktivitas</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Komentar Guru</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($logbooks as $logbook)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $logbook->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $logbook->siswa?->kelas ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $logbook->tanggal?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div>{{ $logbook->aktivitas }}</div>
                                @if ($logbook->catatan_industri)
                                <div class="mt-2 text-xs text-gray-500">Catatan industri: {{ $logbook->catatan_industri }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="space-y-2 mb-3">
                                    @forelse ($logbook->komentar as $komentar)
                                    <div class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                                        {{ $komentar->komentar }}
                                    </div>
                                    @empty
                                    <span class="text-xs text-gray-400 italic">Belum ada komentar.</span>
                                    @endforelse
                                </div>
                                <form method="POST" action="{{ route('guru.elogbook.komentar', $logbook->id) }}" class="flex items-start gap-2">
                                    @csrf
                                    <textarea name="komentar" rows="2" class="w-full px-2.5 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500" placeholder="Tulis komentar"></textarea>
                                    <button class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                                        Kirim
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="4">
                                Belum ada logbook siswa bimbingan.
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
    </div>
</x-admin-layout>
