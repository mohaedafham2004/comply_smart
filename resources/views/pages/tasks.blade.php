@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="tasksDashboard()" x-init="initTasks()">

    {{-- Top Header & New Task Modal Trigger --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Compliance Tasks Management</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Assign, track, and complete Sri Lankan regulatory compliance requirements</p>
        </div>

        <button @click="showCreateModal = true" id="btn-create-task-modal"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-base">add_task</span>
            <span>Add Compliance Task</span>
        </button>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="glass-card p-2 rounded-2xl flex flex-wrap gap-1">
        <template x-for="s in ['', 'pending', 'in_progress', 'completed', 'overdue']" :key="s">
            <button @click="filterStatus = s; fetchTasks()"
                class="px-4 py-2 rounded-xl text-xs font-semibold capitalize transition flex items-center gap-1.5"
                :class="filterStatus === s
                    ? 'bg-indigo-600 dark:bg-zinc-100 text-white dark:text-black shadow-md font-bold'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-slate-900 dark:hover:text-white'">
                <span x-text="s === '' ? 'All Tasks' : s.replace('_',' ')"></span>
                <span x-show="s === 'overdue' && getCount('overdue') > 0" class="px-1.5 py-0.2 rounded-full text-[9px] bg-rose-600 text-white font-bold" x-text="getCount('overdue')"></span>
            </button>
        </template>
    </div>

    {{-- Tasks List Grid --}}
    <div x-show="loading" class="text-center py-16 text-slate-500 dark:text-zinc-500 text-xs animate-pulse">
        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-indigo-500 block"></i>
        Loading compliance tasks...
    </div>

    <template x-if="!loading && tasks.length === 0">
        <div class="glass-card p-12 rounded-3xl text-center space-y-4 max-w-md mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 flex items-center justify-center text-2xl mx-auto">
                <span class="material-symbols-outlined text-3xl">assignment</span>
            </div>
            <div class="space-y-1">
                <h3 class="font-hanken text-base font-bold text-slate-900 dark:text-white">No tasks found</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400">Create a task to track tax filings, permit renewals, or health Inspections.</p>
            </div>
            <button @click="showCreateModal = true" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-base">add_task</span>
                <span>Create First Task</span>
            </button>
        </div>
    </template>

    <div x-show="!loading && tasks.length > 0" class="space-y-3">
        <template x-for="task in tasks" :key="task._id">
            <div class="glass-card p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 transition border-l-4"
                :class="{
                    'border-l-rose-500': task.status === 'overdue',
                    'border-l-amber-500': task.status === 'pending',
                    'border-l-sky-500': task.status === 'in_progress',
                    'border-l-emerald-500': task.status === 'completed'
                }">
                
                <div class="space-y-1.5 min-w-0 flex-1">
                    <div class="flex items-center gap-3">
                        <span class="font-hanken text-base font-bold text-slate-900 dark:text-white truncate" x-text="task.title"></span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0"
                            :class="{
                                'bg-rose-500/10 text-rose-600 dark:text-rose-300 border border-rose-500/30': task.status === 'overdue',
                                'bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30': task.status === 'pending',
                                'bg-sky-500/10 text-sky-600 dark:text-sky-300 border border-sky-500/30': task.status === 'in_progress',
                                'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30': task.status === 'completed'
                            }"
                            x-text="task.status?.replace('_',' ')">
                        </span>
                    </div>

                    <p x-show="task.description" class="text-xs text-slate-600 dark:text-zinc-400 line-clamp-1" x-text="task.description"></p>

                    <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 dark:text-zinc-500 pt-1">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                            <span x-text="task.due_date ? 'Due: ' + new Date(task.due_date).toLocaleDateString('en-GB') : 'No due date'"></span>
                        </span>
                        <span class="capitalize px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300" x-text="'Priority: ' + (task.priority || 'medium')"></span>
                    </div>
                </div>

                {{-- Status Change Controls & Actions --}}
                <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-200 dark:border-zinc-800">
                    <template x-if="task.status !== 'completed'">
                        <button @click="updateStatus(task._id, 'completed')" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-600 text-emerald-600 hover:text-white dark:text-emerald-400 text-xs font-semibold transition">
                            Mark Complete
                        </button>
                    </template>

                    <template x-if="task.status === 'pending'">
                        <button @click="updateStatus(task._id, 'in_progress')" class="px-3 py-1.5 rounded-xl bg-sky-500/10 hover:bg-sky-600 text-sky-600 hover:text-white dark:text-sky-400 text-xs font-semibold transition">
                            Start Progress
                        </button>
                    </template>

                    <button @click="deleteTask(task._id)" title="Delete Task" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-zinc-800 transition">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </div>

            </div>
        </template>
    </div>

    {{-- Create Task Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="glass-card w-full max-w-lg p-6 sm:p-8 rounded-3xl space-y-5 border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-zinc-800 pb-3">
                <h3 class="font-hanken text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-500">add_task</span>
                    <span>New Compliance Task</span>
                </h3>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form @submit.prevent="createTask" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Task Title *</label>
                    <input id="create-task-title" x-model="newForm.title" type="text" required placeholder="e.g. File EPF/ETF monthly return for June"
                        class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Due Date *</label>
                        <input id="create-task-due" x-model="newForm.due_date" type="date" required
                            class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Priority</label>
                        <select x-model="newForm.priority" class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Description (Optional)</label>
                    <textarea x-model="newForm.description" rows="3" placeholder="Provide details or requirements for this task..."
                        class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800">Cancel</button>
                    <button type="submit" id="btn-submit-task" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white rounded-xl shadow-md" :disabled="creating">
                        <span x-show="!creating">Create Task</span>
                        <span x-show="creating">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function tasksDashboard() {
    return {
        tasks: [],
        loading: false,
        filterStatus: '',
        showCreateModal: false,
        creating: false,

        newForm: {
            title: '',
            due_date: '',
            priority: 'medium',
            description: ''
        },

        async initTasks() {
            if (this.token) {
                await this.fetchTasks();
            }
        },

        async fetchTasks() {
            this.loading = true;
            const url = '/tasks' + (this.filterStatus ? `?status=${this.filterStatus}` : '');
            const res = await api('GET', url);
            this.loading = false;
            if (res.ok) {
                this.tasks = res.data.data || [];
            }
        },

        async updateStatus(id, newStatus) {
            const res = await api('PATCH', `/tasks/${id}/status`, { status: newStatus });
            if (res.ok) {
                await this.fetchTasks();
            } else {
                alert(res.data.message || 'Failed to update task status.');
            }
        },

        async deleteTask(id) {
            if (!confirm('Are you sure you want to delete this task?')) return;
            const res = await api('DELETE', `/tasks/${id}`);
            if (res.ok) {
                await this.fetchTasks();
            } else {
                alert(res.data.message || 'Failed to delete task.');
            }
        },

        async createTask() {
            this.creating = true;
            const res = await api('POST', '/tasks', this.newForm);
            this.creating = false;

            if (res.ok) {
                this.showCreateModal = false;
                this.newForm = { title: '', due_date: '', priority: 'medium', description: '' };
                await this.fetchTasks();
            } else {
                alert(res.data.message || 'Failed to create task.');
            }
        },

        getCount(status) {
            return this.tasks.filter(t => t.status === status).length;
        }
    };
}
</script>
@endpush
@endsection
