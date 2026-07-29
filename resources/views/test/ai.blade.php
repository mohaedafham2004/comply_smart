@extends('layouts.app')

@section('content')
<div x-data="aiPage()" x-init="init()" class="space-y-6">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-3xl">smart_toy</span>
                <span>AI Compliance Assistant</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Powered by Google Gemini AI for Sri Lankan SME regulatory guidance</p>
        </div>
        <div class="text-xs font-semibold text-purple-700 dark:text-purple-300 bg-purple-500/10 border border-purple-500/30 px-3.5 py-1.5 rounded-full self-start">
            Gemini Flash • Rate Limit: 20 req/min
        </div>
    </div>

    <template x-if="!getToken()">
        <div class="glass-card p-5 rounded-2xl border-l-4 border-l-amber-500 flex items-center gap-3">
            <span class="material-symbols-outlined text-amber-500 text-xl">warning</span>
            <div class="text-xs text-slate-700 dark:text-zinc-300 font-medium">
                Please <a href="/login" class="underline font-bold text-indigo-600 dark:text-indigo-400">Sign in</a> to use Gemini AI compliance features.
            </div>
        </div>
    </template>

    <template x-if="getToken()">
        <div class="grid lg:grid-cols-2 gap-6">

            {{-- ═══════════════════════════════════════════════════════════════
                 LEFT COLUMN: Chat
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="flex flex-col glass-card rounded-3xl overflow-hidden h-[640px]">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-slate-100/80 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Compliance Chatbot</span>
                    </div>
                    <button @click="clearChat" class="text-xs font-semibold text-slate-500 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 transition">
                        Clear History
                    </button>
                </div>

                {{-- Messages --}}
                <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="chatContainer">

                    <template x-if="chatHistory.length === 0">
                        <div class="text-center py-16 space-y-3">
                            <span class="material-symbols-outlined text-4xl text-purple-500 dark:text-zinc-400">forum</span>
                            <p class="text-xs text-slate-600 dark:text-zinc-400 font-medium">Ask me anything about compliance, tax rates, EPF/ETF returns, or permits in Sri Lanka.</p>
                            <div class="pt-2 flex flex-wrap gap-2 justify-center max-w-md mx-auto">
                                <template x-for="q in suggestedQuestions">
                                    <button @click="sendSuggestion(q)"
                                        class="text-xs bg-slate-100 dark:bg-zinc-900 hover:bg-slate-200 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-full px-3 py-1.5 text-slate-700 dark:text-zinc-300 transition text-left"
                                        x-text="q">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-for="msg in chatHistory" :key="msg._id || msg.tempId">
                        <div class="flex gap-3" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[85%] p-3.5 rounded-2xl text-xs leading-relaxed font-sans"
                                :class="msg.role === 'user'
                                    ? 'bg-indigo-600 text-white rounded-br-none shadow-sm font-medium'
                                    : 'bg-slate-100 dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 border border-slate-200 dark:border-zinc-800 rounded-bl-none'">
                                <div class="font-bold text-[10px] uppercase mb-1 opacity-75" x-text="msg.role === 'user' ? 'You' : 'Gemini AI'"></div>
                                <div class="whitespace-pre-line" x-text="msg.content"></div>
                            </div>
                        </div>
                    </template>

                    <div x-show="sendingChat" class="flex justify-start">
                        <div class="bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 p-3 rounded-2xl text-xs text-slate-600 dark:text-zinc-400 flex items-center gap-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-purple-500"></i>
                            <span>Gemini is thinking...</span>
                        </div>
                    </div>
                </div>

                {{-- Input Bar --}}
                <div class="p-3 bg-white dark:bg-zinc-950 border-t border-slate-200 dark:border-zinc-800">
                    <form @submit.prevent="sendChat" class="flex items-center gap-2">
                        <input x-model="chatInput" type="text" placeholder="Type your compliance question..."
                            class="flex-1 bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition"
                            :disabled="sendingChat">
                        <button type="submit" :disabled="sendingChat || !chatInput.trim()"
                            class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black text-white font-bold text-xs rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">send</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 RIGHT COLUMN: Checklist & Summarizer Tools
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="space-y-6">

                {{-- Tool 1: Compliance Checklist Generator --}}
                <div class="glass-card p-6 rounded-3xl space-y-4">
                    <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                        <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">checklist</span>
                        <span>AI Compliance Checklist Generator</span>
                    </h3>

                    <form @submit.prevent="generateChecklist" class="space-y-3">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Business Type *</label>
                                <select x-model="checklistForm.business_type" required class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                                    <option value="private_limited">Private Limited (Pvt Ltd)</option>
                                    <option value="sole_proprietorship">Sole Proprietorship</option>
                                    <option value="restaurant_food">Restaurant / Food Service</option>
                                    <option value="retail_store">Retail Store / E-commerce</option>
                                    <option value="tech_software">Software / IT Exports</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Description (Optional)</label>
                                <input x-model="checklistForm.description" type="text" placeholder="e.g. Hiring 10 staff in Colombo" class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                            </div>
                        </div>

                        <button type="submit" :disabled="generatingChecklist" class="w-full py-2.5 bg-purple-600 hover:bg-purple-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2">
                            <span x-show="!generatingChecklist" class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">auto_awesome</span>
                                <span>Generate Custom Checklist</span>
                            </span>
                            <span x-show="generatingChecklist">Generating Checklist...</span>
                        </button>
                    </form>

                    <template x-if="checklistResult && checklistResult.length > 0">
                        <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-zinc-800">
                            <template x-for="item in checklistResult" :key="item.title">
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-xs space-y-1">
                                    <div class="flex items-center justify-between font-bold text-slate-900 dark:text-white">
                                        <span x-text="item.title"></span>
                                        <span class="text-[10px] text-purple-600 dark:text-purple-300 font-mono" x-text="'Due in ' + item.suggested_due_in_days + 'd'"></span>
                                    </div>
                                    <p class="text-slate-600 dark:text-zinc-400 text-[11px]" x-text="item.description"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Tool 2: Regulation Summarizer --}}
                <div class="glass-card p-6 rounded-3xl space-y-4">
                    <h3 class="font-hanken text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                        <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">summarize</span>
                        <span>Regulation Legal Summarizer</span>
                    </h3>

                    <form @submit.prevent="summarizeText" class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Raw Legal / Act Text *</label>
                            <textarea x-model="summaryText" rows="3" required placeholder="Paste legal acts, circulars, or regulations here..." class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl p-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition resize-none"></textarea>
                        </div>

                        <button type="submit" :disabled="summarizingText" class="w-full py-2.5 bg-purple-600 hover:bg-purple-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2">
                            <span x-show="!summarizingText" class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">short_text</span>
                                <span>Summarize Plain Language</span>
                            </span>
                            <span x-show="summarizingText">Summarizing...</span>
                        </button>
                    </form>

                    <template x-if="summaryResult">
                        <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-xs text-slate-800 dark:text-zinc-200 space-y-2">
                            <div class="font-bold text-purple-600 dark:text-purple-300 text-xs">Summary Result:</div>
                            <div class="whitespace-pre-line leading-relaxed" x-text="summaryResult"></div>
                        </div>
                    </template>
                </div>

            </div>

        </div>
    </template>
</div>

@push('scripts')
<script>
function aiPage() {
    return {
        chatHistory: [],
        chatInput: '',
        sendingChat: false,
        suggestedQuestions: [
            "What are the EPF & ETF contribution percentages in Sri Lanka?",
            "What is the SVAT registration threshold for Sri Lanka SMEs?",
            "What documents are required for municipal trade license renewal?"
        ],

        checklistForm: {
            business_type: 'private_limited',
            description: ''
        },
        checklistResult: null,
        generatingChecklist: false,

        summaryText: '',
        summaryResult: null,
        summarizingText: false,

        init() {
            if (this.getToken()) {
                this.loadChatHistory();
            }
        },

        getToken() {
            return localStorage.getItem('cs_token');
        },

        async loadChatHistory() {
            const res = await api('GET', '/ai/chat/history');
            if (res.ok) {
                this.chatHistory = res.data.data || [];
            }
        },

        async sendChat() {
            if (!this.chatInput.trim()) return;
            const message = this.chatInput;
            this.chatInput = '';
            this.sendingChat = true;

            this.chatHistory.push({ role: 'user', content: message, tempId: Date.now() });
            this.scrollToBottom();

            const res = await api('POST', '/ai/chat', { message });
            this.sendingChat = false;

            if (res.ok && res.data.data) {
                this.chatHistory.push({ role: 'assistant', content: res.data.data.reply });
            } else {
                this.chatHistory.push({ role: 'assistant', content: 'Error: ' + (res.data.message || 'AI service unavailable.') });
            }
            this.scrollToBottom();
        },

        sendSuggestion(q) {
            this.chatInput = q;
            this.sendChat();
        },

        async clearChat() {
            this.chatHistory = [];
        },

        async generateChecklist() {
            this.generatingChecklist = true;
            this.checklistResult = null;

            const res = await api('POST', '/ai/checklist', this.checklistForm);
            this.generatingChecklist = false;

            if (res.ok && res.data.data) {
                this.checklistResult = res.data.data.checklist || [];
            } else {
                alert(res.data.message || 'Failed to generate checklist.');
            }
        },

        async summarizeText() {
            if (!this.summaryText.trim()) return;
            this.summarizingText = true;
            this.summaryResult = null;

            const res = await api('POST', '/ai/summarize', { text: this.summaryText });
            this.summarizingText = false;

            if (res.ok && res.data.data) {
                this.summaryResult = res.data.data.summary;
            } else {
                alert(res.data.message || 'Failed to generate summary.');
            }
        },

        scrollToBottom() {
            setTimeout(() => {
                if (this.$refs.chatContainer) {
                    this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
                }
            }, 100);
        }
    };
}
</script>
@endpush
@endsection
