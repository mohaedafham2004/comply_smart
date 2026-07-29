@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="dashboardPage()" x-init="initDashboard()">

    {{-- Top Business Greeting & Score Banner --}}
    <div class="glass-card p-6 sm:p-8 rounded-3xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2 z-10">
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 border border-indigo-500/20 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 capitalize"
                    x-text="business?.type ? business.type.replace('_',' ') : 'SME Profile'">
                </span>
                <span class="text-xs text-slate-500 dark:text-zinc-400 font-medium" x-text="'BRN: ' + (business?.registration_no || 'Pending')"></span>
            </div>

            <h1 class="font-hanken text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Welcome back, <span class="brand-gradient" x-text="user?.name || 'Business Owner'"></span>
            </h1>

            <p class="text-xs text-slate-500 dark:text-zinc-400">
                Here is your real-time compliance health and active tracking summary for Sri Lanka.
            </p>
        </div>

        {{-- Live Compliance Gauge --}}
        <div class="glass-card p-4 rounded-2xl flex items-center gap-4 border-indigo-500/30 shrink-0 z-10">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-slate-200 dark:text-zinc-800" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="text-indigo-600 dark:text-white transition-all duration-1000" stroke-dasharray="100" :stroke-dashoffset="100 - (scoreData?.compliance_score || 0)" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <span class="absolute font-extrabold text-sm text-slate-900 dark:text-white" x-text="(scoreData?.compliance_score || 0) + '%'"></span>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-600 dark:text-zinc-300">Compliance Health</div>
                <div class="text-[11px] font-bold mt-0.5"
                    :class="{
                        'text-emerald-600 dark:text-emerald-400': (scoreData?.compliance_score || 0) >= 80,
                        'text-amber-600 dark:text-amber-400': (scoreData?.compliance_score || 0) >= 50 && (scoreData?.compliance_score || 0) < 80,
                        'text-rose-600 dark:text-rose-400': (scoreData?.compliance_score || 0) < 50
                    }"
                    x-text="getHealthStatus(scoreData?.compliance_score)">
                </div>
                <button @click="fetchScore()" class="text-[10px] text-slate-500 hover:text-indigo-600 dark:hover:text-white transition mt-1 block font-medium flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">refresh</span> Recalculate
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Action Launchpad --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="/documents/upload" class="glass-card p-4 rounded-2xl flex items-center gap-3 hover:border-indigo-500/50 dark:hover:border-zinc-600 transition">
            <div class="w-10 h-10 rounded-xl bg-indigo-600/10 text-indigo-600 dark:bg-zinc-800 dark:text-zinc-200 flex items-center justify-center text-base font-bold shrink-0">
                <span class="material-symbols-outlined text-xl">cloud_upload</span>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">Upload Doc</div>
                <div class="text-[10px] text-slate-500 dark:text-zinc-400">Cloudinary & OCR</div>
            </div>
        </a>

        <a href="/tasks" class="glass-card p-4 rounded-2xl flex items-center gap-3 hover:border-emerald-500/50 dark:hover:border-zinc-600 transition">
            <div class="w-10 h-10 rounded-xl bg-emerald-600/10 text-emerald-600 dark:bg-zinc-800 dark:text-zinc-200 flex items-center justify-center text-base font-bold shrink-0">
                <span class="material-symbols-outlined text-xl">add_task</span>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">New Task</div>
                <div class="text-[10px] text-slate-500 dark:text-zinc-400">Track deadlines</div>
            </div>
        </a>

        <a href="/renewals" class="glass-card p-4 rounded-2xl flex items-center gap-3 hover:border-amber-500/50 dark:hover:border-zinc-600 transition">
            <div class="w-10 h-10 rounded-xl bg-amber-600/10 text-amber-600 dark:bg-zinc-800 dark:text-zinc-200 flex items-center justify-center text-base font-bold shrink-0">
                <span class="material-symbols-outlined text-xl">event_repeat</span>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">Renewals</div>
                <div class="text-[10px] text-slate-500 dark:text-zinc-400">EPF/ETF & Permits</div>
            </div>
        </a>

        <a href="/ai" class="glass-card p-4 rounded-2xl flex items-center gap-3 hover:border-purple-500/50 dark:hover:border-zinc-600 transition">
            <div class="w-10 h-10 rounded-xl bg-purple-600/10 text-purple-600 dark:bg-zinc-800 dark:text-zinc-200 flex items-center justify-center text-base font-bold shrink-0">
                <span class="material-symbols-outlined text-xl">smart_toy</span>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">Ask Gemini AI</div>
                <div class="text-[10px] text-slate-500 dark:text-zinc-400">Checklist & Summary</div>
            </div>
        </a>
    </div>

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="glass-card p-5 rounded-2xl space-y-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-medium">
                <span>Vault Documents</span>
                <span class="material-symbols-outlined text-indigo-500 dark:text-zinc-400 text-lg">folder_open</span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white" x-text="overviewData?.documents?.total ?? 0"></div>
            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold" x-text="(overviewData?.documents?.expiring_soon_30 ?? 0) + ' expiring in 30d'"></div>
        </div>

        <div class="glass-card p-5 rounded-2xl space-y-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-medium">
                <span>Active Tasks</span>
                <span class="material-symbols-outlined text-emerald-500 dark:text-zinc-400 text-lg">assignment</span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white" x-text="overviewData?.tasks?.total ?? 0"></div>
            <div class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold" x-text="(overviewData?.tasks?.overdue ?? 0) + ' overdue tasks'"></div>
        </div>

        <div class="glass-card p-5 rounded-2xl space-y-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-medium">
                <span>Upcoming Renewals</span>
                <span class="material-symbols-outlined text-amber-500 dark:text-zinc-400 text-lg">calendar_month</span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white" x-text="overviewData?.renewals?.upcoming_30 ?? 0"></div>
            <div class="text-[11px] text-slate-500 dark:text-zinc-400" x-text="(overviewData?.renewals?.overdue ?? 0) + ' overdue renewals'"></div>
        </div>

        <div class="glass-card p-5 rounded-2xl space-y-1">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-medium">
                <span>Unread Alerts</span>
                <span class="material-symbols-outlined text-rose-500 dark:text-zinc-400 text-lg">notifications</span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white" x-text="unreadCount"></div>
            <a href="/notifications" class="text-[11px] text-indigo-600 dark:text-zinc-300 hover:underline font-semibold block">Notification Center</a>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Left 2-Cols: Recent Documents & Active Tasks --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Recent Vault Documents --}}
            <div class="glass-card p-6 rounded-3xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500 dark:text-zinc-400">description</span>
                        <span>Recent Vault Documents</span>
                    </h3>
                    <a href="/documents" class="text-xs font-semibold text-indigo-600 dark:text-zinc-300 hover:underline">View All →</a>
                </div>

                <template x-if="recentDocs.length === 0">
                    <div class="text-center py-8 text-slate-500 dark:text-zinc-500 text-xs">
                        No documents stored yet. <a href="/documents/upload" class="text-indigo-600 dark:text-zinc-300 underline font-semibold">Upload your first document</a>.
                    </div>
                </template>

                <div class="grid sm:grid-cols-2 gap-3">
                    <template x-for="doc in recentDocs.slice(0, 4)" :key="doc._id">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 flex flex-col justify-between space-y-3">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="doc.title"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30': doc.ocr_status === 'done',
                                            'bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30': doc.ocr_status === 'processing' || doc.ocr_status === 'pending',
                                            'bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30': doc.ocr_status === 'failed',
                                            'bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400': doc.ocr_status === 'skipped',
                                        }"
                                        x-text="doc.ocr_status ? 'OCR: ' + doc.ocr_status : 'Stored'">
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-zinc-400 capitalize" x-text="'Category: ' + (doc.category || 'General')"></div>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-zinc-800 text-[11px]">
                                <span class="text-slate-400 dark:text-zinc-500" x-text="doc.created_at ? new Date(doc.created_at).toLocaleDateString('en-GB') : ''"></span>
                                <a :href="'/documents/details?id=' + doc._id" class="font-bold text-indigo-600 dark:text-zinc-300 hover:underline flex items-center gap-1">
                                    Inspect <span class="material-symbols-outlined text-xs">open_in_new</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Pending Tasks --}}
            <div class="glass-card p-6 rounded-3xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-500 dark:text-zinc-400">task</span>
                        <span>Compliance Action Items</span>
                    </h3>
                    <a href="/tasks" class="text-xs font-semibold text-emerald-600 dark:text-zinc-300 hover:underline">Manage Tasks →</a>
                </div>

                <template x-if="recentTasks.length === 0">
                    <div class="text-center py-8 text-slate-500 dark:text-zinc-500 text-xs">
                        No pending tasks. You're all caught up!
                    </div>
                </template>

                <div class="space-y-2">
                    <template x-for="task in recentTasks.slice(0, 4)" :key="task._id">
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 flex items-center justify-between gap-3">
                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="task.title"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold capitalize"
                                        :class="{
                                            'bg-rose-500/20 text-rose-600 dark:text-rose-300': task.status === 'overdue',
                                            'bg-amber-500/20 text-amber-600 dark:text-amber-300': task.status === 'pending',
                                            'bg-sky-500/20 text-sky-600 dark:text-sky-300': task.status === 'in_progress',
                                            'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300': task.status === 'completed',
                                        }"
                                        x-text="task.status?.replace('_',' ')">
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-zinc-400" x-text="task.due_date ? 'Due: ' + new Date(task.due_date).toLocaleDateString('en-GB') : 'No due date'"></div>
                            </div>

                            <a href="/tasks" class="px-3 py-1.5 rounded-xl bg-slate-200 dark:bg-zinc-800 hover:bg-slate-300 dark:hover:bg-zinc-700 text-xs font-semibold text-slate-800 dark:text-zinc-200 transition shrink-0">
                                Open
                            </a>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- Right 1-Col: Upcoming Renewals & AI Quick Prompt --}}
        <div class="space-y-6">

            {{-- Upcoming Renewals Box --}}
            <div class="glass-card p-6 rounded-3xl space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-500 dark:text-zinc-400">event_repeat</span>
                    <span>Upcoming Renewals (30d)</span>
                </h3>

                <template x-if="upcomingRenewals.length === 0">
                    <div class="text-center py-6 text-slate-500 dark:text-zinc-500 text-xs">
                        No renewals due in the next 30 days.
                    </div>
                </template>

                <div class="space-y-2.5">
                    <template x-for="r in upcomingRenewals.slice(0, 4)" :key="r._id">
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 dark:bg-zinc-900 dark:border-zinc-800 text-xs space-y-1">
                            <div class="flex items-center justify-between text-amber-800 dark:text-zinc-200 font-bold">
                                <span class="truncate" x-text="r.title"></span>
                                <span class="text-[10px] text-amber-600 dark:text-zinc-400 font-mono" x-text="r.due_date ? new Date(r.due_date).toLocaleDateString('en-GB') : ''"></span>
                            </div>
                            <div class="text-[10px] text-amber-700 dark:text-zinc-400 capitalize" x-text="r.renewal_type?.replace('_',' ')"></div>
                        </div>
                    </template>
                </div>

                <a href="/renewals" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-xl text-xs text-center font-bold text-slate-700 dark:text-zinc-200 block transition">
                    View All Renewals
                </a>
            </div>

            {{-- AI Advisor Teaser --}}
            <div class="glass-card p-6 rounded-3xl space-y-4">
                <div class="flex items-center gap-2 text-purple-600 dark:text-zinc-200 font-bold text-sm">
                    <span class="material-symbols-outlined text-purple-500 dark:text-zinc-300">smart_toy</span>
                    <span>ComplySmart AI Chat</span>
                </div>
                <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed">
                    Have a question about EPF contribution percentages, VAT threshold limits, or trade permit steps? Ask Gemini AI directly.
                </p>
                <a href="/ai" class="w-full py-3 bg-purple-600 hover:bg-purple-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs rounded-xl text-center block transition shadow-md">
                    Launch AI Chatbot
                </a>
            </div>

        </div>

    </div>

</div>

@push('scripts')
<script>
function dashboardPage() {
    return {
        scoreData: null,
        overviewData: null,
        recentDocs: [],
        recentTasks: [],
        upcomingRenewals: [],

        async initDashboard() {
            if (this.token) {
                await Promise.all([
                    this.fetchScore(),
                    this.fetchOverview(),
                    this.fetchDocs(),
                    this.fetchTasks(),
                    this.fetchRenewals()
                ]);
            }
        },

        async fetchScore() {
            const res = await api('GET', '/business/compliance-score');
            if (res.ok) this.scoreData = res.data.data;
        },

        async fetchOverview() {
            const res = await api('GET', '/reports/overview');
            if (res.ok) this.overviewData = res.data.data;
        },

        async fetchDocs() {
            const res = await api('GET', '/documents');
            if (res.ok) this.recentDocs = res.data.data || [];
        },

        async fetchTasks() {
            const res = await api('GET', '/tasks');
            if (res.ok) this.recentTasks = res.data.data || [];
        },

        async fetchRenewals() {
            const res = await api('GET', '/renewals/upcoming?days=30');
            if (res.ok) this.upcomingRenewals = res.data.data || [];
        },

        getHealthStatus(score) {
            if (!score) return 'Needs Setup';
            if (score >= 85) return 'Excellent';
            if (score >= 70) return 'Good';
            if (score >= 50) return 'Attention Needed';
            return 'Critical Risks';
        }
    };
}
</script>
@endpush
@endsection
