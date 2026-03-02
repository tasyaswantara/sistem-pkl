@section('title', 'Absensi PKL')

@php
    use App\Enums\AbsensiStatus;
@endphp

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mapElement = document.getElementById('admin-absensi-map');
        const hasLeaflet = typeof L !== 'undefined';
        if (!mapElement) {
            return;
        }

        if (!hasLeaflet) {
            mapElement.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-amber-700 bg-amber-50">Leaflet gagal dimuat. Cek koneksi internet/CDN atau gunakan asset Leaflet lokal.</div>';
        }

        if (hasLeaflet) {
            // Peta monitoring absensi harian (read-only) untuk admin.
            const points = @json($mapPoints);
            const map = L.map('admin-absensi-map').setView([-6.200000, 106.816666], 11);
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

                const color = point.status === '{{ AbsensiStatus::HADIR_VALID->value }}'
                    ? '#16a34a'
                    : (point.status === '{{ AbsensiStatus::DI_LUAR_AREA->value }}' ? '#e11d48' : '#6b7280');

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
                        Status: ${point.status}<br>
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
        }

        // Modal ini dipakai khusus untuk koreksi titik geofence per industri.
        const correctionModal = document.getElementById('geofence-correction-modal');
        const correctionMapElement = document.getElementById('geofence-correction-map');
        const correctionTitle = document.getElementById('correction-title');
        const correctionHint = document.getElementById('correction-hint');
        const closeCorrectionBtn = document.getElementById('close-correction-btn');
        const applyCorrectionBtn = document.getElementById('apply-correction-btn');
        const radiusWatcherInput = document.getElementById('correction-radius-input');
        const openButtons = document.querySelectorAll('.open-correction-map-btn');
        let selectedIndustryId = null;
        let correctionMap = null;
        let correctionMarker = null;
        let correctionCircle = null;

        function closeCorrectionModal() {
            if (correctionModal) {
                correctionModal.classList.add('hidden');
            }
        }

        function upsertMarkerAndCircle(lat, lng, radius) {
            if (!correctionMap || !hasLeaflet) {
                return;
            }

            if (correctionMarker) {
                correctionMarker.setLatLng([lat, lng]);
            } else {
                correctionMarker = L.marker([lat, lng], { draggable: true }).addTo(correctionMap);
                correctionMarker.on('dragend', () => {
                    const pos = correctionMarker.getLatLng();
                    if (selectedIndustryId !== null) {
                        const latInput = document.getElementById(`geofence-lat-${selectedIndustryId}`);
                        const lngInput = document.getElementById(`geofence-lng-${selectedIndustryId}`);
                        if (latInput && lngInput) {
                            latInput.value = Number(pos.lat).toFixed(7);
                            lngInput.value = Number(pos.lng).toFixed(7);
                        }
                    }
                    if (correctionCircle) {
                        correctionCircle.setLatLng(pos);
                    }
                });
            }

            if (correctionCircle) {
                correctionCircle.setLatLng([lat, lng]);
                correctionCircle.setRadius(radius);
            } else {
                correctionCircle = L.circle([lat, lng], {
                    radius: radius,
                    color: '#2563eb',
                    fillColor: '#93c5fd',
                    fillOpacity: 0.18,
                }).addTo(correctionMap);
            }

            correctionMap.setView([lat, lng], 16);
        }

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!hasLeaflet) {
                    alert('Leaflet tidak tersedia, koreksi via peta belum bisa digunakan.');
                    return;
                }

                selectedIndustryId = button.dataset.industriId;
                const name = button.dataset.industriName || 'Industri';
                const address = button.dataset.industriAddress || '-';
                const latInput = document.getElementById(`geofence-lat-${selectedIndustryId}`);
                const lngInput = document.getElementById(`geofence-lng-${selectedIndustryId}`);
                const radiusInput = document.getElementById(`geofence-radius-${selectedIndustryId}`);
                if (!latInput || !lngInput || !radiusInput) {
                    return;
                }

                const lat = Number(latInput.value || button.dataset.defaultLat || -6.2);
                const lng = Number(lngInput.value || button.dataset.defaultLng || 106.816666);
                const radius = Number(radiusInput.value || 200);
                if (correctionTitle) {
                    correctionTitle.textContent = `Koreksi Titik: ${name}`;
                }
                if (correctionHint) {
                    correctionHint.textContent = address;
                }
                if (radiusWatcherInput) {
                    radiusWatcherInput.value = String(radius);
                }

                correctionModal.classList.remove('hidden');
                if (!correctionMap) {
                    correctionMap = L.map(correctionMapElement).setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(correctionMap);
                    correctionMap.on('click', (e) => {
                        // Klik peta langsung memindahkan titik geofence.
                        const { lat: clickedLat, lng: clickedLng } = e.latlng;
                        const latInputInner = document.getElementById(`geofence-lat-${selectedIndustryId}`);
                        const lngInputInner = document.getElementById(`geofence-lng-${selectedIndustryId}`);
                        if (latInputInner && lngInputInner) {
                            latInputInner.value = Number(clickedLat).toFixed(7);
                            lngInputInner.value = Number(clickedLng).toFixed(7);
                        }
                        const radiusInner = Number(radiusWatcherInput?.value || 200);
                        upsertMarkerAndCircle(clickedLat, clickedLng, radiusInner);
                    });
                }

                upsertMarkerAndCircle(lat, lng, radius);
                setTimeout(() => correctionMap.invalidateSize(), 0);
            });
        });

        if (radiusWatcherInput) {
            radiusWatcherInput.addEventListener('input', () => {
                const radius = Number(radiusWatcherInput.value || 200);
                if (selectedIndustryId !== null) {
                    const radiusInput = document.getElementById(`geofence-radius-${selectedIndustryId}`);
                    if (radiusInput) {
                        radiusInput.value = String(Math.max(radius, 20));
                    }
                }
                if (correctionCircle) {
                    correctionCircle.setRadius(Math.max(radius, 20));
                }
            });
        }

        if (applyCorrectionBtn) {
            applyCorrectionBtn.addEventListener('click', () => {
                if (!correctionMarker || selectedIndustryId === null) {
                    closeCorrectionModal();
                    return;
                }

                const pos = correctionMarker.getLatLng();
                const latInput = document.getElementById(`geofence-lat-${selectedIndustryId}`);
                const lngInput = document.getElementById(`geofence-lng-${selectedIndustryId}`);
                if (latInput && lngInput) {
                    // Sinkronkan posisi marker ke input form sebelum disimpan.
                    latInput.value = Number(pos.lat).toFixed(7);
                    lngInput.value = Number(pos.lng).toFixed(7);
                }
                closeCorrectionModal();
            });
        }

        if (closeCorrectionBtn) {
            closeCorrectionBtn.addEventListener('click', closeCorrectionModal);
        }
        if (correctionModal) {
            correctionModal.addEventListener('click', (event) => {
                if (event.target === correctionModal) {
                    closeCorrectionModal();
                }
            });
        }
    });
</script>
@endpush

<x-admin-layout>
    <div class="space-y-6">
        <div class="mb-2">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Absensi PKL</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Monitoring Absensi Lokasi</h1>
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

        <form method="GET" action="{{ route('admin.absensi') }}" class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Tanggal</label>
                    <input type="date" name="date" value="{{ $filters['date'] }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Jurusan</label>
                    <select name="jurusan_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua</option>
                        @foreach ($jurusanOptions as $jurusan)
                        <option value="{{ $jurusan->id }}" {{ (string) $filters['jurusan_id'] === (string) $jurusan->id ? 'selected' : '' }}>
                            {{ $jurusan->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Industri</label>
                    <select name="industri_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua</option>
                        @foreach ($industriOptions as $industri)
                        <option value="{{ $industri->id }}" {{ (string) $filters['industri_id'] === (string) $industri->id ? 'selected' : '' }}>
                            {{ $industri->nama_industri }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ (string) $filters['status'] === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Cari Siswa</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nama atau NIS"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                    Terapkan
                </button>
                <a href="{{ route('admin.absensi') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                    Reset
                </a>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Hadir Valid</div>
                <div class="text-2xl font-semibold text-emerald-700">{{ $statusCounts[AbsensiStatus::HADIR_VALID->value] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Di Luar Area</div>
                <div class="text-2xl font-semibold text-rose-700">{{ $statusCounts[AbsensiStatus::DI_LUAR_AREA->value] ?? 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Peta Check-in</h3>
                <div class="text-xs text-gray-500">{{ count($mapPoints) }} titik di halaman ini</div>
            </div>
            <div id="admin-absensi-map" class="w-full h-[360px] rounded-lg border border-gray-200"></div>
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>Hadir Valid</span>
                <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>Di Luar Area</span>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Data Absensi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Koordinat</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jarak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($absensiList as $row)
                        @php
                        $statusClass = match ($row->status) {
                            AbsensiStatus::HADIR_VALID->value => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            AbsensiStatus::DI_LUAR_AREA->value => 'bg-rose-50 text-rose-700 border border-rose-200',
                            default => 'bg-gray-50 text-gray-700 border border-gray-200',
                        };
                        $statusKey = 'absensi.status.' . $row->status;
                        $statusLabel = \Illuminate\Support\Facades\Lang::has($statusKey)
                            ? __($statusKey)
                            : ucfirst(str_replace('_', ' ', $row->status));
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $row->siswa?->user?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->siswa?->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->check_in_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ number_format((float) $row->latitude, 6) }}, {{ number_format((float) $row->longitude, 6) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $row->distance_to_industri_m !== null ? number_format((float) $row->distance_to_industri_m, 2) . ' m' : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada data absensi pada filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $absensiList->links() }}</div>

        <div id="geofence-setting" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Pengaturan Geofence Industri (Admin)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jurusan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Latitude</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Longitude</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Radius (m)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($geofenceList as $industri)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $industri->nama_industri }}</div>
                                <div class="text-xs text-gray-500">{{ $industri->alamat }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $industri->jurusan?->nama ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <input
                                    id="geofence-lat-{{ $industri->id }}"
                                    type="number"
                                    step="0.0000001"
                                    name="latitude"
                                    form="update-geofence-{{ $industri->id }}"
                                    value="{{ old('latitude', $industri->latitude) }}"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-[170px]">
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    id="geofence-lng-{{ $industri->id }}"
                                    type="number"
                                    step="0.0000001"
                                    name="longitude"
                                    form="update-geofence-{{ $industri->id }}"
                                    value="{{ old('longitude', $industri->longitude) }}"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-[170px]">
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    id="geofence-radius-{{ $industri->id }}"
                                    type="number"
                                    min="20"
                                    max="5000"
                                    name="geofence_radius_m"
                                    form="update-geofence-{{ $industri->id }}"
                                    value="{{ old('geofence_radius_m', $industri->geofence_radius_m ?? 200) }}"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-[120px]">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <form id="update-geofence-{{ $industri->id }}" method="POST" action="{{ route('admin.absensi.geofence', $industri->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                                            Simpan
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.absensi.geofence.geocode', $industri->id) }}">
                                        @csrf
                                        <button class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-medium">
                                            Auto Geocode
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        class="open-correction-map-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-medium"
                                        data-industri-id="{{ $industri->id }}"
                                        data-industri-name="{{ $industri->nama_industri }}"
                                        data-industri-address="{{ $industri->alamat }}"
                                        data-default-lat="{{ $industri->latitude ?? -6.2000000 }}"
                                        data-default-lng="{{ $industri->longitude ?? 106.8166660 }}">
                                        Koreksi di Peta
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada data industri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="geofence-correction-modal" class="fixed inset-0 z-50 hidden bg-black/40 p-4">
            <div class="mx-auto mt-10 max-w-3xl bg-white rounded-xl shadow-xl border border-gray-200">
                <div class="flex items-start justify-between p-4 border-b border-gray-200">
                    <div>
                        <h4 id="correction-title" class="text-base font-semibold text-gray-900">Koreksi Titik Geofence</h4>
                        <p id="correction-hint" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                    <button type="button" id="close-correction-btn" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="p-4">
                    <div id="geofence-correction-map" class="w-full h-[420px] rounded-lg border border-gray-200"></div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Klik peta atau drag marker untuk mengoreksi titik.
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="correction-radius-input" class="text-xs text-gray-600">Radius (m)</label>
                            <input id="correction-radius-input" type="number" min="20" max="5000" value="200"
                                class="w-28 px-2 py-1.5 border border-gray-300 rounded text-sm">
                            <button type="button" id="apply-correction-btn"
                                class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-medium">
                                Gunakan Titik Ini
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
