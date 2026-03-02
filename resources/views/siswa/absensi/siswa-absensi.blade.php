@section('title', 'Absensi PKL')

@php
    use App\Enums\AbsensiStatus;
    use App\Enums\PenempatanStatus;

    $industri = $penempatan?->industri;
    $isPenempatanAktif = $penempatan?->status === PenempatanStatus::DITERIMA_INDUSTRI->value && $industri;
    $geofenceSet = $industri?->latitude !== null && $industri?->longitude !== null;
    $canCheckIn = $isPenempatanAktif && $geofenceSet && !$todayAbsensi;
@endphp

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mapElement = document.getElementById('siswa-absensi-map');
        if (!mapElement) {
            return;
        }

        const industriLat = parseFloat(mapElement.dataset.industriLat);
        const industriLng = parseFloat(mapElement.dataset.industriLng);
        const industriName = mapElement.dataset.industriName || 'Lokasi Industri';
        const radius = parseInt(mapElement.dataset.radius || '200', 10);
        const oldLat = parseFloat(mapElement.dataset.oldLat);
        const oldLng = parseFloat(mapElement.dataset.oldLng);
        const oldAccuracy = parseFloat(mapElement.dataset.oldAccuracy || '0');
        const geofenceReady = !Number.isNaN(industriLat) && !Number.isNaN(industriLng);

        const latInput = document.getElementById('absensi-latitude');
        const lngInput = document.getElementById('absensi-longitude');
        const accuracyInput = document.getElementById('absensi-accuracy');
        const catatanLabel = document.getElementById('catatan-label');
        const catatanInput = document.getElementById('catatan-textarea');
        const statusLokasi = document.getElementById('status-lokasi');
        const submitButton = document.getElementById('submit-absensi-btn');
        const ambilLokasiButton = document.getElementById('ambil-lokasi-btn');

        let map = null;
        let siswaMarker = null;
        let accuracyCircle = null;

        if (typeof L !== 'undefined') {
            const defaultCenter = geofenceReady ? [industriLat, industriLng] : [-6.200000, 106.816666];
            map = L.map('siswa-absensi-map').setView(defaultCenter, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            if (geofenceReady) {
                L.marker([industriLat, industriLng]).addTo(map).bindPopup(industriName);
                L.circle([industriLat, industriLng], {
                    radius: radius,
                    color: '#16a34a',
                    fillColor: '#86efac',
                    fillOpacity: 0.2
                }).addTo(map);
            }
        } else {
            mapElement.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-amber-700 bg-amber-50">Leaflet gagal dimuat. Peta tidak tersedia, tetapi check-in lokasi tetap bisa dilakukan.</div>';
        }

        function haversineDistanceMeters(lat1, lon1, lat2, lon2) {
            const toRad = (deg) => (deg * Math.PI) / 180;
            const earthRadius = 6371000;
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2
                + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return earthRadius * c;
        }

        function syncCatatanRequirement(distanceMeters) {
            if (!catatanInput || !catatanLabel) {
                return;
            }

            if (!geofenceReady || Number.isNaN(distanceMeters)) {
                catatanInput.removeAttribute('required');
                catatanLabel.textContent = 'Catatan (opsional)';
                return;
            }

            if (distanceMeters > radius) {
                catatanInput.setAttribute('required', 'required');
                catatanLabel.textContent = 'Catatan (wajib karena di luar area)';
            } else {
                catatanInput.removeAttribute('required');
                catatanLabel.textContent = 'Catatan (opsional)';
            }
        }

        function updateSubmitState() {
            if (!submitButton) {
                return;
            }

            const hasLatLng = latInput.value && lngInput.value;
            if (!hasLatLng && !submitButton.hasAttribute('disabled')) {
                submitButton.setAttribute('disabled', 'disabled');
            }
            if (hasLatLng && submitButton.dataset.serverDisabled !== '1') {
                submitButton.removeAttribute('disabled');
            }
        }

        function setLokasi(lat, lng, accuracy = null) {
            latInput.value = Number(lat).toFixed(7);
            lngInput.value = Number(lng).toFixed(7);
            accuracyInput.value = accuracy !== null && !Number.isNaN(accuracy)
                ? Number(accuracy).toFixed(2)
                : '';

            if (map) {
                if (siswaMarker) {
                    map.removeLayer(siswaMarker);
                }
                if (accuracyCircle) {
                    map.removeLayer(accuracyCircle);
                }

                siswaMarker = L.marker([lat, lng]).addTo(map).bindPopup('Posisi Anda').openPopup();
                if (accuracy !== null && !Number.isNaN(accuracy)) {
                    accuracyCircle = L.circle([lat, lng], {
                        radius: accuracy,
                        color: '#2563eb',
                        fillColor: '#93c5fd',
                        fillOpacity: 0.25
                    }).addTo(map);
                }

                map.setView([lat, lng], 16);
            }

            let info = `Lokasi siap: ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}${accuracy ? ` (akurasi ±${Number(accuracy).toFixed(1)} m)` : ''}`;
            let distance = NaN;
            if (geofenceReady) {
                distance = haversineDistanceMeters(lat, lng, industriLat, industriLng);
                const distanceText = Number(distance).toFixed(2);
                if (distance <= radius) {
                    info += ` | Dalam area (${distanceText} m dari titik industri)`;
                    statusLokasi.className = 'px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700';
                } else {
                    info += ` | Di luar area (${distanceText} m, radius ${radius} m)`;
                    statusLokasi.className = 'px-3 py-2 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-700';
                }
            } else {
                statusLokasi.className = 'px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700';
            }

            statusLokasi.textContent = info;
            syncCatatanRequirement(distance);
            updateSubmitState();
        }

        if (submitButton && submitButton.hasAttribute('disabled')) {
            submitButton.dataset.serverDisabled = '1';
        }

        if (!Number.isNaN(oldLat) && !Number.isNaN(oldLng)) {
            setLokasi(oldLat, oldLng, Number.isNaN(oldAccuracy) ? null : oldAccuracy);
        } else {
            updateSubmitState();
        }

        if (ambilLokasiButton) {
            ambilLokasiButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    statusLokasi.textContent = 'Browser tidak mendukung geolocation.';
                    statusLokasi.className = 'px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700';
                    return;
                }

                statusLokasi.textContent = 'Mengambil lokasi...';
                statusLokasi.className = 'px-3 py-2 bg-sky-50 border border-sky-200 rounded-lg text-sm text-sky-700';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setLokasi(
                            position.coords.latitude,
                            position.coords.longitude,
                            position.coords.accuracy
                        );
                    },
                    (error) => {
                        statusLokasi.textContent = `Gagal mengambil lokasi: ${error.message}`;
                        statusLokasi.className = 'px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            });
        }
    });
</script>
@endpush

<x-admin-layout>
    <div class="space-y-6">
        <div class="mb-2">
            <div class="text-sm text-gray-500 mb-2">Dashboard → Absensi PKL</div>
            <h1 class="text-gray-900 text-2xl font-semibold mb-2">Absensi Lokasi Siswa</h1>
            <p class="text-gray-500 text-sm max-w-2xl">
                Lakukan check-in harian berbasis lokasi untuk memvalidasi kehadiran di area industri.
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Industri Aktif</div>
                <div class="text-sm font-semibold text-gray-900">{{ $industri?->nama_industri ?? 'Belum tersedia' }}</div>
                <div class="text-xs text-gray-500 mt-2">{{ $industri?->alamat ?? '-' }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Geofence</div>
                <div class="text-sm font-semibold {{ $geofenceSet ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $geofenceSet ? 'Aktif' : 'Belum diatur admin' }}
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    Radius: {{ $industri?->geofence_radius_m ?? 200 }} m
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="text-xs text-gray-500 mb-1">Check-in Hari Ini</div>
                <div class="text-sm font-semibold {{ $todayAbsensi ? 'text-emerald-700' : 'text-gray-900' }}">
                    {{ $todayAbsensi ? 'Sudah check-in' : 'Belum check-in' }}
                </div>
                @if ($todayAbsensi?->check_in_at)
                <div class="text-xs text-gray-500 mt-2">{{ $todayAbsensi->check_in_at->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Check-in Lokasi</h3>
                @if (!$isPenempatanAktif)
                <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700">
                    Penempatan belum diterima industri
                </span>
                @endif
            </div>

            @if ($isPenempatanAktif && !$geofenceSet)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Geofence industri belum diatur admin, jadi check-in belum bisa dilakukan.
            </div>
            @endif

            <div
                id="siswa-absensi-map"
                class="w-full h-[320px] rounded-lg border border-gray-200 mb-4"
                data-industri-lat="{{ $industri?->latitude }}"
                data-industri-lng="{{ $industri?->longitude }}"
                data-industri-name="{{ $industri?->nama_industri }}"
                data-radius="{{ $industri?->geofence_radius_m ?? 200 }}"
                data-old-lat="{{ old('latitude') }}"
                data-old-lng="{{ old('longitude') }}"
                data-old-accuracy="{{ old('accuracy_m') }}">
            </div>

            <form method="POST" action="{{ route('siswa.absensi.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="latitude" id="absensi-latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="absensi-longitude" value="{{ old('longitude') }}">
                <input type="hidden" name="accuracy_m" id="absensi-accuracy" value="{{ old('accuracy_m') }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Status Lokasi</label>
                        <div id="status-lokasi" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
                            Belum mengambil lokasi.
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Aksi Lokasi</label>
                        <button
                            type="button"
                            id="ambil-lokasi-btn"
                            class="w-full px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 text-sm font-medium">
                            Ambil Lokasi Saya
                        </button>
                    </div>
                </div>

                <div>
                    <label id="catatan-label" class="block text-xs font-medium text-gray-700 mb-1.5">Catatan (opsional)</label>
                    <textarea
                        id="catatan-textarea"
                        name="catatan"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="Contoh: Sinyal GPS kurang stabil, cek ulang lokasi.">{{ old('catatan') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        id="submit-absensi-btn"
                        class="px-5 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $canCheckIn ? '' : 'disabled' }}>
                        Simpan Check-in
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Riwayat Absensi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Industri</th>
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
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->check_in_at?->format('H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->industri?->nama_industri ?? '-' }}</td>
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
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada data absensi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $absensiList->links() }}
        </div>
    </div>
</x-admin-layout>
