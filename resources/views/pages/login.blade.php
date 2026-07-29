@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12" x-data="loginForm()">
    <main class="w-full max-w-[420px]">
        {{-- Branding Header --}}
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-16 h-16 bg-indigo-600/10 text-indigo-600 dark:bg-zinc-800 dark:text-zinc-200 rounded-2xl flex items-center justify-center mb-4 shadow-sm border border-indigo-500/20">
                <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">
                    verified_user
                </span>
            </div>
            <h1 class="font-hanken text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">ComplySmart</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Secure SME Enterprise Compliance Portal</p>
        </div>

        {{-- Login Card --}}
        <div class="glass-card rounded-3xl p-6 sm:p-8 space-y-6">
            
            {{-- Validation Error Alert --}}
            <template x-if="errorMessage">
                <div class="bg-rose-500/10 text-rose-600 dark:text-rose-400 p-3.5 rounded-xl flex items-center gap-2 border border-rose-500/20 text-xs font-semibold">
                    <span class="material-symbols-outlined text-base shrink-0">error</span>
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <form @submit.prevent="submitLogin()" class="space-y-4">
                
                {{-- Email Field --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="email">Work Email</label>
                    <input x-model="form.email" type="email" id="email" required placeholder="owner@company.lk" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                {{-- Password Field --}}
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="password">Password</label>
                    </div>
                    <input x-model="form.password" type="password" id="password" required placeholder="••••••••" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                {{-- Keep Logged In Checkbox --}}
                <div class="flex items-center gap-2 py-1">
                    <input type="checkbox" id="remember" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <label for="remember" class="text-xs text-slate-600 dark:text-zinc-400 font-medium">Keep me logged in</label>
                </div>

                {{-- Submit Button --}}
                <button type="submit" :disabled="loading" 
                    class="w-full h-11 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2 disabled:opacity-50 active:scale-95">
                    <span x-show="!loading">Sign In to Dashboard</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-sm"></i> Authenticating...
                    </span>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-200 dark:border-zinc-800 text-center text-xs text-slate-500 dark:text-zinc-400">
                Don't have a business account? 
                <a href="/register" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Create Account</a>
            </div>
        </div>
    </main>
</div>

@push('scripts')
<script>
function loginForm() {
    return {
        form: {
            email: '',
            password: ''
        },
        loading: false,
        errorMessage: '',

        async submitLogin() {
            this.loading = true;
            this.errorMessage = '';

            const res = await api('POST', '/auth/login', this.form);
            this.loading = false;

            const d = res.data?.data || res.data;
            if (res.ok && d && d.token) {
                localStorage.setItem('cs_token', d.token);
                localStorage.setItem('cs_user', JSON.stringify(d.user));
                if (d.business) localStorage.setItem('cs_business', JSON.stringify(d.business));

                window.location.href = '/dashboard';
            } else {
                this.errorMessage = res.data?.message || (res.data?.errors ? Object.values(res.data.errors).flat().join(', ') : 'Invalid email or password.');
            }
        }
    };
}
</script>
@endpush
@endsection
