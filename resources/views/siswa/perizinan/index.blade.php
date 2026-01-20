@section('title', 'Perizinan')

<x-admin-layout>
    <div>
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Perizinan</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Perizinan</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Lihat perizinan yang diajukan untuk Anda dan statusnya.
            </p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jenis Izin</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan Industri</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($perizinanList as $row)
                        @php
                        $statusClass = match ($row->status) {
                        'disetujui' => 'bg-green-50 text-green-700 border border-green-200',
                        'ditolak' => 'bg-red-50 text-red-700 border border-red-200',
                        default => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                        };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->jenis_izin }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->tanggal_mulai?->format('d/m/Y') ?? '-' }} - {{ $row->tanggal_selesai?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->catatan_industri ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="5">
                                Belum ada data perizinan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $perizinanList->links() }}
        </div>
    </div>
</x-admin-layout>
