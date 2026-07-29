@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="uploadPage()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Upload Compliance Document</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Files are encrypted, stored on Cloudinary, and processed via Tesseract OCR</p>
        </div>
        <a href="/documents" class="text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Back to Vault</span>
        </a>
    </div>

    <template x-if="error">
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2 font-semibold">
            <span class="material-symbols-outlined text-base text-rose-600 dark:text-rose-400 shrink-0">error</span>
            <span x-text="error"></span>
        </div>
    </template>

    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">

        <form @submit.prevent="upload" class="space-y-5">
            
            {{-- Title --}}
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Document Title *</label>
                <input id="upload-doc-title" x-model="form.title" type="text" required placeholder="e.g. Business Registration Certificate 2024"
                    class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
            </div>

            {{-- Category & Expiry Date --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Category *</label>
                    <select id="upload-doc-category" x-model="form.category" required
                        class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                        <option value="registration">Business Registration (BRN)</option>
                        <option value="tax">Tax & Revenue (TIN/VAT)</option>
                        <option value="license">Trade License</option>
                        <option value="permit">Environmental / Health Permit</option>
                        <option value="labor">Labor / EPF & ETF</option>
                        <option value="health_safety">Health & Safety</option>
                        <option value="financial">Financial & Audit</option>
                        <option value="general">General</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Expiry Date (Optional)</label>
                    <input id="upload-doc-expiry" x-model="form.expiry_date" type="date"
                        class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                </div>
            </div>

            {{-- File Dropzone --}}
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">File Attachment (PDF, PNG, JPG — Max 10MB) *</label>
                <div class="border-2 border-dashed border-slate-300 dark:border-zinc-800 hover:border-indigo-500 rounded-2xl p-6 text-center bg-slate-50/50 dark:bg-zinc-950/50 transition cursor-pointer relative"
                    @dragover.prevent="" @drop.prevent="handleDrop($event)">
                    <input id="upload-doc-file" type="file" required accept=".pdf,.jpg,.jpeg,.png" @change="handleFileSelect($event)"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    
                    <div x-show="!selectedFile" class="space-y-2">
                        <span class="material-symbols-outlined text-4xl text-indigo-500 dark:text-zinc-400">cloud_upload</span>
                        <div class="text-xs font-semibold text-slate-700 dark:text-zinc-300">
                            Drag & drop your document file here, or <span class="text-indigo-600 dark:text-indigo-400 underline">browse</span>
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-zinc-500">Supports PDF, PNG, JPG files up to 10MB</div>
                    </div>

                    <div x-show="selectedFile" class="flex items-center justify-center gap-3 py-2">
                        <span class="material-symbols-outlined text-2xl text-emerald-600 dark:text-emerald-400">description</span>
                        <div class="text-left">
                            <div class="text-xs font-bold text-slate-900 dark:text-white" x-text="selectedFile ? selectedFile.name : ''"></div>
                            <div class="text-[10px] text-slate-500 dark:text-zinc-400" x-text="selectedFile ? (selectedFile.size / 1024).toFixed(1) + ' KB' : ''"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit" id="btn-submit-upload" :disabled="uploading"
                class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2 disabled:opacity-50">
                <span x-show="!uploading" class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">cloud_upload</span>
                    <span>Upload to Cloudinary & Run OCR</span>
                </span>
                <span x-show="uploading" class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-sm"></i> Uploading & Queueing OCR...
                </span>
            </button>

        </form>
    </div>
</div>

@push('scripts')
<script>
function uploadPage() {
    return {
        form: {
            title: '',
            category: 'registration',
            expiry_date: ''
        },
        selectedFile: null,
        uploading: false,
        error: '',

        handleFileSelect(e) {
            if (e.target.files && e.target.files[0]) {
                this.selectedFile = e.target.files[0];
            }
        },

        handleDrop(e) {
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                this.selectedFile = e.dataTransfer.files[0];
            }
        },

        async upload() {
            if (!this.selectedFile) {
                this.error = 'Please select a document file.';
                return;
            }

            this.uploading = true;
            this.error = '';

            const formData = new FormData();
            formData.append('title', this.form.title);
            formData.append('category', this.form.category);
            if (this.form.expiry_date) formData.append('expiry_date', this.form.expiry_date);
            formData.append('file', this.selectedFile);

            const res = await api('POST', '/documents', formData, true);
            this.uploading = false;

            if (res.ok && res.data.data) {
                window.location.href = `/documents/details?id=${res.data.data._id}`;
            } else {
                this.error = res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(', ') : 'Failed to upload document.');
            }
        }
    };
}
</script>
@endpush
@endsection
