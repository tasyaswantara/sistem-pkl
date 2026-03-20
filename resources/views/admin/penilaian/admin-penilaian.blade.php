@section('title', 'Penilaian')

<x-admin-layout>
    <div x-data="{ detailOpen: false, detailNama: '', detailIndustri: '', detailList: [], detailTotal: '', toastOpen: {{ session('success') ? 'true' : 'false' }} }"
        x-init="if (toastOpen) { setTimeout(() => { toastOpen = false }, 3000) }">
        <div class="mb-8">
            <div class="text-sm text-gray-500 mb-2">Dashboard -> Penilaian</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Penilaian Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Monitoring penilaian PKL dari industri, termasuk detail aspek penilaian.
            </p>
        </div>

        @if (session('success'))
        <div x-show="toastOpen" x-transition
            class="fixed top-6 right-6 z-50 max-w-sm w-full bg-white shadow-md rounded-lg overflow-hidden">
            <div class="flex">
                <div class="flex justify-center items-center w-12 bg-emerald-500">
                    <svg class="h-6 w-6 fill-current text-white" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 3.33331C10.8 3.33331 3.33337 10.8 3.33337 20C3.33337 29.2 10.8 36.6666 20 36.6666C29.2 36.6666 36.6667 29.2 36.6667 20C36.6667 10.8 29.2 3.33331 20 3.33331ZM16.6667 28.3333L8.33337 20L10.6834 17.65L16.6667 23.6166L29.3167 10.9666L31.6667 13.3333L16.6667 28.3333Z" />
                    </svg>
                </div>
                <div class="flex-1 -mx-3 py-2 px-4">
                    <div class="mx-3">
                        <span class="text-emerald-600 font-semibold">Success</span>
                        <p class="text-gray-600 text-sm">{{ session('success') }}</p>
                    </div>
                </div>
                <button type="button" @click="toastOpen = false"
                    class="px-4 text-gray-400 hover:text-gray-600">
                    ✕
                </button>
            </div>
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

        {{-- Rubrik Penilaian --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-semibold text-gray-900">Rubrik Penilaian (Global)</h3>
                <span id="rubrik-status-badge" class="text-xs px-2.5 py-1 rounded-full {{ $isBobotValid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $isBobotValid ? 'Total bobot valid' : 'Total bobot harus 100%' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 mb-4">Atur bobot tiap aspek. Total bobot harus 100%.</p>

            <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
                <form method="POST" action="{{ route('admin.penilaian.aspek.store') }}" class="flex items-end gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tambah Aspek</label>
                        <input
                            type="text"
                            name="nama_aspek"
                            placeholder="Nama aspek"
                            class="w-60 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                    </div>
                    <button type="submit" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-medium">
                        Tambah
                    </button>
                </form>
            </div>

            <form id="rubrik-form" method="POST" action="{{ route('admin.penilaian.rubrik') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aspek</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-40">Bobot (%)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($aspekList as $aspek)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $aspek->nama_aspek }}</td>
                            <td class="px-4 py-3">
                                <div class="relative w-28">
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        name="bobot[{{ $aspek->id }}]"
                                        value="{{ $aspek->bobot * 100 }}"
                                        class="w-full pr-7 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-500">%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="submit"
                                    form="delete-aspek-{{ $aspek->id }}"
                                    class="px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 text-xs font-medium"
                                    onclick="return confirm('Hapus aspek ini?')">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-4 py-3 text-sm text-gray-900">Total Bobot</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 text-green-700">
                                    <span id="rubrik-total" class="text-sm">{{ number_format($totalBobot * 100, 2) }}%</span>
                                    <span id="rubrik-total-badge" class="text-xs px-2 py-0.5 rounded-full {{ $isBobotValid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $isBobotValid ? 'Valid' : 'Belum valid' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="submit" form="rubrik-form" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-medium">
                                    Simpan Rubrik
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
            </form>

            @foreach ($aspekList as $aspek)
            <form id="delete-aspek-{{ $aspek->id }}" method="POST" action="{{ route('admin.penilaian.aspek.destroy', $aspek->id) }}">
                @csrf
                @method('DELETE')
            </form>
            @endforeach

        </div>

        <form method="GET" action="{{ route('admin.penilaian') }}" class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Filter Penilaian</h3>
            </div>

            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="flex-1 flex flex-wrap items-end gap-4">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                    <select name="tahun_ajaran" onchange="this.form.submit()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua</option>
                        @foreach ($tahunAjaranList as $tahun)
                        <option value="{{ $tahun }}" {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                            {{ $tahun }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" onchange="this.form.submit()" class="w-[200px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua Jurusan</option>
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) $filters['jurusan_id'] === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                    <select name="industri_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all">
                        <option value="">Semua Industri</option>
                        @foreach ($industriOptions as $industri)
                        <option value="{{ $industri->id }}" {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                            {{ $industri->nama_industri }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px] flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Nama siswa"
                            class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                            oninput="debouncedSubmit(this)">
                    </div>
                </div>
                </div>
                <div class="flex items-center gap-3">
                <a href="{{ route('admin.penilaian') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all text-sm font-medium">
                    Reset
                </a>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jurusan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Industri</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Nilai</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catatan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($penilaianList as $row)
                        @php
                        $detailItems = $row->detailPenilaian->map(function ($detail) {
                        return [
                        'aspek' => $detail->aspekPenilaian?->nama_aspek ?? '-',
                        'bobot' => $detail->aspekPenilaian?->bobot ?? null,
                        'nilai' => $detail->nilai,
                        ];
                        })->values();
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->tanggal_penilaian?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row->catatan ? \Illuminate\Support\Str::limit($row->catatan, 80) : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium"
                                    @click="detailOpen = true; detailNama = @js($row->siswa?->user?->name ?? '-'); detailIndustri = @js($row->industri?->nama_industri ?? '-'); detailList = @js($detailItems); detailTotal = @js($row->total_nilai !== null ? number_format($row->total_nilai, 2) : '-');">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-6 py-6 text-center text-sm text-gray-500" colspan="7">
                                Belum ada data penilaian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $penilaianList->links() }}
        </div>

        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-4 border-b">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Detail Penilaian</h4>
                        <p class="text-xs text-gray-500">
                            <span x-text="detailNama"></span> · <span x-text="detailIndustri"></span>
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="detailOpen = false">✕</button>
                </div>
                <div class="p-4">
                    <template x-if="detailList.length === 0">
                        <div class="text-sm text-gray-500 italic">Belum ada detail penilaian.</div>
                    </template>
                    <template x-if="detailList.length > 0">
                        <div class="space-y-3">
                            <template x-for="item in detailList" :key="item.aspek">
                                <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="item.aspek"></div>
                                        <div class="text-xs text-gray-500" x-show="item.bobot !== null">
                                            Bobot: <span x-text="(item.bobot * 100).toFixed(0) + '%'"></span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700">
                                        Nilai <span x-text="Number(item.nilai).toFixed(2)"></span>
                                    </span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between border border-emerald-200 rounded-lg px-3 py-2 bg-emerald-50">
                                <span class="text-sm font-semibold text-emerald-700">Total Nilai</span>
                                <span class="text-sm font-semibold text-emerald-700" x-text="detailTotal"></span>
                            </div>
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

    const rubrikForm = document.getElementById('rubrik-form');
    const rubrikToast = document.getElementById('rubrik-toast');
    if (rubrikForm) {
        rubrikForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = rubrikForm.querySelector('button[type="submit"]');
            const totalLabel = document.getElementById('rubrik-total');
            const totalBadge = document.getElementById('rubrik-total-badge');
            const statusBadge = document.getElementById('rubrik-status-badge');

            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';

            try {
                const response = await fetch(rubrikForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: new FormData(rubrikForm),
                });

                if (!response.ok) {
                    throw new Error('Gagal menyimpan rubrik');
                }

                const data = await response.json();
                const total = Number(data.total || 0);
                const totalPercent = `${total.toFixed(2)}%`;

                totalLabel.textContent = totalPercent;

                const validClass = 'bg-green-100 text-green-700';
                const invalidClass = 'bg-red-100 text-red-700';
                const totalValid = data.is_valid === true;

                totalBadge.className = `text-xs px-2 py-0.5 rounded-full ${totalValid ? validClass : invalidClass}`;
                totalBadge.textContent = totalValid ? 'Valid' : 'Belum valid';

                statusBadge.className = `text-xs px-2.5 py-1 rounded-full ${totalValid ? validClass : invalidClass}`;
                statusBadge.textContent = totalValid ? 'Total bobot valid' : 'Total bobot harus 100%';

                showRubrikToast(data.message || 'Rubrik penilaian berhasil diperbarui.');
            } catch (error) {
                alert('Rubrik gagal disimpan. Coba lagi.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Simpan Rubrik';
            }
        });
    }

    function showRubrikToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-6 right-6 z-50 max-w-sm w-full bg-white shadow-md rounded-lg overflow-hidden';
        toast.innerHTML = `
            <div class="flex">
                <div class="flex justify-center items-center w-12 bg-emerald-500">
                    <svg class="h-6 w-6 fill-current text-white" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 3.33331C10.8 3.33331 3.33337 10.8 3.33337 20C3.33337 29.2 10.8 36.6666 20 36.6666C29.2 36.6666 36.6667 29.2 36.6667 20C36.6667 10.8 29.2 3.33331 20 3.33331ZM16.6667 28.3333L8.33337 20L10.6834 17.65L16.6667 23.6166L29.3167 10.9666L31.6667 13.3333L16.6667 28.3333Z" />
                    </svg>
                </div>
                <div class="flex-1 -mx-3 py-2 px-4">
                    <div class="mx-3">
                        <span class="text-emerald-600 font-semibold">Success</span>
                        <p class="text-gray-600 text-sm">${message}</p>
                    </div>
                </div>
                <button type="button" class="px-4 text-gray-400 hover:text-gray-600">✕</button>
            </div>
        `;

        toast.querySelector('button').addEventListener('click', () => {
            toast.remove();
        });

        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
