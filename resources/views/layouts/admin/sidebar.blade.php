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
    <nav class="flex-1 px-3 py-6 space-y-1">

        @php
        $menus = [
        [
        'label' => 'Data Pengguna',
        'icon' => 'users',
        'route' => 'admin.data-pengguna',
        ],
        [
        'label' => 'Penempatan Siswa',
        'icon' => 'map',
        'route' => 'admin.penempatan',
        ],
        [
        'label' => 'E-Logbook',
        'icon' => 'book',
        'route' => 'admin.elogbook',
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
        @endphp

        @foreach ($menus as $menu)
        <a
            href="{{ route($menu['route']) }}"
            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all
            {{ request()->routeIs($menu['route'])
                ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border border-emerald-400/30 text-white'
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
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.553-.832L9 7l6-3 5.447 2.724A1 1 0 0121 7.618v10.764a1 1 0 01-1.553.832L15 17l-6 3z" />
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