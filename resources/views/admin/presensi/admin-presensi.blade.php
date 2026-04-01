@section('title', 'Presensi PKL')

@php
    use App\Enums\AbsensiStatus;
@endphp

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        window.initAdminPresensiMap = function initAdminPresensiMap() {
            const mapElement = document.getElementById('admin-presensi-map');
            const hasLeaflet = typeof L !== 'undefined';
            const statusLabels = @json($statusLabels);
            if (!mapElement) {
                return;
            }

            if (!hasLeaflet) {
                mapElement.innerHTML =
                    '<div class="h-full flex items-center justify-center text-sm text-amber-700 bg-amber-50">Leaflet gagal dimuat. Cek koneksi internet/CDN atau gunakan asset Leaflet lokal.</div>';
            }

            if (hasLeaflet) {
                // Peta monitoring presensi harian (read-only) untuk admin.
                const points = @json($mapPoints);
                const map = L.map('admin-presensi-map').setView([-6.200000, 106.816666], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                const markers = [];
                points.forEach((point) => {
                    const lat = Number(point.latitude);
                    const lng = Number(point.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return;
                    }

                    const color = point.status === '{{ AbsensiStatus::HADIR_VALID_LOKASI->value }}' ?
                        '#16a34a' :
                        (point.status === '{{ AbsensiStatus::HADIR_VALID_LUAR_LOKASI->value }}' ? '#2563eb' :
                            (point.status === '{{ AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value }}' ? '#d97706' : '#dc2626'));

                    const marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        color,
                        fillColor: color,
                        fillOpacity: 0.8
                    }).addTo(map);

                    marker.bindPopup(`
                    <div style="min-width:220px">
                        <strong>${point.siswa}</strong><br>
                        NIS: ${point.nis}<br>
                        Industri: ${point.industri}<br>
                        Status: ${statusLabels[point.status] ?? point.status}<br>
                        Catatan: ${point.catatan ?? '-'}<br>
                        Approval: ${point.approval_status ?? '-'}<br>
                        Waktu: ${point.check_in_at ?? '-'}<br>
                        Jarak: ${point.distance ? Number(point.distance).toFixed(2) + ' m' : '-'}
                    </div>
                `);

                    markers.push(marker);
                });

                if (markers.length > 0) {
                    const group = L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.2));
                }

                // Pastikan ukuran peta sinkron setelah layout selesai dirender.
                setTimeout(() => map.invalidateSize(), 120);
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            window.initAdminPresensiMap();
        });
    </script>
@endpush

<x-admin-layout>
    <div class="space-y-6">
        <div class="mb-2">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Presensi PKL</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Monitoring Presensi Lokasi</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Pantau check-in harian siswa berbasis lokasi, termasuk validasi geofence industri.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="admin-presensi-filter-target">
            <form id="admin-presensi-filter-form" method="GET" action="{{ route('admin.presensi') }}"
                class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                            <input type="date" name="date" value="{{ $filters['date'] }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Tahun Ajaran</label>
                            <select name="tahun_ajaran"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Semua</option>
                                @foreach ($tahunAjaranOptions as $tahun)
                                    <option value="{{ $tahun }}"
                                        {{ (string) $filters['tahun_ajaran'] === (string) $tahun ? 'selected' : '' }}>
                                        {{ $tahun }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                            <select name="jurusan_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Semua</option>
                                @foreach ($jurusanOptions as $jurusan)
                                    <option value="{{ $jurusan->id }}"
                                        {{ (string) $filters['jurusan_id'] === (string) $jurusan->id ? 'selected' : '' }}>
                                        {{ $jurusan->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                            <select name="industri_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Semua</option>
                                @foreach ($industriOptions as $industri)
                                    <option value="{{ $industri->id }}"
                                        {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                                        {{ $industri->nama_industri }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                            <input type="text" name="q" value="{{ $filters['q'] }}"
                                placeholder="Nama atau NIS"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.presensi') }}"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Valid di Lokasi</div>
                <div class="text-2xl font-semibold text-emerald-700">
                    {{ $statusCounts[AbsensiStatus::HADIR_VALID_LOKASI->value] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Menunggu Persetujuan</div>
                <div class="text-2xl font-semibold text-amber-700">
                    {{ $statusCounts[AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Valid di Luar Lokasi</div>
                <div class="text-2xl font-semibold text-sky-700">
                    {{ $statusCounts[AbsensiStatus::HADIR_VALID_LUAR_LOKASI->value] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Alpha</div>
                <div class="text-2xl font-semibold text-rose-700">
                    {{ $statusCounts[AbsensiStatus::ALPHA->value] ?? 0 }}</div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-900">Peta Lokasi</h3>
                        <div class="text-xs text-gray-500">{{ count($mapPoints) }} titik pada halaman ini</div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                        <span class="inline-flex items-center gap-2"><span
                                class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>Valid di Lokasi</span>
                        <span class="inline-flex items-center gap-2"><span
                                class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>Menunggu</span>
                        <span class="inline-flex items-center gap-2"><span
                                class="w-2.5 h-2.5 rounded-full bg-sky-600"></span>Valid di Luar Lokasi</span>
                        <span class="inline-flex items-center gap-2"><span
                                class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>Alpha</span>
                    </div>
                </div>
                <div id="admin-presensi-map" class="w-full h-[420px] md:h-[520px] xl:h-[560px]"></div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900">Data Presensi</h3>
                <div class="text-xs text-gray-500">{{ $absensiList->total() }} data</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1120px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Koordinat
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jarak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($absensiList as $row)
                            @php
                                $statusClass = match ($row->status) {
                                    AbsensiStatus::HADIR_VALID_LOKASI->value
                                        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                    AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value
                                        => 'bg-amber-50 text-amber-700 border border-amber-200',
                                    AbsensiStatus::HADIR_VALID_LUAR_LOKASI->value
                                        => 'bg-sky-50 text-sky-700 border border-sky-200',
                                    AbsensiStatus::ALPHA->value
                                        => 'bg-rose-50 text-rose-700 border border-rose-200',
                                    default => 'bg-gray-50 text-gray-700 border border-gray-200',
                                };
                                $statusKey = 'presensi.status.' . $row->status;
                                $statusLabel = \Illuminate\Support\Facades\Lang::has($statusKey)
                                    ? __($statusKey)
                                    : ucfirst(str_replace('_', ' ', $row->status));
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $row->industri?->nama_industri ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $row->check_in_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ number_format((float) $row->latitude, 6) }},
                                    {{ number_format((float) $row->longitude, 6) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $row->distance_to_industri_m !== null ? number_format((float) $row->distance_to_industri_m, 2) . ' m' : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $row->catatan ? $row->catatan : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Belum ada data presensi pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $absensiList->links() }}
            </div>
        </div>

        <div id="geofence-setting" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="flex flex-col gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Data Geofence Industri</h3>
                        @if (!$radiusUniform)
                            <p class="text-xs text-amber-700 mt-1">Radius antar industri saat ini berbeda. Simpan
                                radius global untuk menyeragamkan.</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.presensi.geofence.radius-global') }}"
                        class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Radius Global (m)</label>
                            <input type="number" name="geofence_radius_m" min="20" max="5000"
                                value="{{ old('geofence_radius_m', $globalRadiusM ?? 200) }}"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-[140px]">
                        </div>
                        <button
                            class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                            Terapkan ke Semua
                        </button>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Latitude
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Longitude
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Radius
                                (m)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($geofenceList as $industri)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $industri->nama_industri }}</div>
                                    <div class="text-xs text-gray-500">{{ $industri->alamat }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $industri->jurusan?->nama ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $industri->latitude !== null ? number_format((float) $industri->latitude, 7) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $industri->longitude !== null ? number_format((float) $industri->longitude, 7) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $industri->geofence_radius_m !== null ? number_format((float) $industri->geofence_radius_m, 0) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Belum ada data industri.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
@include('partials.ajax-filter-script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.setupAjaxFilter({
            formId: 'admin-presensi-filter-form',
            targetId: 'admin-presensi-filter-target',
            debounce: 500,
            afterReplace: 'initAdminPresensiMap',
        });
    });
</script>
