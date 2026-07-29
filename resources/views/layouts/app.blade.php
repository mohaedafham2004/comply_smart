<!DOCTYPE html>
<html lang="en" class="h-full" x-data="themeManager()" :class="{ 'dark': theme === 'dark', 'light': theme === 'light' }">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $title ?? 'ComplySmart — Enterprise Compliance Platform' }}</title>
    
    <!-- Instant Theme Initializer Script (Prevents Any White Flash on Navigation) -->
    <script>
        (function() {
            var t = localStorage.getItem('cs_theme') || 'light';
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Google Fonts: Hanken Grotesk & Inter (Official Stitch System) -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Material Symbols Outlined & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        },
                        amber: {
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                        emerald: {
                            500: '#10b981',
                            600: '#059669',
                        },
                        purple: {
                            500: '#a855f7',
                            600: '#9333ea',
                        }
                    },
                    borderRadius: {
                        "DEFAULT": "0.375rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "3xl": "1.5rem",
                        "full": "9999px"
                    },
                    fontFamily: {
                        "headline": ["Hanken Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"],
                    }
                },
            },
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        
        html { scroll-behavior: smooth; }
        
        /* Eliminate White Flash in Dark Theme */
        html.dark, html.dark body {
            background-color: #000000 !important;
            color: #f4f4f5 !important;
            color-scheme: dark;
        }
        
        html.light, html.light body {
            background-color: #f8fafc !important;
            color-scheme: light;
        }

        body { font-family: 'Inter', sans-serif; }
        .font-hanken { font-family: 'Hanken Grotesk', sans-serif; }
        
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }

        /* ⚡ Smooth Dark/Light Page Animations */
        main {
            animation: pageFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes pageFadeIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Interactive Micro Animations */
        button, a, input, select, textarea, .glass-card {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        }

        button:active, a:active {
            transform: scale(0.98);
        }

        /* 🎨 Active Sidebar Link Highlighting */
        .sidebar-active {
            border-left: 4px solid #4f46e5;
            background: rgba(79, 70, 229, 0.08);
            font-weight: 700;
            color: #4f46e5;
        }

        .dark .sidebar-active {
            border-left: 4px solid #ffffff;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* 💎 Monochromatic Glass Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 20px -2px rgba(148, 163, 184, 0.08);
        }
        .dark .glass-card {
            background: #09090b;
            backdrop-filter: blur(16px);
            border: 1px solid #27272a;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.8);
        }

        /* Typography */
        .brand-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .dark .brand-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        .dark ::-webkit-scrollbar-thumb { background: #27272a; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-black text-slate-800 dark:text-zinc-100 antialiased selection:bg-indigo-600 selection:text-white transition-colors duration-200" x-data="globalApp()" x-init="initApp()">

<div class="min-h-screen flex flex-col bg-slate-50 dark:bg-black">

    {{-- Top Navigation Header (Pure Black Dark / Crisp Light) --}}
    <header class="fixed top-0 w-full flex justify-between items-center px-6 bg-white/95 dark:bg-black/95 border-b border-slate-200/80 dark:border-zinc-800 z-50 h-16 backdrop-blur-md transition-colors duration-200">
        
        {{-- Brand Logo & Search --}}
        <div class="flex items-center gap-6">
            <a href="/dashboard" class="font-hanken font-extrabold text-xl text-slate-900 dark:text-white flex items-center gap-2 tracking-tight hover:opacity-90 active:scale-95 transition">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-indigo-700 dark:from-zinc-800 dark:to-zinc-900 text-white flex items-center justify-center border border-transparent dark:border-zinc-700 shadow-md">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                </div>
                <span>Comply<span class="brand-gradient">Smart</span></span>
            </a>

            <div x-show="!isAuthPage()" class="hidden md:flex items-center bg-slate-100 dark:bg-zinc-900/90 px-4 py-1.5 rounded-full border border-slate-200 dark:border-zinc-800 w-80 focus-within:border-indigo-500 transition-colors">
                <span class="material-symbols-outlined text-slate-400 dark:text-zinc-500 text-xl">search</span>
                <input class="bg-transparent border-none focus:ring-0 text-xs text-slate-800 dark:text-zinc-200 w-full ml-2 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none" placeholder="Search compliance records, tasks, docs..." type="text"/>
            </div>
        </div>

        {{-- Top Right Controls --}}
        <div class="flex items-center gap-3">
            
            {{-- Default Light / Dark Theme Switcher --}}
            <button @click="toggleTheme()" class="p-2.5 hover:bg-slate-100 dark:hover:bg-zinc-900 transition rounded-full text-slate-600 dark:text-zinc-300 flex items-center gap-1.5 active:scale-90" title="Toggle Light / Dark Mode">
                <span class="material-symbols-outlined text-xl text-amber-500 dark:text-zinc-300" x-text="theme === 'dark' ? 'dark_mode' : 'light_mode'"></span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400" x-text="theme === 'dark' ? 'Dark' : 'Light'"></span>
            </button>

            <a href="/notifications" x-show="!isAuthPage()" class="p-2.5 hover:bg-slate-100 dark:hover:bg-zinc-900 transition rounded-full relative text-slate-600 dark:text-zinc-300 active:scale-90">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span x-show="unreadCount > 0" class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-rose-600 rounded-full shadow-md"></span>
            </a>

            <div class="h-6 w-px bg-slate-200 dark:bg-zinc-800 mx-1"></div>

            {{-- User Avatar / Login buttons --}}
            <template x-if="!token">
                <div class="flex items-center gap-2">
                    <a href="/login" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white transition">Sign In</a>
                    <a href="/register" class="px-4 py-1.5 text-xs font-bold bg-indigo-600 dark:bg-zinc-100 hover:bg-indigo-500 dark:hover:bg-white text-white dark:text-black rounded-full transition shadow-md active:scale-95">Register</a>
                </div>
            </template>

            <template x-if="token">
                <div class="flex items-center gap-3 pl-1">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-900 dark:text-white" x-text="user?.name || 'Business Owner'"></p>
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400 capitalize" x-text="user?.role || 'Owner'"></p>
                    </div>
                    <a href="/profile" class="w-9 h-9 rounded-full bg-indigo-600/10 dark:bg-zinc-800 text-indigo-600 dark:text-white border border-indigo-200 dark:border-zinc-700 flex items-center justify-center font-bold text-xs shrink-0 shadow-md active:scale-90 transition">
                        <span x-text="user?.name ? user.name.charAt(0).toUpperCase() : 'U'"></span>
                    </a>
                    <button @click="logout()" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-900 transition active:scale-90">
                        <span class="material-symbols-outlined text-lg">logout</span>
                    </button>
                </div>
            </template>
        </div>
    </header>

    {{-- Main Container with Fixed Left Sidebar --}}
    <div class="flex-1 flex overflow-hidden pt-16 bg-slate-50 dark:bg-black">

        {{-- ── SideNavBar (Black Dark Mode / Crisp Light Mode) ──────────────── --}}
        <aside x-show="!isAuthPage()" class="fixed left-0 top-0 h-full flex flex-col pt-20 pb-6 w-[260px] z-40 bg-white/95 border-r border-slate-200/80 dark:bg-black/95 dark:border-zinc-800 hidden md:flex transition-colors duration-200">
            
            {{-- Business Banner Card --}}
            <div class="px-4 mb-4">
                <div class="p-3.5 rounded-2xl bg-slate-100/90 dark:bg-zinc-900/90 border border-slate-200 dark:border-zinc-800 flex items-center justify-between shadow-sm">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="business?.name || 'My Business'"></div>
                        <div class="text-[10px] text-indigo-600 dark:text-zinc-400 font-semibold truncate capitalize" x-text="business?.type ? business.type.replace('_',' ') : 'SME Profile'"></div>
                    </div>
                    <a href="/profile" class="w-7 h-7 rounded-xl bg-indigo-600/10 text-indigo-600 hover:bg-indigo-600 hover:text-white dark:bg-zinc-800 dark:text-zinc-300 dark:hover:text-white flex items-center justify-center text-xs transition active:scale-90">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </a>
                </div>
            </div>

            {{-- Vertically Aligned Stitch Navigation Items --}}
            <nav class="flex-1 px-3 space-y-1 overflow-y-auto">

                <a href="/dashboard" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/dashboard') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">dashboard</span>
                    <span class="truncate">Dashboard</span>
                </a>

                <a href="/profile" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/profile') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">business_center</span>
                    <span class="truncate">Business Profile</span>
                </a>

                <a href="/documents" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/documents') && !isActive('/documents/upload')
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">description</span>
                    <span class="truncate">Document Vault</span>
                </a>

                <a href="/documents/upload" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/documents/upload') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg text-emerald-600 dark:text-emerald-400">cloud_upload</span>
                    <span class="truncate">Upload Document</span>
                </a>

                <a href="/tasks" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/tasks') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">assignment</span>
                    <span class="truncate">Compliance Tasks</span>
                </a>

                <a href="/renewals" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/renewals') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg text-amber-600 dark:text-amber-400">event_repeat</span>
                    <span class="truncate">Renewals</span>
                </a>

                <a href="/ai" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/ai') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg text-purple-600 dark:text-purple-400">smart_toy</span>
                    <span class="truncate">AI Assistant</span>
                    <span class="ml-auto text-[9px] font-bold px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-300 border border-purple-500/20">Gemini</span>
                </a>

                <a href="/reports" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/reports') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">analytics</span>
                    <span class="truncate">Analytics & Reports</span>
                </a>

                <a href="/notifications" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 w-full font-medium active:scale-95"
                    :class="isActive('/notifications') 
                        ? 'sidebar-active' 
                        : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white'">
                    <span class="material-symbols-outlined text-lg">notifications</span>
                    <span class="truncate">Notifications</span>
                    <span x-show="unreadCount > 0" class="ml-auto px-2 py-0.5 text-[10px] font-bold bg-rose-600 text-white rounded-full shadow-sm" x-text="unreadCount"></span>
                </a>

            </nav>

            {{-- Footer Controls & Settings --}}
            <div class="px-3 pt-4 border-t border-slate-200/80 dark:border-zinc-800 space-y-1">
                <a href="/profile" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-white transition-all font-medium active:scale-95">
                    <span class="material-symbols-outlined text-lg">settings</span>
                    <span>Settings</span>
                </a>
            </div>
        </aside>

        {{-- ── Main Content Canvas ────────────────────────────────────────── --}}
        <main class="flex-1 md:ml-[260px] p-6 md:p-8 overflow-y-auto bg-slate-50 dark:bg-black">
            <div class="max-w-6xl mx-auto">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script>
function themeManager() {
    return {
        theme: localStorage.getItem('cs_theme') || 'light',

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('cs_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

function globalApp() {
    return {
        token: localStorage.getItem('cs_token') || '',
        user: JSON.parse(localStorage.getItem('cs_user') || 'null'),
        business: JSON.parse(localStorage.getItem('cs_business') || 'null'),
        unreadCount: 0,

        async initApp() {
            if (this.token) {
                await this.fetchMe();
                await this.fetchUnreadNotifications();
            }
        },

        isAuthPage() {
            const p = window.location.pathname;
            return p === '/login' || p === '/register' || p === '/welcome' || p === '/test/auth';
        },

        isActive(path) {
            const p = window.location.pathname;
            if (path === '/dashboard' && (p === '/' || p === '/dashboard' || p === '/test')) return true;
            return p.startsWith(path);
        },

        async fetchMe() {
            const res = await api('GET', '/auth/me');
            if (res.ok) {
                this.user = res.data.data?.user || res.data.data;
                this.business = res.data.data?.business || this.business;
                localStorage.setItem('cs_user', JSON.stringify(this.user));
                if (this.business) localStorage.setItem('cs_business', JSON.stringify(this.business));
            } else if (res.status === 401) {
                this.logoutSilently();
            }
        },

        async fetchUnreadNotifications() {
            const res = await api('GET', '/notifications?unread_only=1');
            if (res.ok) {
                this.unreadCount = res.data.meta?.unread ?? (res.data.data ? res.data.data.length : 0);
            }
        },

        logout() {
            api('POST', '/auth/logout');
            this.logoutSilently();
            window.location.href = '/login';
        },

        logoutSilently() {
            this.token = '';
            this.user = null;
            this.business = null;
            localStorage.removeItem('cs_token');
            localStorage.removeItem('cs_user');
            localStorage.removeItem('cs_business');
        }
    };
}

/**
 * Universal API Client Helper
 */
async function api(method, endpoint, body = null, isMultipart = false) {
    const token = localStorage.getItem('cs_token');
    const url = endpoint.startsWith('http') ? endpoint : `/api/v1${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;

    const headers = {
        'Accept': 'application/json',
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    let payload = body;
    if (body && !isMultipart && typeof body === 'object') {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    try {
        const response = await fetch(url, {
            method,
            headers,
            body: payload,
        });

        const data = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            status: response.status,
            data: data
        };
    } catch (e) {
        return {
            ok: false,
            status: 0,
            data: { message: 'Network connection failed.' }
        };
    }
}
</script>

@stack('scripts')
</body>
</html>
