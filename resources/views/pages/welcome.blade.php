@extends('layouts.app')

@section('content')
<div class="space-y-12 py-6">

    {{-- Hero Banner matching Stitch Legal Tech Aesthetic --}}
    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 rounded-3xl p-8 sm:p-12 text-white relative overflow-hidden shadow-2xl border border-indigo-500/20">
        <div class="max-w-3xl space-y-6 relative z-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-outlined text-sm text-blue-400">verified</span>
                <span>Sri Lanka SME Enterprise Edition</span>
            </div>

            <h1 class="font-hanken text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-tight">
                Automated Legal & Tax Compliance <span class="text-blue-400">Simplified</span>
            </h1>

            <p class="text-slate-300 text-base sm:text-lg leading-relaxed font-body">
                ComplySmart brings together a Cloudinary-backed secure document vault, automatic Tesseract OCR text extraction, renewal deadline tracking, and 24/7 AI compliance assistance powered by Google Gemini.
            </p>

            <div class="flex flex-wrap items-center gap-4 pt-2">
                <a href="/register" class="px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 font-bold text-sm text-white shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                    <span>Register Business Profile</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>

                <a href="/login" class="px-6 py-3.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700 font-semibold text-sm text-slate-200 transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-slate-400">login</span>
                    <span>Sign In to Portal</span>
                </a>
            </div>
        </div>

        <div class="absolute -right-16 -bottom-16 w-80 h-80 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Key SME Capabilities Grid --}}
    <div class="grid md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-2xl space-y-3 border-l-4 border-l-blue-600">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">folder_managed</span>
            </div>
            <h3 class="font-hanken font-bold text-lg text-slate-900 dark:text-white">Secure Document Vault</h3>
            <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed">
                Store PDF and image business registration files, trade permits, EPF/ETF forms, and tax receipts securely with Cloudinary integration.
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl space-y-3 border-l-4 border-l-purple-600">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">document_scanner</span>
            </div>
            <h3 class="font-hanken font-bold text-lg text-slate-900 dark:text-white">Tesseract OCR Processing</h3>
            <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed">
                Automatically extract searchable text from uploaded document scans using background Redis queues and Tesseract OCR engine.
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl space-y-3 border-l-4 border-l-emerald-600">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">smart_toy</span>
            </div>
            <h3 class="font-hanken font-bold text-lg text-slate-900 dark:text-white">Gemini AI Compliance Chat</h3>
            <p class="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed">
                Generate tailored SME compliance checklists, summarize legal jargon, and chat directly with Google Gemini AI for Sri Lankan regulations.
            </p>
        </div>
    </div>

</div>
@endsection
