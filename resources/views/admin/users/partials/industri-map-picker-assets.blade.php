@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const section = document.getElementById('industri-map-picker-section');
        if (!section) {
            return;
        }

        const mapElement = document.getElementById('industri-location-map');
        const latInput = document.getElementById('industri-latitude');
        const lngInput = document.getElementById('industri-longitude');
        const searchInput = document.getElementById('industri-location-search');
        const searchButton = document.getElementById('industri-location-search-btn');
        const resultBox = document.getElementById('industri-location-search-results');
        const roleSelect = document.querySelector('select[name="role"]');
        const hasLeaflet = typeof L !== 'undefined';

        let initialized = false;
        let map = null;
        let marker = null;

        function renderResults(items) {
            if (!resultBox) {
                return;
            }
            if (!Array.isArray(items) || items.length === 0) {
                resultBox.innerHTML = '<div class="text-xs text-gray-500 px-3 py-2">Lokasi tidak ditemukan.</div>';
                return;
            }

            resultBox.innerHTML = '';
            items.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-50'
                    + (index !== 0 ? ' border-t border-gray-100' : '');
                button.dataset.lat = String(item.lat ?? '');
                button.dataset.lng = String(item.lon ?? '');
                button.textContent = String(item.display_name ?? '-');
                resultBox.appendChild(button);
            });
        }

        function setPoint(lat, lng, zoom = 16) {
            if (!map || !marker || !Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            marker.setLatLng([lat, lng]);
            if (latInput) {
                latInput.value = Number(lat).toFixed(7);
            }
            if (lngInput) {
                lngInput.value = Number(lng).toFixed(7);
            }
            map.setView([lat, lng], zoom);
        }

        async function searchLocation() {
            if (!searchInput || !searchButton || !resultBox) {
                return;
            }

            const keyword = searchInput.value.trim();
            if (keyword === '') {
                resultBox.innerHTML = '<div class="text-xs text-gray-500 px-3 py-2">Masukkan kata kunci lokasi terlebih dahulu.</div>';
                return;
            }

            searchButton.disabled = true;
            resultBox.innerHTML = '<div class="text-xs text-gray-500 px-3 py-2">Mencari lokasi...</div>';

            try {
                const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&countrycodes=id&q='
                    + encodeURIComponent(keyword);
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const items = await response.json();
                renderResults(items);
            } catch (error) {
                resultBox.innerHTML = '<div class="text-xs text-red-600 px-3 py-2">Gagal mencari lokasi. Coba lagi.</div>';
            } finally {
                searchButton.disabled = false;
            }
        }

        function initializeMap() {
            if (initialized) {
                if (map) {
                    setTimeout(() => map.invalidateSize(), 150);
                }
                return;
            }
            initialized = true;

            if (!mapElement || !latInput || !lngInput) {
                return;
            }

            if (!hasLeaflet) {
                mapElement.innerHTML = '<div class="h-full flex items-center justify-center px-4 text-sm text-amber-700 bg-amber-50 rounded-lg">Leaflet gagal dimuat. Cek koneksi internet/CDN.</div>';
                return;
            }

            const defaultLat = -7.983908;
            const defaultLng = 112.621391;
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            const startLat = Number.isFinite(lat) ? lat : defaultLat;
            const startLng = Number.isFinite(lng) ? lng : defaultLng;
            const startZoom = (Number.isFinite(lat) && Number.isFinite(lng)) ? 16 : 11;

            map = L.map('industri-location-map').setView([startLat, startLng], startZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
            setPoint(startLat, startLng, startZoom);

            map.on('click', (event) => {
                setPoint(event.latlng.lat, event.latlng.lng);
            });

            marker.on('dragend', () => {
                const pos = marker.getLatLng();
                setPoint(pos.lat, pos.lng, map.getZoom());
            });

            setTimeout(() => map.invalidateSize(), 180);
        }

        if (searchButton) {
            searchButton.addEventListener('click', searchLocation);
        }
        if (searchInput) {
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchLocation();
                }
            });
        }
        if (resultBox) {
            resultBox.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-lat][data-lng]');
                if (!button) {
                    return;
                }

                const lat = Number(button.dataset.lat);
                const lng = Number(button.dataset.lng);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPoint(lat, lng);
                }
            });
        }

        if (roleSelect) {
            const maybeInit = () => {
                if (roleSelect.value === 'perwakilan industri') {
                    initializeMap();
                }
            };

            roleSelect.addEventListener('change', () => {
                setTimeout(maybeInit, 150);
            });
            setTimeout(maybeInit, 150);
            return;
        }

        initializeMap();
    });
</script>
@endpush
