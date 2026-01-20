<header class="relative z-40 flex items-center justify-between px-6 py-4  bg-white/70 backdrop-blur-lg border-b border-gray-200/50 shadow-sm">
    <div class="flex items-center">
        <button @click="sidebarOpen = true" class="text-gray-500 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <div class="relative mx-4 lg:mx-0"></div>
    </div>

    <div class="flex items-center">
        <div x-data="{
                notificationOpen: false,
                notifications: [],
                unreadCount: 0,
                poller: null,
                async fetchNotifications() {
                    const response = await fetch('{{ route('admin.notifications') }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    this.notifications = data.items || [];
                    this.unreadCount = data.unread_count || 0;
                },
                startPolling() {
                    this.fetchNotifications();
                    this.poller = setInterval(() => this.fetchNotifications(), 30000);
                },
                stopPolling() {
                    if (this.poller) clearInterval(this.poller);
                    this.poller = null;
                }
            }" class="relative">
            <button @click="notificationOpen = ! notificationOpen; notificationOpen ? startPolling() : stopPolling()"
                class="relative flex mx-4 text-gray-600 focus:outline-none">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span x-show="unreadCount > 0"
                    class="absolute -top-1 -right-1 min-w-[18px] h-[18px] text-[10px] px-1 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                    <span x-text="unreadCount"></span>
                </span>
            </button>

            <div x-cloak x-show="notificationOpen" @click="notificationOpen = false" class="fixed inset-0 z-10 w-full h-full"></div>

            <div x-cloak x-show="notificationOpen" class="absolute right-0 z-50 mt-2 overflow-hidden bg-white rounded-lg shadow-xl w-80">
                <div class="px-4 py-3 border-b">
                    <div class="text-sm font-semibold text-gray-900">Notifikasi</div>
                    <div class="text-xs text-gray-500">Terbaru untuk admin</div>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <template x-if="notifications.length === 0">
                        <div class="px-4 py-6 text-sm text-gray-500 text-center">Belum ada notifikasi.</div>
                    </template>
                    <template x-for="item in notifications" :key="item.id">
                        <div class="px-4 py-3 border-b last:border-b-0">
                            <div class="text-sm font-semibold text-gray-900" x-text="item.title"></div>
                            <div class="text-xs text-gray-600 mt-1" x-text="item.body"></div>
                            <div class="text-[11px] text-gray-400 mt-1" x-text="item.created_at"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = ! dropdownOpen" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                {{-- <img class="object-cover w-full h-full" src="https://images.unsplash.com/photo-1528892952291-009c663ce843?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=296&q=80" alt="Your avatar"> --}}
                {{-- <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Layer_2" data-name="Layer 2"> <g id="invisible_box" data-name="invisible box"> <rect width="48" height="48" fill="none"></rect> </g> <g id="Layer_7" data-name="Layer 7"> <g> <path d="M25.1,41H4a2,2,0,0,1-2-2V31.1l1-.6A25.6,25.6,0,0,1,16,27a26.7,26.7,0,0,1,7.5,1.1,21.2,21.2,0,0,0-.5,4.4A18.4,18.4,0,0,0,25.1,41Z"></path> <path d="M16,23a9,9,0,1,0-9-9A9,9,0,0,0,16,23Z"></path> <path d="M46,34.1V31.9L42.4,31l-.5-1.1,2-3.2-1.6-1.6-3.2,2L38,26.6,37.1,23H34.9L34,26.6l-1.1.5-3.2-2-1.6,1.6,2,3.2L29.6,31l-3.6.9v2.2l3.6.9.5,1.1-2,3.2,1.6,1.6,3.2-2,1.1.5.9,3.6h2.2l.9-3.6,1.1-.5,3.2,2,1.6-1.6-2-3.2.5-1.1ZM36,36a3,3,0,1,1,3-3A2.9,2.9,0,0,1,36,36Z"></path> </g> </g> </g> </g></svg> --}}
                {{-- <div>{{ Auth::user()->name }}
        </div> --}}
                <span>{{ Auth::user()->roles->first()->name ?? 'user' }}</span>
                <svg class="w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-cloak x-show="dropdownOpen" @click="dropdownOpen = false" class="fixed inset-0 z-10 w-full h-full"></div>

            <div x-cloak x-show="dropdownOpen" class="absolute right-0 z-10 w-56 mt-2 overflow-hidden bg-white rounded-md shadow-xl border border-gray-200">
                <div class="px-4 py-3">
                    <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ Auth::user()->roles->first()->name ?? 'user' }}</div>
                </div>
            </div>
        </div>

    </div>
</header>
