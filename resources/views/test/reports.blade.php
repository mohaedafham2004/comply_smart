@extends('layouts.app')

@section('content')
<div x-data="reportsPage()" x-init="init()" class="space-y-6">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600 dark:text-zinc-400 text-3xl">analytics</span>
                <span>Compliance Analytics & Reports</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Executive metrics, document vault counts, task completion trends, and risk summaries</p>
        </div>
        <button @click="loadOverview" class="px-3.5 py-2 bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-xs font-semibold text-slate-800 dark:text-zinc-200 rounded-xl transition flex items-center gap-1.5 self-start">
            <span class="material-symbols-outlined text-base">refresh</span>
            <span>Refresh Metrics</span>
        </button>
    </div>

    <template x-if="!getToken()">
        <div class="glass-card p-5 rounded-2xl border-l-4 border-l-amber-500 flex items-center gap-3">
            <span class="material-symbols-outlined text-amber-500 text-xl">warning</span>
            <div class="text-xs text-slate-700 dark:text-zinc-300 font-medium">
                Please <a href="/login" class="underline font-bold text-indigo-600 dark:text-indigo-400">Sign in</a> to view executive reports.
            </div>
        </div>
    </template>

    <template x-if="getToken()">
        <div class="space-y-6">

            <div x-show="loading" class="text-center py-12 text-slate-500 dark:text-zinc-500 text-xs animate-pulse">
                <i class="fa-solid fa-circle-notch fa-spin text-xl text-indigo-500 block mb-1"></i>
                Loading analytics report...
            </div>

            <template x-if="!loading && data">
                <div class="space-y-6">

                    {{-- Top KPI Bento Cards --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="glass-card p-5 rounded-3xl space-y-2 border-l-4 border-l-indigo-600">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-zinc-400">
                                <span>Compliance Score</span>
                                <span class="material-symbols-outlined text-indigo-500 text-lg">donut_large</span>
                            </div>
                            <div class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="(data.business?.compliance_score || 0) + '%'"></div>
                            <div class="text-[11px] text-indigo-600 dark:text-zinc-400 font-medium truncate" x-text="data.business?.name"></div>
                        </div>

                        <div class="glass-card p-5 rounded-3xl space-y-2 border-l-4 border-l-emerald-600">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-zinc-400">
                                <span>Task Completion</span>
                                <span class="material-symbols-outlined text-emerald-500 text-lg">task_alt</span>
                            </div>
                            <div class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="(data.compliance_score_trend?.task_completion_rate || 0) + '%'"></div>
                            <div class="text-[11px] text-slate-500 dark:text-zinc-400" x-text="(data.tasks?.completed || 0) + ' of ' + (data.tasks?.total || 0) + ' tasks done'"></div>
                        </div>

                        <div class="glass-card p-5 rounded-3xl space-y-2 border-l-4 border-l-sky-600">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-zinc-400">
                                <span>Renewal Rate</span>
                                <span class="material-symbols-outlined text-sky-500 text-lg">event_repeat</span>
                            </div>
                            <div class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="(data.compliance_score_trend?.renewal_completion_rate || 0) + '%'"></div>
                            <div class="text-[11px] text-slate-500 dark:text-zinc-400" x-text="(data.renewals?.completed || 0) + ' of ' + (data.renewals?.total || 0) + ' renewals done'"></div>
                        </div>

                        <div class="glass-card p-5 rounded-3xl space-y-2 border-l-4 border-l-rose-600">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-zinc-400">
                                <span>Overdue Risks</span>
                                <span class="material-symbols-outlined text-rose-500 text-lg">warning</span>
                            </div>
                            <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400" x-text="(data.tasks?.overdue || 0) + (data.renewals?.overdue || 0)"></div>
                            <div class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold" x-text="(data.tasks?.overdue || 0) + ' tasks, ' + (data.renewals?.overdue || 0) + ' renewals'"></div>
                        </div>
                    </div>

                    {{-- Breakdown Sections Grid --}}
                    <div class="grid md:grid-cols-3 gap-6">

                        {{-- Tasks Breakdown --}}
                        <div class="glass-card p-6 rounded-3xl space-y-4">
                            <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                                <span class="material-symbols-outlined text-indigo-500">assignment</span>
                                <span>Tasks Status Breakdown</span>
                            </h3>

                            <div class="space-y-3 text-xs">
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Completed</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="data.tasks?.completed || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">In Progress</span>
                                    <span class="font-bold text-sky-600 dark:text-sky-400" x-text="data.tasks?.in_progress || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Pending</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="data.tasks?.pending || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Overdue</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="data.tasks?.overdue || 0"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Documents Breakdown --}}
                        <div class="glass-card p-6 rounded-3xl space-y-4">
                            <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                                <span class="material-symbols-outlined text-indigo-500">description</span>
                                <span>Document Vault Metrics</span>
                            </h3>

                            <div class="space-y-3 text-xs">
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Total Documents</span>
                                    <span class="font-bold text-slate-900 dark:text-white" x-text="data.documents?.total || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">OCR Processed</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="data.documents?.ocr_done || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Expiring in 30 Days</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="data.documents?.expiring_soon_30 || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Expired Documents</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="data.documents?.expired || 0"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Renewals Breakdown --}}
                        <div class="glass-card p-6 rounded-3xl space-y-4">
                            <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                                <span class="material-symbols-outlined text-indigo-500">event_repeat</span>
                                <span>Renewals Metrics</span>
                            </h3>

                            <div class="space-y-3 text-xs">
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Total Tracked</span>
                                    <span class="font-bold text-slate-900 dark:text-white" x-text="data.renewals?.total || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Due in 30 Days</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="data.renewals?.upcoming_30 || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Completed Renewals</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="data.renewals?.completed || 0"></span>
                                </div>
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 dark:text-zinc-400">Overdue Renewals</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="data.renewals?.overdue || 0"></span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </template>
        </div>
    </template>
</div>

@push('scripts')
<script>
function reportsPage() {
    return {
        data: null,
        loading: false,

        init() {
            if (this.getToken()) {
                this.loadOverview();
            }
        },

        getToken() {
            return localStorage.getItem('cs_token');
        },

        async loadOverview() {
            this.loading = true;
            const res = await api('GET', '/reports/overview');
            this.loading = false;
            if (res.ok) {
                this.data = res.data.data;
            }
        }
    };
}
</script>
@endpush
@endsection
