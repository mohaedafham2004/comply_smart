@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="profilePage()" x-init="initProfile()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-hanken text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Business Entity Profile</h1>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Manage company registration info and official contact details</p>
        </div>
    </div>

    <template x-if="message">
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2 font-semibold">
            <span class="material-symbols-outlined text-base text-emerald-600 dark:text-emerald-400">check_circle</span>
            <span x-text="message"></span>
        </div>
    </template>

    <template x-if="error">
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2 font-semibold">
            <span class="material-symbols-outlined text-base text-rose-600 dark:text-rose-400">error</span>
            <span x-text="error"></span>
        </div>
    </template>

    <div class="grid md:grid-cols-3 gap-6">

        {{-- Left 1-Col: Profile Overview Card --}}
        <div class="space-y-4">
            <div class="glass-card p-6 rounded-3xl text-center space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-zinc-800 dark:text-zinc-200 border border-indigo-500/20 dark:border-zinc-700 flex items-center justify-center text-2xl font-bold mx-auto">
                    <span class="material-symbols-outlined text-3xl">corporate_fare</span>
                </div>

                <div class="space-y-1">
                    <h2 class="font-hanken text-base font-bold text-slate-900 dark:text-white" x-text="form.name || 'Business Name'"></h2>
                    <div class="text-xs text-indigo-600 dark:text-zinc-400 font-semibold capitalize" x-text="form.type ? form.type.replace('_',' ') : 'SME'"></div>
                    <div class="text-[11px] text-slate-500 dark:text-zinc-500 font-mono" x-text="'BRN: ' + (form.registration_no || 'N/A')"></div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-zinc-800 text-left space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-zinc-400">Owner</span>
                        <span class="text-slate-900 dark:text-white font-semibold" x-text="user?.name || 'Owner'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-zinc-400">Role</span>
                        <span class="text-indigo-600 dark:text-zinc-300 font-semibold uppercase" x-text="user?.role || 'owner'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right 2-Cols: Edit Business Profile Form --}}
        <div class="md:col-span-2">
            <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-zinc-800 pb-3">
                    <span class="material-symbols-outlined text-indigo-600 dark:text-zinc-400">edit_note</span>
                    <span>Update Profile Details</span>
                </h3>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Business / Company Name *</label>
                        <input id="profile-name" x-model="form.name" type="text" required 
                            class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Business Type *</label>
                            <select id="profile-type" x-model="form.type" required 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                                <option value="sole_proprietorship">Sole Proprietorship</option>
                                <option value="private_limited">Private Limited (Pvt Ltd)</option>
                                <option value="partnership">Partnership</option>
                                <option value="retail_store">Retail Store / Shop</option>
                                <option value="restaurant_food">Restaurant / Food Service</option>
                                <option value="other">Other SME</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Registration No (BRN / PV) *</label>
                            <input id="profile-reg-no" x-model="form.registration_no" type="text" required 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Official Phone</label>
                            <input x-model="form.phone" type="text" placeholder="+94 11 234 5678" 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Official Email</label>
                            <input x-model="form.email" type="email" placeholder="contact@company.lk" 
                                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700 dark:text-zinc-300">Registered Office Address</label>
                        <input x-model="form.address" type="text" placeholder="123 Main Street, Colombo 03" 
                            class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 transition">
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" id="btn-save-profile" 
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-zinc-100 dark:hover:bg-white dark:text-black font-bold text-xs text-white rounded-xl shadow-md transition flex items-center gap-2"
                            :disabled="saving">
                            <span x-show="!saving">Save Changes</span>
                            <span x-show="saving" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i> Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function profilePage() {
    return {
        form: {
            name: '',
            type: 'private_limited',
            registration_no: '',
            phone: '',
            email: '',
            address: ''
        },
        saving: false,
        message: '',
        error: '',

        async initProfile() {
            if (this.token) {
                await this.fetchBusiness();
            }
        },

        async fetchBusiness() {
            const res = await api('GET', '/business');
            if (res.ok && res.data.data) {
                const b = res.data.data;
                this.form.name = b.name || '';
                this.form.type = b.type || 'private_limited';
                this.form.registration_no = b.registration_no || '';
                this.form.phone = b.contact?.phone || '';
                this.form.email = b.contact?.email || '';
                this.form.address = b.contact?.address || '';
            }
        },

        async updateProfile() {
            this.saving = true;
            this.message = '';
            this.error = '';

            const payload = {
                name: this.form.name,
                type: this.form.type,
                registration_no: this.form.registration_no,
                contact: {
                    phone: this.form.phone,
                    email: this.form.email,
                    address: this.form.address
                }
            };

            const res = await api('PUT', '/business', payload);
            this.saving = false;

            if (res.ok) {
                this.message = 'Business profile updated successfully!';
                if (res.data.data) {
                    localStorage.setItem('cs_business', JSON.stringify(res.data.data));
                }
            } else {
                this.error = res.data.message || 'Failed to update business profile.';
            }
        }
    };
}
</script>
@endpush
@endsection
