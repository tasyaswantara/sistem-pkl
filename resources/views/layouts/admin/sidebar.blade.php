<!-- Overlay -->
<div
    x-cloak
    :class="sidebarOpen ? 'block' : 'hidden'"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-20 transition-opacity bg-[#0f2e24] opacity-50 lg:hidden">
</div>

<!-- Sidebar -->
<div
    x-cloak
    :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
    class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform
           bg-gradient-to-b from-[#1a4d3e] to-[#0f2e24]
           lg:translate-x-0 lg:static lg:inset-0">

    <!-- Logo -->
    <div class="flex items-center justify-center mt-8">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden border border-white/20">
                <img
                    src="{{ asset('assets/images/logo serviam.jpg') }}"
                    alt="Logo"
                    class="w-full h-full object-cover">
            </div>
            <span class="text-2xl font-semibold text-white">SISTEM PKL</span>
        </div>
    </div>

    <!-- MENU -->
    <nav class="flex-1 px-3 py-6 space-y-1"
        x-data="{ activeHash: window.location.hash }"
        x-init="window.addEventListener('hashchange', () => activeHash = window.location.hash)">

        @php
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isGuru = $user && $user->hasRole('guru pembimbing');
        $isSiswa = $user && $user->hasRole('siswa');
        $isIndustri = $user && $user->hasRole('perwakilan industri');
        $industriApproved = $isIndustri && $user->industri?->status_pengajuan === 'disetujui';

        $menus = [];

        if ($isAdmin) {
        $menus = [
        [
        'label' => 'Data Pengguna',
        'icon' => 'users',
        'route' => 'admin.data-pengguna',
        'children' => [
        [
        'label' => 'Semua Pengguna',
        'route' => 'admin.data-pengguna',
        'params' => ['role' => 'Semua Pengguna'],
        ],
        [
        'label' => 'Admin',
        'route' => 'admin.data-pengguna',
        'params' => ['role' => 'Admin'],
        ],
        [
        'label' => 'Siswa',
        'route' => 'admin.data-pengguna',
        'params' => ['role' => 'Siswa'],
        ],
        [
        'label' => 'Guru Pembimbing',
        'route' => 'admin.data-pengguna',
        'params' => ['role' => 'Guru Pembimbing'],
        ],
        [
        'label' => 'Perwakilan Industri',
        'route' => 'admin.data-pengguna',
        'params' => ['role' => 'Perwakilan Industri'],
        ],
        ],
        ],
        [
        'label' => 'Penempatan PKL',
        'icon' => 'map',
        'route' => 'admin.penempatan',
        'children' => [
        [
        'label' => 'Konfigurasi Pembobotan',
        'route' => 'admin.penempatan',
        'params' => ['tab' => 'konfigurasi'],
        ],
        [
        'label' => 'Usulan Industri',
        'route' => 'admin.penempatan',
        'params' => ['tab' => 'usulan'],
        ],
        [
        'label' => 'Penempatan Langsung',
        'route' => 'admin.penempatan',
        'params' => ['tab' => 'langsung'],
        ],
        [
        'label' => 'Hasil Penempatan',
        'route' => 'admin.penempatan',
        'params' => ['tab' => 'hasil'],
        ],
        ],
        ],
        [
        'label' => 'Resiko PKL',
        'icon' => 'clipboard',
        'route' => 'admin.risk',
        ],
        [
        'label' => 'E-Logbook',
        'icon' => 'book',
        'route' => 'admin.elogbook',
        ],
        [
        'label' => 'Absensi',
        'icon' => 'map',
        'route' => 'admin.absensi',
        ],
        [
        'label' => 'Perizinan',
        'icon' => 'file',
        'route' => 'admin.perizinan',
        ],
        [
        'label' => 'Penilaian',
        'icon' => 'clipboard',
        'route' => 'admin.penilaian',
        ],
        ];
        }

        if ($isGuru) {
        $menus = array_merge($menus, [
        [
        'label' => 'Data Siswa',
        'icon' => 'users',
        'route' => 'guru.siswa',
        ],
        [
        'label' => 'Resiko PKL',
        'icon' => 'clipboard',
        'route' => 'guru.risk',
        ],
        [
        'label' => 'E-Logbook',
        'icon' => 'book',
        'route' => 'guru.elogbook',
        ],
        [
        'label' => 'Perizinan',
        'icon' => 'file',
        'route' => 'guru.perizinan',
        ],
        [
        'label' => 'Penilaian',
        'icon' => 'clipboard',
        'route' => 'guru.penilaian',
        ],
        ]);
        }

        if ($isSiswa) {
        $menus = array_merge($menus, [
            [
        'label' => 'Berkas Siswa',
        'icon' => 'file',
        'route' => 'siswa.berkas',
        ],
        [
        'label' => 'Penempatan PKL',
        'icon' => 'map',
        'route' => 'siswa.penempatan',
        ],
        [
        'label' => 'E-Logbook',
        'icon' => 'book',
        'route' => 'siswa.elogbook',
        ],
        [
        'label' => 'Absensi',
        'icon' => 'map',
        'route' => 'siswa.absensi',
        ],
        
        [
        'label' => 'Perizinan',
        'icon' => 'file',
        'route' => 'siswa.perizinan',
        ],
        [
        'label' => 'Penilaian',
        'icon' => 'clipboard',
        'route' => 'siswa.penilaian',
        ],
        ]);
        }

        if ($isIndustri) {
        $menus = array_merge($menus, [
        [
        'label' => 'Pengajuan',
        'icon' => 'file',
        'route' => 'industri.pengajuan',
        ],
        ]);
        }

        if ($industriApproved) {
        $menus = array_merge($menus, [
        [
        'label' => 'Data Siswa',
        'icon' => 'users',
        'route' => 'industri.siswa',
        ],
        [
        'label' => 'E-Logbook',
        'icon' => 'book',
        'route' => 'industri.elogbook',
        ],
        [
        'label' => 'Perizinan',
        'icon' => 'file',
        'route' => 'industri.perizinan',
        ],
        [
        'label' => 'Penilaian',
        'icon' => 'clipboard',
        'route' => 'industri.penilaian',
        ],
        ]);
        }
        @endphp

        @foreach ($menus as $menu)
        @php
        $hasChildren = !empty($menu['children']);
        @endphp
        @if ($hasChildren)
        <div x-data="{ open: {{ request()->routeIs($menu['route']) ? 'true' : 'false' }} }">
            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                {{ request()->routeIs($menu['route'])
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/5 hover:text-white' }}">

                {{-- ICON --}}
                @switch($menu['icon'])

                @case('users')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                @break

                @case('map')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                    <circle cx="12" cy="11" r="2.5" />
                </svg>
                @break

                @case('book')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2 4h6a4 4 0 014 4v12a4 4 0 00-4-4H2z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M22 4h-6a4 4 0 00-4 4v12a4 4 0 014-4h6z" />
                </svg>
                @break

                @case('file')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 2h8l5 5v13a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2z" />
                </svg>
                @break

                @case('clipboard')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                </svg>
                @break

                @endswitch

                <span class="flex-1 text-left">{{ $menu['label'] }}</span>
                <svg class="w-4 h-4 text-white/60 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 ml-6 border-l border-white/10 pl-4">
                @foreach ($menu['children'] as $child)
                @php
                $childParams = $child['params'] ?? [];
                $childHash = $child['hash'] ?? null;
                $childUrl = $childParams ? route($child['route'], $childParams) : route($child['route']);
                if ($childHash) {
                $childUrl .= '#' . $childHash;
                }
                $isActiveChild = $child['route'] === 'admin.data-pengguna'
                ? request()->routeIs('admin.data-pengguna') && request('role') === ($childParams['role'] ?? null)
                : ($child['route'] === 'admin.penempatan'
                    ? request()->routeIs('admin.penempatan') && request('tab', 'konfigurasi') === ($childParams['tab'] ?? null)
                    : request()->routeIs($child['route']));
                @endphp
                @if ($childHash)
                <a
                    href="{{ $childUrl }}"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg transition-all text-sm text-white/70 hover:bg-white/5 hover:text-white"
                    :class="activeHash === '#{{ $childHash }}' ? 'bg-white/10 text-white' : ''">
                    <span>{{ $child['label'] }}</span>
                </a>
                @else
                <a
                    href="{{ $childUrl }}"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg transition-all text-sm
                    {{ $isActiveChild ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    <span>{{ $child['label'] }}</span>
                </a>
                @endif
                @endforeach
            </div>
        </div>
        @else
        <a
            href="{{ route($menu['route']) }}"
            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all
            {{ request()->routeIs($menu['route'])
                ? 'bg-white/10 text-white'
                : 'text-white/70 hover:bg-white/5 hover:text-white' }}">

            {{-- ICON --}}
            @switch($menu['icon'])

            @case('users')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            @break

            @case('map')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                <circle cx="12" cy="11" r="2.5" />
            </svg>
            @break

            @case('book')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2 4h6a4 4 0 014 4v12a4 4 0 00-4-4H2z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M22 4h-6a4 4 0 00-4 4v12a4 4 0 014-4h6z" />
            </svg>
            @break

            @case('file')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7 2h8l5 5v13a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2z" />
            </svg>
            @break

            @case('clipboard')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
            </svg>
            @break

            @endswitch

            <span>{{ $menu['label'] }}</span>
        </a>
        @endif
        @endforeach

        <!-- LOGOUT -->
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-white/70 hover:bg-red-500/10 hover:text-white transition-all">

                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 17l5-5-5-5M21 12H9" />
                </svg>

                <span>Logout</span>
            </button>
        </form>

    </nav>
</div>
