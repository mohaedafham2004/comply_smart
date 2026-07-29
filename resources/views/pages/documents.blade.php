@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="documentsPage()" x-init="initDocs()">

    {{-- Top Header & Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Secure Document Vault</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Cloudinary cloud storage with Tesseract OCR text extraction</p>
        </div>

        <a href="/documents/upload" id="btn-upload-doc-page" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-base">cloud_upload</span>
            <span>Upload New Document</span>
        </a>
    </div>

    {{-- Filters & Search Bar --}}
    <div class="glass-card p-4 rounded-2xl flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        
        {{-- Search input --}}
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3.5 top-2.5 text-slate-400 text-sm">search</span>
            <input id="doc-search" x-model="search" @input.debounce.400ms="loadDocs()" type="text" placeholder="Search by document title or OCR extracted text..."
                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
        </div>

        {{-- Category Dropdown & OCR Status filter --}}
        <div class="flex items-center gap-2">
            <select x-model="selectedCategory" @change="loadDocs()" class="bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:border-indigo-500 transition">
                <option value="">All Categories</option>
                <option value="registration">Registration</option>
                <option value="tax">Tax & Revenue</option>
                <option value="license">Trade License</option>
                <option value="permit">Permits & Clearances</option>
                <option value="labor">Labor & EPF/ETF</option>
                <option value="health_safety">Health & Safety</option>
                <option value="financial">Financial & Bank</option>
                <option value="general">General</option>
            </select>

            <select x-model="selectedOcrStatus" @change="loadDocs()" class="bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-zinc-200 focus:outline-none focus:border-indigo-500 transition">
                <option value="">All OCR Statuses</option>
                <option value="done">OCR Done</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="failed">Failed</option>
                <option value="skipped">Skipped</option>
            </select>
        </div>
    </div>

    {{-- Document Cards Grid --}}
    <div x-show="loading" class="text-center py-16 text-slate-500 dark:text-zinc-500 text-xs animate-pulse">
        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-indigo-500 block"></i>
        Loading vault documents...
    </div>

    <template x-if="!loading && docs.length === 0">
        <div class="glass-card p-12 rounded-3xl text-center space-y-3">
            <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-zinc-500">folder_open</span>
            <h3 class="font-hanken text-base font-bold text-slate-900 dark:text-white">No documents found</h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Try adjusting your search query or upload a new compliance document.</p>
            <a href="/documents/upload" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-zinc-100 dark:text-black font-bold text-xs text-white rounded-xl shadow-md">
                <span class="material-symbols-outlined text-base">cloud_upload</span> Upload Document
            </a>
        </div>
    </template>

    <div x-show="!loading && docs.length > 0" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="doc in docs" :key="doc._id">
            <div class="glass-card p-5 rounded-3xl flex flex-col justify-between space-y-4 hover:border-indigo-500/40 dark:hover:border-zinc-700 transition">
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-hanken text-sm font-bold text-slate-900 dark:text-white truncate" x-text="doc.title"></span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                            :class="{
                                'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30': doc.ocr_status === 'done',
                                'bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30': doc.ocr_status === 'processing' || doc.ocr_status === 'pending',
                                'bg-rose-500/10 text-rose-600 dark:text-rose-300 border border-rose-500/30': doc.ocr_status === 'failed',
                                'bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400': doc.ocr_status === 'skipped',
                            }"
                            x-text="doc.ocr_status ? 'OCR: ' + doc.ocr_status : 'Stored'">
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-zinc-400">
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 capitalize font-medium" x-text="doc.category"></span>
                        <span x-text="doc.file_size ? (doc.file_size / 1024).toFixed(1) + ' KB' : ''"></span>
                    </div>

                    <div x-show="doc.ocr_extracted_text" class="p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 text-[10px] text-slate-600 dark:text-zinc-400 font-mono line-clamp-2 border border-slate-200 dark:border-zinc-800"
                        x-text="doc.ocr_extracted_text">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-zinc-800 text-xs">
                    <span class="text-slate-500 dark:text-zinc-500 text-[11px]" x-text="doc.created_at ? new Date(doc.created_at).toLocaleDateString('en-GB') : ''"></span>
                    <div class="flex items-center gap-2">
                        <a :href="'/documents/details?id=' + doc._id" class="px-3 py-1.5 rounded-xl bg-indigo-600/10 text-indigo-600 dark:bg-zinc-800 dark:text-zinc-200 hover:bg-indigo-600 hover:text-white dark:hover:bg-zinc-700 text-xs font-semibold transition flex items-center gap-1">
                            <span>Details</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                        <button @click="deleteDoc(doc._id)" title="Delete" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-zinc-800 transition">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function documentsPage() {
    return {
        docs: [],
        loading: false,
        search: '',
        selectedCategory: '',
        selectedOcrStatus: '',

        async initDocs() {
            if (this.token) {
                await this.loadDocs();
            }
        },

        async loadDocs() {
            this.loading = true;
            let query = [];
            if (this.selectedCategory) query.push(`category=${this.selectedCategory}`);
            if (this.selectedOcrStatus) query.push(`ocr_status=${this.selectedOcrStatus}`);
            if (this.search) query.push(`q=${encodeURIComponent(this.search)}`);

            const url = `/documents` + (query.length ? '?' + query.join('&') : '');
            const res = await api('GET', url);
            this.loading = false;

            if (res.ok) {
                this.docs = res.data.data || [];
            }
        },

        async deleteDoc(id) {
            if (!confirm('Are you sure you want to delete this document?')) return;
            const res = await api('DELETE', `/documents/${id}`);
            if (res.ok) {
                await this.loadDocs();
            } else {
                alert(res.data.message || 'Failed to delete document.');
            }
        }
    };
}
</script>
@endpush
@endsection
