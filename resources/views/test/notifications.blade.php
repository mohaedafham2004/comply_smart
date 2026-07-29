@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="notificationsPage()" x-init="init()">

    {{-- Top Header Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>Compliance Notifications</span>
                <template x-if="unreadCount > 0">
                    <span class="ml-2 text-xs px-2.5 py-0.5 bg-rose-600 text-white rounded-full font-bold shadow-sm"
                        x-text="unreadCount + ' unread'">
                    </span>
                </template>
            </h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Automated reminder alerts for Sri Lankan SME compliance deadlines</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="filterUnread = !filterUnread; loadNotifications()"
                class="px-3 py-2 rounded-xl text-xs font-semibold border transition"
                :class="filterUnread
                    ? 'bg-indigo-600 dark:bg-zinc-100 text-white dark:text-black border-indigo-600 font-bold'
                    : 'bg-white dark:bg-zinc-900 border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300'">
                <span x-text="filterUnread ? 'Showing Unread' : 'Unread Only'"></span>
            </button>

            <button @click="markAllRead"
                class="px-3.5 py-2 bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-xl text-xs font-semibold transition"
                x-show="unreadCount > 0">
                Mark All Read
            </button>

            <button @click="triggerReminders"
                class="px-3.5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                title="Runs daily schedule check — creates alerts for upcoming due items">
                <span class="material-symbols-outlined text-base">notifications_active</span>
                <span>Trigger Reminders</span>
            </button>
        </div>
    </div>

    <template x-if="!getToken()">
        <div class="glass-card p-5 rounded-2xl border-l-4 border-l-amber-500 flex items-center gap-3">
            <span class="material-symbols-outlined text-amber-500 text-xl">warning</span>
            <div class="text-xs text-slate-700 dark:text-zinc-300 font-medium">
                Please <a href="/login" class="underline font-bold text-indigo-600 dark:text-indigo-400">Sign in</a> to view notifications.
            </div>
        </div>
    </template>

    {{-- Trigger result alert --}}
    <template x-if="triggerResult">
        <div class="rounded-2xl border p-4 text-xs font-semibold flex items-center gap-2"
            :class="triggerResult.ok
                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300'
                : 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300'">
            <span class="material-symbols-outlined text-base" x-text="triggerResult.ok ? 'check_circle' : 'error'"></span>
            <span x-text="triggerResult.message"></span>
        </div>
    </template>

    <template x-if="getToken()">
        <div class="space-y-3">
            <div x-show="loading" class="text-center py-12 text-slate-500 dark:text-zinc-500 text-xs animate-pulse">
                <i class="fa-solid fa-circle-notch fa-spin text-xl text-indigo-500 block mb-1"></i>
                Loading notification logs...
            </div>

            <template x-if="!loading && notifications.length === 0">
                <div class="glass-card p-12 rounded-3xl text-center space-y-3">
                    <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-zinc-500">notifications_off</span>
                    <h3 class="font-hanken text-base font-bold text-slate-900 dark:text-white">No notifications</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Use the "Trigger Reminders" button to simulate automated daily reminder checks.</p>
                </div>
            </template>

            <div x-show="!loading && notifications.length > 0" class="space-y-3">
                <template x-for="n in notifications" :key="n._id">
                    <div class="glass-card p-4 sm:p-5 rounded-2xl flex items-start justify-between gap-4 transition border-l-4"
                        :class="n.read ? 'border-l-slate-300 dark:border-l-zinc-700' : 'border-l-indigo-600 dark:border-l-white bg-indigo-500/5'">
                        
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-hanken text-sm font-bold text-slate-900 dark:text-white" x-text="n.title"></span>
                                <span x-show="!n.read" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-600 text-white">NEW</span>
                            </div>

                            <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed" x-text="n.message"></p>

                            <div class="text-[10px] text-slate-400 dark:text-zinc-500 pt-1 font-mono" x-text="formatDate(n.created_at)"></div>
                        </div>

                        <button @click="markRead(n._id)" x-show="!n.read" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-xs font-semibold text-slate-700 dark:text-zinc-200 transition shrink-0">
                            Mark Read
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function notificationsPage() {
    return {
        notifications: [],
        unreadCount: 0,
        loading: false,
        filterUnread: false,
        triggerResult: null,

        init() {
            if (this.getToken()) {
                this.loadNotifications();
            }
        },

        getToken() {
            return localStorage.getItem('cs_token');
        },

        async loadNotifications() {
            this.loading = true;
            const url = '/notifications' + (this.filterUnread ? '?unread_only=1' : '');
            const res = await api('GET', url);
            this.loading = false;

            if (res.ok) {
                this.notifications = res.data.data || [];
                this.unreadCount = res.data.meta?.unread ?? this.notifications.filter(n => !n.read).length;
            }
        },

        async markRead(id) {
            const res = await api('PATCH', `/notifications/${id}/read`);
            if (res.ok) {
                await this.loadNotifications();
            }
        },

        async markAllRead() {
            const unread = this.notifications.filter(n => !n.read);
            await Promise.all(unread.map(n => api('PATCH', `/notifications/${n._id}/read`)));
            await this.loadNotifications();
        },

        async triggerReminders() {
            this.triggerResult = null;
            const res = await api('POST', '/notifications/trigger-reminders');
            if (res.ok) {
                this.triggerResult = { ok: true, message: res.data.message || 'Daily reminders generated!' };
                await this.loadNotifications();
            } else {
                this.triggerResult = { ok: false, message: res.data.message || 'Failed to trigger reminders.' };
            }
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleString('en-GB');
        }
    };
}
</script>
@endpush
@endsection
