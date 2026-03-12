@section('title', 'Presensi Harian')

@php
    use App\Enums\LogbookStatus;
    use App\Enums\PenempatanStatus;
    use App\Enums\PerizinanStatus;

    $industri = $penempatan?->industri;
    $isPenempatanAktif = $penempatan?->status === PenempatanStatus::DITERIMA_INDUSTRI->value && $industri;
    $geofenceSet = $industri?->latitude !== null && $industri?->longitude !== null;

    $weekMeta = [
        'hadir' => [
            'box' => 'bg-emerald-100 border-emerald-200 text-emerald-800',
            'dot' => 'bg-emerald-500',
            'label' => 'Hadir',
        ],
        'izin' => [
            'box' => 'bg-amber-100 border-amber-200 text-amber-800',
            'dot' => 'bg-amber-500',
            'label' => 'Izin',
        ],
        'tidak_absen' => [
            'box' => 'bg-rose-100 border-rose-200 text-rose-800',
            'dot' => 'bg-rose-500',
            'label' => 'Tidak Absen',
        ],
    ];

    $logbookFieldErrors = collect(['tanggal', 'aktivitas'])->contains(fn($key) => $errors->has($key))
        || $errors->has('logbook');
    $izinFieldErrors = collect(['jenis_izin', 'tanggal_mulai', 'tanggal_selesai'])->contains(fn($key) => $errors->has($key))
        || $errors->has('perizinan');
    $checkInTimeDisplay = $todayAbsensi?->check_in_at?->format('H:i:s') ?? session('checkin_at');
    $hasCheckInToday = !empty($checkInTimeDisplay);
    $canTapPresensi = $isPenempatanAktif && $geofenceSet;

    $statusPresensiClass = 'rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600';
    $statusPresensiText = 'Klik tombol Presensi untuk mengambil lokasi otomatis.';
@endphp

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        #presensi-map .leaflet-pane,
        #presensi-map .leaflet-top,
        #presensi-map .leaflet-bottom {
            z-index: 20;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapElement = document.getElementById('presensi-map');
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

            const form = document.getElementById('presensi-form');
            const presensiButton = document.getElementById('btn-presensi');
            const statusBox = document.getElementById('status-presensi-lokasi');
            const catatanInput = document.getElementById('presensi-catatan');
            const catatanLabel = document.getElementById('presensi-catatan-label');
            const hasCheckInToday = form?.dataset.hasCheckinToday === '1';
            const checkInTime = form?.dataset.checkinTime || '-';

            const latInput = document.getElementById('presensi-latitude');
            const lngInput = document.getElementById('presensi-longitude');
            const accuracyInput = document.getElementById('presensi-accuracy');

            let map = null;
            let siswaMarker = null;
            let accuracyCircle = null;

            if (typeof L !== 'undefined') {
                const defaultCenter = geofenceReady ? [industriLat, industriLng] : [-7.983908, 112.621391];
                map = L.map('presensi-map').setView(defaultCenter, 14);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                if (geofenceReady) {
                    L.marker([industriLat, industriLng]).addTo(map).bindPopup(industriName);
                    L.circle([industriLat, industriLng], {
                        radius,
                        color: '#0f766e',
                        fillColor: '#5eead4',
                        fillOpacity: 0.2,
                    }).addTo(map);
                }
            } else {
                mapElement.innerHTML = '<div class="h-full flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-sm text-amber-700">Leaflet gagal dimuat. Peta tidak tersedia.</div>';
            }

            function haversineDistanceMeters(lat1, lon1, lat2, lon2) {
                const toRad = (deg) => (deg * Math.PI) / 180;
                const earthRadius = 6371000;
                const dLat = toRad(lat2 - lat1);
                const dLng = toRad(lon2 - lon1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
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
                    catatanLabel.textContent = 'Catatan (wajib jika di luar area)';
                } else {
                    catatanInput.removeAttribute('required');
                    catatanLabel.textContent = 'Catatan (opsional)';
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
                            fillOpacity: 0.2,
                        }).addTo(map);
                    }

                    map.setView([lat, lng], 16);
                }

                let distance = NaN;
                let info = `Lokasi siap: ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;

                if (geofenceReady) {
                    distance = haversineDistanceMeters(lat, lng, industriLat, industriLng);
                    const distanceText = Number(distance).toFixed(2);
                    if (distance <= radius) {
                        info += ` | Dalam area (${distanceText} m)`;
                        statusBox.className = 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700';
                    } else {
                        info += ` | Di luar area (${distanceText} m, radius ${radius} m)`;
                        statusBox.className = 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700';
                    }
                } else {
                    statusBox.className = 'rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';
                }

                statusBox.textContent = info;
                syncCatatanRequirement(distance);
            }

            async function runCheckInWithCurrentLocation() {
                if (!form || !presensiButton) {
                    return;
                }

                if (hasCheckInToday) {
                    statusBox.textContent = `Anda sudah presensi hari ini pada ${checkInTime}.`;
                    statusBox.className = 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700';
                    return;
                }

                if (!navigator.geolocation) {
                    statusBox.textContent = 'Browser tidak mendukung geolocation.';
                    statusBox.className = 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700';
                    return;
                }

                presensiButton.disabled = true;
                presensiButton.textContent = 'Mengambil Lokasi...';

                statusBox.textContent = 'Mengambil lokasi akurat...';
                statusBox.className = 'rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-700';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setLokasi(
                            position.coords.latitude,
                            position.coords.longitude,
                            position.coords.accuracy
                        );

                        if (form && !form.checkValidity()) {
                            form.reportValidity();
                            presensiButton.disabled = false;
                            presensiButton.textContent = 'Presensi Sekarang';
                            statusBox.textContent = catatanInput?.required
                                ? 'Lengkapi catatan jika presensi di luar area.'
                                : 'Lengkapi data presensi yang diperlukan.';
                            statusBox.className = 'rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700';
                            return;
                        }

                        presensiButton.textContent = 'Mengirim Presensi...';
                        form.requestSubmit();
                    },
                    (error) => {
                        statusBox.textContent = `Gagal mengambil lokasi: ${error.message}`;
                        statusBox.className = 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700';
                        presensiButton.disabled = false;
                        presensiButton.textContent = 'Presensi Sekarang';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            }

            if (presensiButton) {
                presensiButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    runCheckInWithCurrentLocation();
                });
            }

            if (!Number.isNaN(oldLat) && !Number.isNaN(oldLng)) {
                setLokasi(oldLat, oldLng, Number.isNaN(oldAccuracy) ? null : oldAccuracy);
            }
        });
    </script>
@endpush

<x-siswa-layout>
    <div
        x-data="{
            izinOpen: @js($izinFieldErrors),
            logbookOpen: @js($logbookFieldErrors),
            editOpen: false,
            editAction: '',
            editTanggal: '',
            editAktivitas: ''
        }"
        class="space-y-6">
        <div>
            <a href="{{ route('siswa.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-[0_6px_18px_rgba(15,46,36,0.12)] hover:bg-gray-50">
                <span class="text-sm">&larr;</span>
                Kembali ke Dashboard
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="mb-1 font-semibold">Terjadi kesalahan:</div>
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-6 xl:grid-cols-5 xl:items-stretch">
            <div class="flex flex-col rounded-2xl bg-white p-4 shadow-[0_10px_24px_rgba(15,46,36,0.12)] xl:col-span-3 sm:p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Peta Lokasi Presensi</h2>
                        <p class="text-xs text-gray-500">{{ $industri?->nama_industri ?? 'Belum ada industri aktif' }}</p>
                    </div>
                    <span class="rounded-full border px-2.5 py-1 text-xs {{ $geofenceSet ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                        {{ $geofenceSet ? 'Geofence Aktif' : 'Geofence Belum Diatur' }}
                    </span>
                </div>

                <div id="presensi-map"
                    class="relative z-0 mt-2 min-h-[430px] flex-1 w-full overflow-hidden rounded-xl border border-gray-200 shadow-sm"
                    data-industri-lat="{{ $industri?->latitude }}"
                    data-industri-lng="{{ $industri?->longitude }}"
                    data-industri-name="{{ $industri?->nama_industri }}"
                    data-radius="{{ $industri?->geofence_radius_m ?? 200 }}"
                    data-old-lat="{{ old('latitude') }}"
                    data-old-lng="{{ old('longitude') }}"
                    data-old-accuracy="{{ old('accuracy_m') }}">
                </div>

                <div class="mt-3 text-xs text-gray-500">
                    Alamat: {{ $industri?->alamat ?? '-' }}
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-[0_10px_24px_rgba(15,46,36,0.12)] xl:col-span-2 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Status 7 Hari Terakhir</h2>
                    <div class="text-xs text-gray-500">Hari ini: {{ now()->locale('id')->translatedFormat('d M Y') }}</div>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    @foreach ($weekDays as $day)
                        @php $meta = $weekMeta[$day['state']]; @endphp
                        <div class="rounded-lg border px-2 py-2 text-center {{ $meta['box'] }} {{ $day['is_today'] ? 'ring-2 ring-teal-400 ring-offset-1' : '' }}">
                            <div class="text-[10px] font-semibold uppercase">{{ $day['day_name'] }}</div>
                            <div class="text-sm font-semibold leading-none mt-1">{{ $day['day_number'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-gray-600">
                    <div class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $weekMeta['hadir']['dot'] }}"></span>Hadir</div>
                    <div class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $weekMeta['izin']['dot'] }}"></span>Izin</div>
                    <div class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $weekMeta['tidak_absen']['dot'] }}"></span>Tidak Absen</div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-emerald-700">
                        <div class="font-semibold text-sm">{{ $weekCounts['hadir'] ?? 0 }}</div>
                        <div>Hadir</div>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-amber-700">
                        <div class="font-semibold text-sm">{{ $weekCounts['izin'] ?? 0 }}</div>
                        <div>Izin</div>
                    </div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-2 text-rose-700">
                        <div class="font-semibold text-sm">{{ $weekCounts['tidak_absen'] ?? 0 }}</div>
                        <div>Tidak</div>
                    </div>
                </div>

                <form id="presensi-form" method="POST" action="{{ route('siswa.presensi.store') }}" class="mt-4 space-y-3"
                    data-has-checkin-today="{{ $hasCheckInToday ? '1' : '0' }}"
                    data-checkin-time="{{ $checkInTimeDisplay ?? '-' }}">
                    @csrf
                    <input type="hidden" name="latitude" id="presensi-latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="presensi-longitude" value="{{ old('longitude') }}">
                    <input type="hidden" name="accuracy_m" id="presensi-accuracy" value="{{ old('accuracy_m') }}">

                    <div>
                        <label id="presensi-catatan-label" class="mb-1.5 block text-xs font-medium text-gray-700">Catatan (opsional)</label>
                        <textarea id="presensi-catatan" name="catatan" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                            placeholder="Wajib diisi jika presensi di luar area industri.">{{ old('catatan') }}</textarea>
                    </div>

                    @if (!$isPenempatanAktif)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                            Presensi aktif setelah status penempatan diterima industri.
                        </div>
                    @elseif (!$geofenceSet)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                            Titik geofence industri belum diatur admin.
                        </div>
                    @endif

                    <div id="status-presensi-lokasi" class="{{ $statusPresensiClass }}">
                        {{ $statusPresensiText }}
                    </div>

                    <div class="grid grid-cols-1 gap-2">
                        <button id="btn-presensi" type="button"
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            {{ $canTapPresensi ? '' : 'disabled' }}>
                            Presensi Sekarang
                        </button>
                        <button type="button" @click="izinOpen = true"
                            class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                            {{ $canRequestIzin ? '' : 'disabled' }}>
                            Ajukan Izin
                        </button>
                    </div>
                </form>

                <div class="mt-4 border-t border-gray-200 pt-3">
                    <div class="mb-2 text-xs font-medium text-gray-700">Perizinan Terbaru</div>
                    <div class="space-y-2">
                        @forelse ($perizinanLatest as $izin)
                            @php
                                $izinStatusClass = match ($izin->status) {
                                    PerizinanStatus::DISETUJUI->value => 'bg-emerald-100 text-emerald-700',
                                    PerizinanStatus::DITOLAK->value => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <div class="rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-gray-800">{{ $izin->jenis_izin }}</span>
                                    <span class="rounded-full px-2 py-0.5 {{ $izinStatusClass }}">
                                        {{ $perizinanStatusLabels[$izin->status] ?? ucfirst((string) $izin->status) }}
                                    </span>
                                </div>
                                <div class="mt-1 text-gray-500">
                                    {{ $izin->tanggal_mulai?->format('d/m/Y') ?? '-' }} - {{ $izin->tanggal_selesai?->format('d/m/Y') ?? '-' }}
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-500">
                                Belum ada perizinan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-4 shadow-[0_10px_24px_rgba(15,46,36,0.12)] sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Logbook</h2>
                    <p class="mt-1 inline-flex rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs text-teal-700">
                        Tercatat: {{ str_pad((string) $logbookTotal, 2, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
                <button type="button" @click="logbookOpen = true"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-teal-600 text-xl font-semibold text-white hover:bg-teal-700"
                    title="Tambah Logbook">
                    +
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Industri</th>
                            <th class="px-4 py-3">Aktivitas</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Catatan Industri</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logbooks as $logbook)
                            @php
                                $logbookStatusClass = match ($logbook->status_validasi) {
                                    LogbookStatus::DISETUJUI->value => 'bg-emerald-100 text-emerald-700',
                                    LogbookStatus::DITOLAK->value => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-gray-700">{{ $logbook->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $logbook->industri?->nama_industri ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 whitespace-pre-line">{{ $logbook->aktivitas }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs {{ $logbookStatusClass }}">
                                        {{ $logbookStatusLabels[$logbook->status_validasi] ?? ucfirst((string) $logbook->status_validasi) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $logbook->catatan_industri ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                                            @click="editOpen = true; editAction = @js(route('siswa.elogbook.update', $logbook->id)); editTanggal = @js($logbook->tanggal?->format('Y-m-d')); editAktivitas = @js($logbook->aktivitas);">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('siswa.elogbook.destroy', $logbook->id) }}"
                                            onsubmit="return confirm('Hapus logbook ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada logbook tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logbooks->links() }}
            </div>
        </section>

        <template x-teleport="body">
            <div x-show="izinOpen" x-cloak class="fixed inset-0 z-[2000] flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-900/50" @click="izinOpen = false"></div>
                <div class="relative z-10 mx-4 w-full max-w-lg rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Ajukan Perizinan</h3>
                        <button type="button" @click="izinOpen = false" class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600">Tutup</button>
                    </div>

                    <form method="POST" action="{{ route('siswa.perizinan.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Jenis Izin</label>
                            <input type="text" name="jenis_izin" value="{{ old('jenis_izin') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                placeholder="Contoh: Izin Sakit" required>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                    required>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                    required>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                Kirim Pengajuan Izin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="logbookOpen" x-cloak class="fixed inset-0 z-[2000] flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-900/50" @click="logbookOpen = false"></div>
                <div class="relative z-10 mx-4 w-full max-w-xl rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Tambah Logbook</h3>
                        <button type="button" @click="logbookOpen = false" class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600">Tutup</button>
                    </div>

                    <form method="POST" action="{{ route('siswa.elogbook.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                                required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Aktivitas</label>
                            <textarea name="aktivitas" rows="4"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                                placeholder="Tuliskan aktivitas utama hari ini" required>{{ old('aktivitas') }}</textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                                Simpan Logbook
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="editOpen" x-cloak class="fixed inset-0 z-[2000] flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-900/50" @click="editOpen = false"></div>
                <div class="relative z-10 mx-4 w-full max-w-xl rounded-2xl bg-white p-5 shadow-[0_14px_30px_rgba(15,46,36,0.16)]">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Ubah Logbook</h3>
                        <button type="button" @click="editOpen = false" class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600">Tutup</button>
                    </div>

                    <form method="POST" :action="editAction" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="tanggal" x-model="editTanggal"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                                required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Aktivitas</label>
                            <textarea name="aktivitas" rows="4" x-model="editAktivitas"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                                required></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="editOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-siswa-layout>
