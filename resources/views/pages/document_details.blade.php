@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="docDetailsPage()" x-init="initDocDetails()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/documents" class="text-xs text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">arrow_back</span> Vault
                </a>
                <span class="text-slate-400 dark:text-zinc-600">/</span>
                <span class="text-xs text-indigo-600 dark:text-zinc-300 font-semibold uppercase" x-text="doc?.category || 'General'"></span>
            </div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight" x-text="doc?.title || 'Document Inspector'"></h1>
        </div>

        <div class="flex items-center gap-2">
            <button @click="summarizeWithAI()" class="px-3.5 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-600 text-purple-600 dark:text-purple-300 hover:text-white border border-purple-500/30 text-xs font-semibold transition flex items-center gap-1.5"
                :disabled="summarizing || !doc?.ocr_extracted_text">
                <span class="material-symbols-outlined text-base">smart_toy</span>
                <span x-text="summarizing ? 'Summarizing...' : 'AI Summary'"></span>
            </button>

            <button @click="deleteDocument()" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-600 text-rose-600 dark:text-rose-300 hover:text-white border border-rose-500/30 text-xs font-semibold transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">delete</span>
                <span>Delete</span>
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Left 1-Col: Document Info & Preview Link --}}
        <div class="space-y-4">
            
            {{-- Status Card --}}
            <div class="glass-card p-6 rounded-3xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-zinc-800 pb-3">
                    <span class="text-xs font-bold text-slate-700 dark:text-zinc-300">OCR Engine Status</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize"
                        :class="{
                            'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30': ocrStatus?.ocr_status === 'done',
                            'bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30': ocrStatus?.ocr_status === 'processing' || ocrStatus?.ocr_status === 'pending',
                            'bg-rose-500/10 text-rose-600 dark:text-rose-300 border border-rose-500/30': ocrStatus?.ocr_status === 'failed',
                            'bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400': ocrStatus?.ocr_status === 'skipped',
                        }"
                        x-text="ocrStatus?.ocr_status || 'Pending'">
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-500 dark:text-zinc-400">File Type</span>
                        <span class="text-slate-800 dark:text-zinc-200 font-mono" x-text="doc?.mime_type || 'N/A'"></span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-500 dark:text-zinc-400">File Size</span>
                        <span class="text-slate-800 dark:text-zinc-200 font-mono" x-text="doc?.file_size ? (doc.file_size / 1024).toFixed(1) + ' KB' : 'N/A'"></span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-500 dark:text-zinc-400">Uploaded On</span>
                        <span class="text-slate-800 dark:text-zinc-200" x-text="doc?.created_at ? new Date(doc.created_at).toLocaleDateString('en-GB') : 'N/A'"></span>
                    </div>

                    <div class="flex justify-between py-1">
                        <span class="text-slate-500 dark:text-zinc-400">Expiry Date</span>
                        <span class="text-slate-800 dark:text-zinc-200 font-semibold" x-text="doc?.expiry_date ? new Date(doc.expiry_date).toLocaleDateString('en-GB') : 'None'"></span>
                    </div>
                </div>

                <div class="pt-2">
                    <a :href="doc?.cloudinary_url" target="_blank" x-show="doc?.cloudinary_url" 
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 shadow-md transition">
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>Open Original Document</span>
                    </a>
                </div>
            </div>

            {{-- AI Summary Output Card --}}
            <div x-show="aiSummary" class="glass-card p-6 rounded-3xl space-y-3 border-purple-500/40">
                <div class="flex items-center justify-between border-b border-purple-500/20 pb-2">
                    <span class="text-xs font-bold text-purple-600 dark:text-purple-300 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">smart_toy</span>
                        <span>AI Document Summary</span>
                    </span>
                    <span class="text-[10px] text-purple-500 font-mono">Gemini Flash</span>
                </div>
                <div class="text-xs text-slate-700 dark:text-zinc-300 leading-relaxed font-sans whitespace-pre-line" x-text="aiSummary"></div>
            </div>

        </div>

        {{-- Right 2-Cols: Extracted OCR Text Content --}}
        <div class="lg:col-span-2">
            <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-zinc-800 pb-3">
                    <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600 dark:text-zinc-400">text_snippet</span>
                        <span>OCR Extracted Text</span>
                    </h3>

                    <button @click="copyOcrText()" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-xs font-semibold text-slate-700 dark:text-zinc-200 transition flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">content_copy</span>
                        <span x-text="copied ? 'Copied!' : 'Copy Text'"></span>
                    </button>
                </div>

                <template x-if="!doc?.ocr_extracted_text && ocrStatus?.ocr_status !== 'done'">
                    <div class="text-center py-12 space-y-2 text-slate-500 dark:text-zinc-400 text-xs">
                        <i class="fa-solid fa-circle-notch fa-spin text-xl text-indigo-500 block mb-1"></i>
                        <p>OCR engine is currently processing this document.</p>
                        <button @click="fetchDocDetails()" class="text-indigo-600 dark:text-zinc-300 underline font-semibold mt-2">Click to refresh status</button>
                    </div>
                </template>

                <template x-if="doc?.ocr_extracted_text">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 text-xs text-slate-800 dark:text-zinc-200 font-mono whitespace-pre-wrap leading-relaxed max-h-[500px] overflow-y-auto"
                        x-text="doc.ocr_extracted_text">
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function docDetailsPage() {
    return {
        docId: new URLSearchParams(window.location.search).get('id'),
        doc: null,
        ocrStatus: null,
        aiSummary: '',
        summarizing: false,
        copied: false,

        async initDocDetails() {
            if (this.docId && this.token) {
                await this.fetchDocDetails();
            }
        },

        async fetchDocDetails() {
            const [resDoc, resStatus] = await Promise.all([
                api('GET', `/documents/${this.docId}`),
                api('GET', `/documents/${this.docId}/ocr-status`)
            ]);

            if (resDoc.ok) this.doc = resDoc.data.data;
            if (resStatus.ok) this.ocrStatus = resStatus.data.data;
        },

        async summarizeWithAI() {
            if (!this.doc?._id) return;
            this.summarizing = true;
            const res = await api('POST', '/ai/summarize', { document_id: this.doc._id });
            this.summarizing = false;

            if (res.ok) {
                this.aiSummary = res.data.data.summary;
            } else {
                alert(res.data.message || 'Failed to generate AI summary.');
            }
        },

        copyOcrText() {
            if (this.doc?.ocr_extracted_text) {
                navigator.clipboard.writeText(this.doc.ocr_extracted_text);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }
        },

        async deleteDocument() {
            if (!confirm('Are you sure you want to delete this document from the vault?')) return;
            const res = await api('DELETE', `/documents/${this.docId}`);
            if (res.ok) {
                window.location.href = '/documents';
            } else {
                alert(res.data.message || 'Failed to delete document.');
            }
        }
    };
}
</script>
@endpush
@endsection
