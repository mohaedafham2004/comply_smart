@extends('layouts.app')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center py-12" x-data="registerForm()">
    <main class="w-full max-w-[460px]">
        {{-- Header --}}
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-14 h-14 bg-indigo-600/10 text-indigo-600 dark:bg-zinc-800 dark:text-zinc-200 rounded-2xl flex items-center justify-center mb-3 shadow-sm border border-indigo-500/20">
                <span class="material-symbols-outlined text-[32px]" style="font-variation-settings: 'FILL' 1;">
                    verified_user
                </span>
            </div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Create your account</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Start managing Sri Lankan business compliance in seconds.</p>
        </div>

        {{-- Card --}}
        <div class="glass-card rounded-3xl p-6 sm:p-8 space-y-5">
            
            <template x-if="errorMessage">
                <div class="bg-rose-500/10 text-rose-600 dark:text-rose-400 p-3.5 rounded-xl flex items-center gap-2 border border-rose-500/20 text-xs font-semibold">
                    <span class="material-symbols-outlined text-base shrink-0">error</span>
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <form @submit.prevent="submitRegister()" class="space-y-4">
                
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="name">Full Name *</label>
                    <input x-model="form.name" type="text" id="name" required placeholder="Alex Perera" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="email">Work Email *</label>
                    <input x-model="form.email" type="email" id="email" required placeholder="alex@company.lk" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="business_name">Business / Company Name *</label>
                    <input x-model="form.business_name" type="text" id="business_name" required placeholder="Lanka Tech Solutions (Pvt) Ltd" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="password">Password *</label>
                    <input x-model="form.password" type="password" id="password" required placeholder="Min 8 characters" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300 block" for="password_confirmation">Confirm Password *</label>
                    <input x-model="form.password_confirmation" type="password" id="password_confirmation" required placeholder="Confirm password" 
                        class="w-full h-11 px-4 text-xs bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 transition outline-none">
                </div>

                <button type="submit" :disabled="loading" 
                    class="w-full h-11 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center justify-center gap-2 disabled:opacity-50 mt-2 active:scale-95">
                    <span x-show="!loading">Register Business Account</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-sm"></i> Creating Account...
                    </span>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-200 dark:border-zinc-800 text-center text-xs text-slate-500 dark:text-zinc-400">
                Already registered? 
                <a href="/login" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Sign In</a>
            </div>
        </div>
    </main>
</div>

@push('scripts')
<script>
function registerForm() {
    return {
        form: {
            name: '',
            email: '',
            business_name: '',
            password: '',
            password_confirmation: ''
        },
        loading: false,
        errorMessage: '',

        async submitRegister() {
            this.loading = true;
            this.errorMessage = '';

            const res = await api('POST', '/auth/register', this.form);
            this.loading = false;

            const d = res.data?.data || res.data;
            if (res.ok && d && d.token) {
                localStorage.setItem('cs_token', d.token);
                localStorage.setItem('cs_user', JSON.stringify(d.user));
                if (d.business) localStorage.setItem('cs_business', JSON.stringify(d.business));

                window.location.href = '/dashboard';
            } else {
                this.errorMessage = res.data?.message || (res.data?.errors ? Object.values(res.data.errors).flat().join(', ') : 'Registration failed.');
            }
        }
    };
}
</script>
@endpush
@endsection
