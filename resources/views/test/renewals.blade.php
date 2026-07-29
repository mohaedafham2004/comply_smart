@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="renewalsPage()" x-init="init()">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">License & Permit Renewal Tracker</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Track EPF/ETF returns, trade permits, and BRN renewal deadlines for Sri Lankan SMEs</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadUpcoming"
                class="px-3.5 py-2.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">event_upcoming</span>
                <span>Upcoming (30 Days)</span>
            </button>
            <button @click="showCreate = !showCreate"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">add</span>
                <span>New Renewal</span>
            </button>
        </div>
    </div>

    <template x-if="!getToken()">
        <div class="glass-card p-5 rounded-2xl border-l-4 border-l-amber-500 flex items-center gap-3">
            <span class="material-symbols-outlined text-amber-500 text-xl">warning</span>
            <div class="text-xs text-slate-700 dark:text-zinc-300 font-medium">
                Please <a href="/login" class="underline font-bold text-indigo-600 dark:text-indigo-400">Sign in</a> to manage business renewals.
            </div>
        </div>
    </template>

    <template x-if="getToken()">
        <div class="space-y-6">

            {{-- 30-Day Upcoming Renewals Alert Banner --}}
            <template x-if="upcomingRenewals.length > 0">
                <div class="glass-card p-5 rounded-3xl border-l-4 border-l-amber-500 space-y-3 bg-amber-500/5">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">notification_important</span>
                            <span x-text="upcomingRenewals.length + ' renewal(s) due in the next 30 days'"></span>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400 tracking-wider">Action Required</span>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <template x-for="r in upcomingRenewals" :key="r._id">
                            <div class="p-3 rounded-2xl bg-white/80 dark:bg-zinc-900 border border-amber-500/20 flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-900 dark:text-white truncate" x-text="r.title"></span>
                                <span class="text-amber-600 dark:text-amber-400 font-mono text-[11px] shrink-0 ml-2" x-text="formatDate(r.due_date)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Create Form Dropdown --}}
            <div x-show="showCreate" x-transition class="glass-card p-6 sm:p-8 rounded-3xl space-y-4">
                <h2 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                    <span class="material-symbols-outlined text-indigo-600 dark:text-zinc-400">add_task</span>
                    <span>Create New Renewal Record</span>
                </h2>
                
                <form @submit.prevent="createRenewal" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Renewal Title *</label>
                            <input id="renewal-title" x-model="form.title" type="text" placeholder="e.g. Municipal Trade License Renewal 2025" 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition" required>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Renewal Type *</label>
                            <select x-model="form.renewal_type" required 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                                <option value="business_registration">Business Registration</option>
                                <option value="trade_license">Trade License</option>
                                <option value="tax_return">Tax / VAT Filing</option>
                                <option value="epf_etf">EPF / ETF Monthly Return</option>
                                <option value="environmental_permit">Environmental Permit</option>
                                <option value="fire_safety">Fire Safety Certificate</option>
                                <option value="other">Other Permit</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Due Date *</label>
                            <input x-model="form.due_date" type="date" 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition" required>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800">Cancel</button>
                        <button type="submit" id="btn-submit-renewal" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition" :disabled="creating">
                            <span x-show="!creating">Save Renewal</span>
                            <span x-show="creating">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Renewals Bento Card Grid --}}
            <div x-show="loading" class="text-center py-12 text-slate-500 dark:text-zinc-500 text-xs animate-pulse">
                <i class="fa-solid fa-circle-notch fa-spin text-xl text-indigo-500 block mb-1"></i>
                Loading renewal records...
            </div>

            <template x-if="!loading && renewals.length === 0">
                <div class="glass-card p-12 rounded-3xl text-center space-y-3">
                    <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-zinc-500">event_repeat</span>
                    <h3 class="font-hanken text-base font-bold text-slate-900 dark:text-white">No renewal items tracked</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Add upcoming license or tax deadlines to receive automated email notifications.</p>
                </div>
            </template>

            <div x-show="!loading && renewals.length > 0" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="r in renewals" :key="r._id">
                    <div class="glass-card p-5 rounded-3xl flex flex-col justify-between space-y-3 hover:border-indigo-500/40 dark:hover:border-zinc-700 transition border-l-4"
                        :class="isDueSoon(r.due_date) ? 'border-l-amber-500' : 'border-l-indigo-600 dark:border-l-zinc-500'">
                        
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-hanken text-sm font-bold text-slate-900 dark:text-white truncate" x-text="r.title"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0"
                                    :class="isDueSoon(r.due_date) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30' : 'bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300'"
                                    x-text="r.renewal_type ? r.renewal_type.replace('_',' ') : 'General'">
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
                                <span class="material-symbols-outlined text-sm">calendar_month</span>
                                <span x-text="'Due: ' + formatDate(r.due_date)"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-zinc-800 text-xs">
                            <span class="text-[11px] font-semibold"
                                :class="isDueSoon(r.due_date) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-zinc-500'"
                                x-text="getDaysRemainingText(r.due_date)">
                            </span>

                            <button @click="deleteRenewal(r._id)" title="Delete Renewal" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-zinc-800 transition">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </template>
</div>

@push('scripts')
<script>
function renewalsPage() {
    return {
        renewals: [],
        upcomingRenewals: [],
        loading: false,
        showCreate: false,
        creating: false,

        form: {
            title: '',
            renewal_type: 'business_registration',
            due_date: ''
        },

        init() {
            if (this.getToken()) {
                this.loadRenewals();
            }
        },

        getToken() {
            return localStorage.getItem('cs_token');
        },

        async loadRenewals() {
            this.loading = true;
            const res = await api('GET', '/renewals');
            this.loading = false;
            if (res.ok) {
                this.renewals = res.data.data || [];
            }
        },

        async loadUpcoming() {
            const res = await api('GET', '/renewals/upcoming?days=30');
            if (res.ok) {
                this.upcomingRenewals = res.data.data || [];
            }
        },

        async createRenewal() {
            this.creating = true;
            const res = await api('POST', '/renewals', this.form);
            this.creating = false;

            if (res.ok) {
                this.showCreate = false;
                this.form = { title: '', renewal_type: 'business_registration', due_date: '' };
                await this.loadRenewals();
                await this.loadUpcoming();
            } else {
                alert(res.data.message || 'Failed to create renewal.');
            }
        },

        async deleteRenewal(id) {
            if (!confirm('Are you sure you want to delete this renewal record?')) return;
            const res = await api('DELETE', `/renewals/${id}`);
            if (res.ok) {
                await this.loadRenewals();
                await this.loadUpcoming();
            } else {
                alert(res.data.message || 'Failed to delete renewal.');
            }
        },

        formatDate(d) {
            if (!d) return 'N/A';
            return new Date(d).toLocaleDateString('en-GB');
        },

        isDueSoon(dueDate) {
            if (!dueDate) return false;
            const diff = new Date(dueDate) - new Date();
            const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
            return days >= 0 && days <= 30;
        },

        getDaysRemainingText(dueDate) {
            if (!dueDate) return '';
            const diff = new Date(dueDate) - new Date();
            const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
            if (days < 0) return `${Math.abs(days)} days overdue`;
            if (days === 0) return 'Due today';
            return `${days} days remaining`;
        }
    };
}
</script>
@endpush
@endsection
