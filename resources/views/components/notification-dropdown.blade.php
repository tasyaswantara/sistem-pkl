@php
    $roleName = auth()->user()?->roles->first()?->name ?? 'pengguna';
@endphp

<div x-data="notificationDropdown()" class="relative cursor-pointer">
    <button @click="toggleNotifications()"
        class="relative flex text-gray-600 focus:outline-none">
        <svg class="h-6 w-6 cursor-pointer" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span x-show="unreadCount > 0"
            class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] text-white">
            <span x-text="unreadCount"></span>
        </span>
    </button>

    <div x-cloak x-show="notificationOpen" @click="notificationOpen = false" class="fixed inset-0 z-10 h-full w-full"></div>

    <div x-cloak x-show="notificationOpen"
        class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="border-b px-4 py-3">
            <div class="text-sm font-semibold text-gray-900">Notifikasi</div>
            <div class="text-xs text-gray-500">Terbaru untuk {{ $roleName }}</div>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-500">Belum ada notifikasi.</div>
            </template>

            <template x-for="item in notifications" :key="item.id">
                <a :href="item.url || '#'"
                    class="block border-b px-4 py-3 last:border-b-0 hover:bg-gray-50"
                    :class="item.is_unread ? 'bg-emerald-50/60' : 'bg-white'">
                    <div class="text-sm font-semibold text-gray-900" x-text="item.title"></div>
                    <div class="mt-1 text-xs text-gray-600" x-text="item.body"></div>
                    <div class="mt-1 text-[11px] text-gray-400" x-text="item.created_at"></div>
                </a>
            </template>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function notificationDropdown() {
                return {
                    notificationOpen: false,
                    notifications: [],
                    unreadCount: 0,
                    poller: null,
                    async fetchNotifications() {
                        const response = await fetch(@js(route('notifications.index')), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) return;
                        const data = await response.json();
                        this.notifications = data.items || [];
                        this.unreadCount = data.unread_count || 0;
                    },
                    async markAllAsRead() {
                        if (this.unreadCount < 1) return;
                        await fetch(@js(route('notifications.read-all')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        this.notifications = this.notifications.map(item => ({ ...item, is_unread: false }));
                        this.unreadCount = 0;
                    },
                    async toggleNotifications() {
                        this.notificationOpen = !this.notificationOpen;
                        if (this.notificationOpen) {
                            await this.fetchNotifications();
                            await this.markAllAsRead();
                            this.startPolling();
                            return;
                        }
                        this.stopPolling();
                    },
                    startPolling() {
                        this.stopPolling();
                        this.poller = setInterval(() => this.fetchNotifications(), 30000);
                    },
                    stopPolling() {
                        if (this.poller) clearInterval(this.poller);
                        this.poller = null;
                    },
                    init() {
                        this.fetchNotifications();
                        this.startPolling();
                    }
                };
            }
        </script>
    @endpush
@endonce
