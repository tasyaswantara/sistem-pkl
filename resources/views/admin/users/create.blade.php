@include('admin.users.partials.industri-map-picker-assets')

<x-admin-layout>
    <div x-data="{
        role: '{{ old('role', $prefillRole ?? '') }}',
        showPassword: false,
        kelas: '{{ old('kelas') }}',
        perangkatItems: [],
        perangkatScore: 1
    }"
        x-effect="perangkatScore = perangkatItems.length === 0 ? 1 : (perangkatItems.length === 3 ? 5 : 1 + perangkatItems.length)">

        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.data-pengguna') }}"
                class="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
                ← Kembali ke Data Pengguna
            </a>

            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Tambah Pengguna</h1>
            <p class="text-gray-500 text-sm">
                Tambahkan pengguna baru ke dalam Sistem PKL
            </p>
        </div>

        {{-- Error --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            {{-- DATA UMUM --}}
            <div class="bg-white border rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Data Umum</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                        <input name="name" value="{{ old('name') }}" required placeholder="Nama Lengkap"
                            class="input-text">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email"
                            class="input-text">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                        <input type="password" name="password" required placeholder="Password" class="input-text">
                    </div>
                </div>
            </div>

            {{-- ROLE --}}
            <div class="bg-white border rounded-lg p-6 mb-6">
                <label class="block text-sm font-medium mb-2">Role</label>
                <select name="role" x-model="role" required class="input-text w-1/2">
                    <option value="">-- Pilih Role --</option>
                    <option value="siswa">Siswa</option>
                    <option value="guru pembimbing">Guru Pembimbing</option>
                    <option value="perwakilan industri">Perwakilan Industri</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            {{-- SISWA --}}
            <div x-show="role === 'siswa'" class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Data Siswa</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">NIS</label>
                        <input name="nis" value="{{ old('nis') }}" placeholder="NIS" class="input-text">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jurusan</label>
                        <select name="jurusan_id" class="input-text" :disabled="role !== 'siswa'"
                            x-init="if (role === 'siswa' && $el.selectedOptions.length) { const name = $el.selectedOptions[0].dataset.name;
                                kelas = name ? 'XII ' + name : ''; }"
                            @change="const name = $event.target.selectedOptions[0].dataset.name; kelas = name ? 'XII ' + name : '';">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" data-name="{{ $j->nama }}"
                                    {{ old('jurusan_id') == $j->id ? 'selected' : '' }}>
                                    {{ $j->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kelas</label>
                        <input name="kelas" x-model="kelas" placeholder="Kelas (otomatis)" class="input-text"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rata-rata Nilai Kejuruan</label>
                        <input name="nilai_akademik" type="number" value="{{ old('nilai_akademik') }}"
                            placeholder="Rata-rata Nilai Kejuruan" class="input-text">
                    </div>
                    <div class="bg-white border rounded-lg p-3 md:col-span-2">
                        <p class="text-sm font-medium text-gray-700 mb-2">Perangkat (skala 1-5)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-700">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="form-checkbox" value="laptop" x-model="perangkatItems">
                                Laptop
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="form-checkbox" value="kamera" x-model="perangkatItems">
                                Kamera
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="form-checkbox" value="hp" x-model="perangkatItems">
                                HP
                            </label>
                        </div>
                        <input type="hidden" name="perangkat" x-model="perangkatScore">
                        <p class="text-xs text-gray-500 mt-2">
                            Skor tersimpan: <span class="font-semibold" x-text="perangkatScore"></span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status PKL</label>
                        <select name="status_pkl" class="input-text">
                            <option value="">-- Status PKL --</option>
                            <option value="{{ \App\Enums\StatusPKL::BELUM->value }}"
                                {{ old('status_pkl') == \App\Enums\StatusPKL::BELUM->value ? 'selected' : '' }}>Belum
                            </option>
                            <option value="{{ \App\Enums\StatusPKL::BERJALAN->value }}"
                                {{ old('status_pkl') == \App\Enums\StatusPKL::BERJALAN->value ? 'selected' : '' }}>
                                Berjalan</option>
                            <option value="{{ \App\Enums\StatusPKL::SELESAI->value }}"
                                {{ old('status_pkl') == \App\Enums\StatusPKL::SELESAI->value ? 'selected' : '' }}>
                                Selesai</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun Ajaran</label>
                        <input name="tahun_ajaran" value="{{ old('tahun_ajaran') }}" placeholder="Tahun Ajaran"
                            class="input-text">
                    </div>
                </div>
            </div>

            {{-- GURU --}}
            <div x-show="role === 'guru pembimbing'"
                class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Data Guru Pembimbing</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">NIP</label>
                        <input name="nip" value="{{ old('nip') }}" placeholder="NIP" class="input-text">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jurusan</label>
                        <select name="jurusan_id" class="input-text" :disabled="role !== 'guru pembimbing'">
                            <option value="">-- Jurusan --</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}"
                                    {{ old('jurusan_id') == $j->id ? 'selected' : '' }}>
                                    {{ $j->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- INDUSTRI --}}
            <div x-show="role === 'perwakilan industri'"
                class="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Data Industri</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Perusahaan</label>
                        <input name="nama_industri" value="{{ old('nama_industri') }}" placeholder="Nama Perusahaan"
                            class="input-text">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kapasitas</label>
                        <input name="kapasitas" type="number" value="{{ old('kapasitas') }}"
                            placeholder="Kapasitas" class="input-text">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Grade</label>
                        <select name="grade" class="input-text" :disabled="role !== 'perwakilan industri'">
                            <option value="">-- Pilih Grade --</option>
                            @foreach (['A', 'B', 'C'] as $g)
                                <option value="{{ $g }}" {{ old('grade') == $g ? 'selected' : '' }}>
                                    Grade {{ $g }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jurusan</label>
                        <select name="jurusan_id" class="input-text" :disabled="role !== 'perwakilan industri'">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}"
                                    {{ old('jurusan_id') == $j->id ? 'selected' : '' }}>
                                    {{ $j->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alamat</label>
                        <textarea name="alamat" placeholder="Alamat" class="input-text md:col-span-2">{{ old('alamat') }}</textarea>
                    </div>
                    <div id="industri-map-picker-section"
                        class="md:col-span-2 rounded-lg border border-orange-200 bg-white p-4">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Pilih Titik Industri di
                            Peta</label>
                        <div class="flex flex-col md:flex-row gap-2">
                            <input id="industri-location-search" type="text" class="input-text flex-1"
                                placeholder="Cari area: nama jalan, kecamatan, kota, atau tempat">
                            <button id="industri-location-search-btn" type="button"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                                Cari Lokasi
                            </button>
                        </div>
                        <div id="industri-location-search-results"
                            class="mt-2 rounded-lg border border-gray-200 bg-white max-h-44 overflow-auto"></div>
                        <div id="industri-location-map"
                            class="w-full h-[360px] rounded-lg border border-gray-200 mt-3"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
                                <input id="industri-latitude" name="latitude" type="number" step="0.0000001"
                                    value="{{ old('latitude') }}" class="input-text">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                                <input id="industri-longitude" name="longitude" type="number" step="0.0000001"
                                    value="{{ old('longitude') }}" class="input-text">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Radius Geofence (m)</label>
                                <input name="geofence_radius_m" type="number" min="20" max="5000"
                                    value="{{ old('geofence_radius_m', 200) }}" class="input-text">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Klik peta atau drag marker untuk mengubah titik secara
                            presisi.</p>
                    </div>
                </div>
            </div>

            {{-- ACTION --}}
            <div class="flex gap-3">
                <button class="px-6 py-2 bg-emerald-600 text-white rounded-lg">
                    Simpan Pengguna
                </button>
                <a href="{{ route('admin.data-pengguna') }}" class="px-6 py-2 bg-white border rounded-lg">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
